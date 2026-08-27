<?php

namespace App\Http\Controllers\Mars;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ItemOutstanding;
use App\Models\DataPO;
use App\Models\ItemMaster;

class OutstandingController extends Controller
{
    /**
     * Check if user has master or admin access
     */
    private function checkMasterAccess()
    {
        if (!auth()->check() || (!auth()->user()->isMaster() && !in_array(auth()->user()->username, ['master', 'admin']))) {
            abort(403, 'Akses ditolak: Hanya Administrator atau Master User yang dapat mengakses halaman ini.');
        }
    }

    /**
     * Display a listing of item outstanding requests.
     */
    public function index()
    {
        $this->checkMasterAccess();

        $requests = ItemOutstanding::orderBy('request_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $itemCodes = $requests->pluck('item_code')->unique()->toArray();
        $poItems = DataPO::whereIn('item_code', $itemCodes)->get();
        $masterItems = ItemMaster::whereIn('item_code', $itemCodes)->get();
        
        $masterLookup = [];
        foreach ($masterItems as $master) {
            $key = strtolower(trim($master->item_code) . '|' . trim($master->item_name));
            $masterLookup[$key] = $master;
        }

        $poLookup = [];
        foreach ($poItems as $po) {
            $key = strtolower(trim($po->item_code) . '|' . trim($po->item_name));
            if (!isset($poLookup[$key])) {
                $poLookup[$key] = [];
            }
            $poLookup[$key][] = $po;
        }

        $processedRequests = $requests->map(function ($req) use ($poLookup, $masterLookup) {
            $reqKey = strtolower(trim($req->item_code) . '|' . trim($req->item_name));

            $matchingPOs = $poLookup[$reqKey] ?? [];

            $poGroups = [];
            foreach ($matchingPOs as $po) {
                $poNo = trim($po->po_no);
                if (empty($poNo))
                    $poNo = '-';

                if (!isset($poGroups[$poNo])) {
                    $poGroups[$poNo] = [
                        'po_no' => $poNo,
                        'total_qty' => 0,
                        'supplier_name' => $po->supplier_name ?? '-',
                        'items' => []
                    ];
                }
                $poGroups[$poNo]['total_qty'] += (int) $po->scheduled_receipt_qty;
                $poGroups[$poNo]['items'][] = $po;
            }

            $reqArray = $req->toArray();
            $reqArray['po_data'] = array_values($poGroups);
            $reqArray['total_receipt_qty'] = array_sum(array_column($poGroups, 'total_qty'));
            $reqArray['has_multiple_po'] = count($poGroups) > 1;

            if (isset($masterLookup[$reqKey])) {
                $reqArray['ending_balance'] = $masterLookup[$reqKey]->ending_balance;
            }

            return $reqArray;
        });

        return view('mars.item_outstanding', ['requests' => $processedRequests->all()]);
    }

    /**
     * Store a newly created request.
     */
    public function store(Request $request)
    {
        $this->abortIfGuest();
        $this->checkMasterAccess();
        $validated = $request->validate([
            'item_code' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'user' => 'nullable|string|max:255',
            'outstanding' => 'nullable|integer|min:0',
            'sudah_pp' => 'nullable|integer|min:0',
            'outstanding_pp' => 'nullable|string|max:255',
            'ending_balance' => 'nullable|integer|min:0',
            'maximal_stock' => 'nullable|integer|min:0',
            'order_point' => 'nullable|integer|min:0',
            'minimal_stock' => 'nullable|integer|min:0',
        ]);

        $exists = ItemOutstanding::where('item_code', $validated['item_code'])
            ->where('item_name', $validated['item_name'])
            ->exists();

        ItemOutstanding::create([
            'request_date' => now()->format('Y-m-d'),
            'item_code' => $validated['item_code'],
            'item_name' => $validated['item_name'],
            'user' => $validated['user'] ?? '',
            'outstanding' => $validated['outstanding'] ?? 0,
            'sudah_pp' => $validated['sudah_pp'] ?? 0,
            'outstanding_pp' => $validated['outstanding_pp'] ?? '',
            'ending_balance' => $validated['ending_balance'] ?? 0,
            'maximal_stock' => $validated['maximal_stock'] ?? 0,
            'order_point' => $validated['order_point'] ?? 0,
            'minimal_stock' => $validated['minimal_stock'] ?? 0,
            'note' => null,
            'imported_at' => now(),
            'duplicate_note' => $exists ? 'Item ini sudah ada di list' : null,
        ]);

        $message = $exists
            ? 'Request ditambahkan dengan note: Item ini sudah ada di list'
            : 'Request berhasil dibuat!';

        return redirect()->route('mars.item_outstanding.index')->with('success', $message);
    }

    /**
     * Import Excel file and process the data
     */
    public function importExcel(Request $request)
    {
        $this->abortIfGuest();
        $this->checkMasterAccess();
        
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls',
        ]);

        DB::beginTransaction();
        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            array_shift($rows);

            $today = now()->format('Y-m-d');
            $imported = 0;
            $updated = 0;

            $currentOutstandingSums = ItemOutstanding::selectRaw("item_code, item_name, SUM(outstanding) as total")
                ->groupBy('item_code', 'item_name')
                ->get()
                ->mapWithKeys(function ($item) {
                    $key = strtolower(trim($item->item_code) . '|' . trim($item->item_name));
                    return [$key => (int) $item->total];
                });

            foreach ($rows as $row) {
                if (empty(array_filter($row)))
                    continue;

                $itemCode = trim($row[0] ?? '');
                $itemName = trim($row[1] ?? '');
                $excelOutstanding = (int) ($row[2] ?? 0);
                $endingBalance = $row[3] ?? 0;
                $maximalStock = $row[4] ?? 0;
                $orderPoint = $row[5] ?? 0;
                $minimalStock = $row[6] ?? 0;
                $user = trim($row[7] ?? '');
                $outstandingPp = trim($row[8] ?? '');

                if (empty($itemCode) || empty($itemName))
                    continue;

                $itemKey = strtolower($itemCode . '|' . $itemName);
                $currentTotal = $currentOutstandingSums[$itemKey] ?? 0;

                $outstandingDifference = $excelOutstanding - $currentTotal;

                $latestRequest = ItemOutstanding::where('item_code', $itemCode)
                    ->where('item_name', $itemName)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($latestRequest) {
                    $currentRequestOutstanding = $latestRequest->outstanding;
                    $newOutstanding = max(0, $currentRequestOutstanding + $outstandingDifference);

                    $latestRequest->outstanding = $newOutstanding;
                    $latestRequest->outstanding_pp = $outstandingPp;
                    $latestRequest->ending_balance = (int) ($endingBalance ?: 0);
                    $latestRequest->maximal_stock = (int) ($maximalStock ?: 0);
                    $latestRequest->order_point = (int) ($orderPoint ?: 0);
                    $latestRequest->minimal_stock = (int) ($minimalStock ?: 0);
                    $latestRequest->user = $user;
                    $latestRequest->imported_at = now();
                    $latestRequest->save();

                    $currentOutstandingSums[$itemKey] = ($currentOutstandingSums[$itemKey] ?? 0) + $outstandingDifference;
                    $updated++;
                } else {
                    ItemOutstanding::create([
                        'request_date' => $today,
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'user' => $user,
                        'outstanding' => $excelOutstanding,
                        'outstanding_pp' => $outstandingPp,
                        'ending_balance' => (int) ($endingBalance ?: 0),
                        'maximal_stock' => (int) ($maximalStock ?: 0),
                        'order_point' => (int) ($orderPoint ?: 0),
                        'minimal_stock' => (int) ($minimalStock ?: 0),
                        'imported_at' => now(),
                    ]);
                    $currentOutstandingSums[$itemKey] = $excelOutstanding;
                    $imported++;
                }
            }

            ItemOutstanding::where('outstanding', '<=', 0)->delete();

            DB::commit();

            $message = "Import berhasil! {$imported} item baru ditambahkan.";
            if ($updated > 0) {
                $message .= " {$updated} item duplicate diperbarui (outstanding, outstanding pp).";
            }

            return redirect()->route('mars.item_outstanding.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Item Outstanding Error: ' . $e->getMessage());
            return redirect()->route('mars.item_outstanding.index')
                ->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    /**
     * Update note for a request.
     */
    public function updateNote(Request $request, $id)
    {
        $this->abortIfGuest();
        $this->checkMasterAccess();
        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $item = ItemOutstanding::find($id);
        if ($item) {
            $item->note = $validated['note'];
            $item->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Note berhasil diperbarui',
        ]);
    }

    /**
     * Update sudah follow for a request.
     */
    public function updateFollow(Request $request, $id)
    {
        $this->abortIfGuest();
        $this->checkMasterAccess();
        $validated = $request->validate([
            'sudah_follow' => 'nullable|string|in:YES,NO,',
        ]);

        $item = ItemOutstanding::find($id);
        if ($item) {
            $item->sudah_follow = $validated['sudah_follow'];
            $item->sudah_follow_edited_at = now();
            $item->save();

            $this->syncToMaster($item, [
                'sudah_follow' => $item->sudah_follow,
                'sudah_follow_edited_at' => $item->sudah_follow_edited_at
            ]);
        }

        $formattedDate = strtolower(now()->format('M d, H:i'));

        return response()->json([
            'success' => true,
            'message' => 'SUDAH FOLLOW berhasil diperbarui',
            'last_edited' => $formattedDate,
        ]);
    }

    /**
     * Update pengiriman tanggal for a request.
     */
    public function updatePengirimanTanggal(Request $request, $id)
    {
        $this->abortIfGuest();
        $this->checkMasterAccess();
        $validated = $request->validate([
            'pengiriman_tanggal' => 'nullable|date',
        ]);

        $item = ItemOutstanding::find($id);
        if ($item) {
            $item->pengiriman_tanggal = $validated['pengiriman_tanggal'];
            $item->pengiriman_tanggal_edited_at = now();
            $item->save();

            $this->syncToMaster($item, [
                'pengiriman_tanggal' => $item->pengiriman_tanggal,
                'pengiriman_tanggal_edited_at' => $item->pengiriman_tanggal_edited_at
            ]);
        }

        $formattedDate = strtolower(now()->format('M d, H:i'));

        return response()->json([
            'success' => true,
            'message' => 'PENGIRIMAN TANGGAL berhasil diperbarui',
            'last_edited' => $formattedDate,
        ]);
    }

    /**
     * Update request WHC for a request.
     */
    public function updateRequestWhc(Request $request, $id)
    {
        if (!auth()->check() || (!auth()->user()->hasRole(['master', 'whc', 'admin', 'warehouse']) && !in_array(auth()->user()->username, ['master', 'whc', 'admin', 'warehouse']))) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya user master atau whc yang dapat mengisi Request WHC.'
            ], 403);
        }
        $validated = $request->validate([
            'request_whc' => 'nullable|integer|min:0',
            'source' => 'nullable|string|max:50',
        ]);

        if (($validated['source'] ?? null) === 'item_minim') {
            $masterItem = ItemMaster::find($id);
            if (!$masterItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak ditemukan'
                ], 404);
            }

            $masterItem->request_whc = $validated['request_whc'];
            $masterItem->request_whc_edited_at = now();
            $masterItem->save();

            $formattedDate = strtolower(now()->setTimezone('Asia/Jakarta')->format('M d, H:i'));

            return response()->json([
                'success' => true,
                'message' => 'Request WHC berhasil diperbarui',
                'last_edited' => $formattedDate,
            ]);
        }

        $item = ItemOutstanding::find($id);
        if ($item) {
            $item->request_whc = $validated['request_whc'];
            $item->request_whc_edited_at = now();
            $item->save();

            $this->syncToMaster($item, [
                'request_whc' => $item->request_whc,
                'request_whc_edited_at' => $item->request_whc_edited_at
            ]);
        } else {
            $masterItem = ItemMaster::find($id);
            if (!$masterItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak ditemukan'
                ], 404);
            }

            $masterItem->request_whc = $validated['request_whc'];
            $masterItem->request_whc_edited_at = now();
            $masterItem->save();
        }

        $formattedDate = strtolower(now()->setTimezone('Asia/Jakarta')->format('M d, H:i'));

        return response()->json([
            'success' => true,
            'message' => 'Request WHC berhasil diperbarui',
            'last_edited' => $formattedDate,
        ]);
    }

    /**
     * Update request WHC Date for a request.
     */
    public function updateRequestWhcDate(Request $request, $id)
    {
        $this->abortIfGuest();
        
        if (!auth()->check() || (!auth()->user()->hasRole(['master', 'whc', 'admin', 'warehouse']) && !in_array(auth()->user()->username, ['master', 'whc', 'admin', 'warehouse']))) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya user master atau whc yang dapat mengisi Request WHC Date.'
            ], 403);
        }
        $validated = $request->validate([
            'request_whc_date' => 'nullable|date',
            'source' => 'nullable|string|max:50',
        ]);

        if (($validated['source'] ?? null) === 'item_minim') {
            $masterItem = ItemMaster::find($id);
            if (!$masterItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak ditemukan'
                ], 404);
            }

            $masterItem->request_whc_date = $validated['request_whc_date'];
            $masterItem->request_whc_date_edited_at = now();
            $masterItem->save();

            $formattedDate = strtolower(now()->setTimezone('Asia/Jakarta')->format('M d, H:i'));

            return response()->json([
                'success' => true,
                'message' => 'Request WHC Date berhasil diperbarui',
                'last_edited' => $formattedDate,
            ]);
        }

        $item = ItemOutstanding::find($id);
        if ($item) {
            $item->request_whc_date = $validated['request_whc_date'];
            $item->request_whc_date_edited_at = now();
            $item->save();

            $this->syncToMaster($item, [
                'request_whc_date' => $item->request_whc_date,
                'request_whc_date_edited_at' => $item->request_whc_date_edited_at
            ]);
        } else {
            $masterItem = ItemMaster::find($id);
            if (!$masterItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak ditemukan'
                ], 404);
            }

            $masterItem->request_whc_date = $validated['request_whc_date'];
            $masterItem->request_whc_date_edited_at = now();
            $masterItem->save();
        }

        $formattedDate = strtolower(now()->setTimezone('Asia/Jakarta')->format('M d, H:i'));

        return response()->json([
            'success' => true,
            'message' => 'Request WHC Date berhasil diperbarui',
            'last_edited' => $formattedDate,
        ]);
    }

    /**
     * Update follow up (qty, pengiriman tanggal, sudah follow) for a request.
     */
    public function updateFollowUp(Request $request, $id)
    {
        $this->abortIfGuest();
        $this->checkMasterAccess();
        $validated = $request->validate([
            'qty_akan_dikirim' => 'nullable|integer|min:0',
            'pengiriman_tanggal' => 'nullable|date',
            'selected_po_no' => 'nullable|string|max:255',
            'sudah_follow' => 'nullable|string|in:YES,NO,',
        ]);

        $item = ItemOutstanding::find($id);
        if ($item) {
            $item->qty_akan_dikirim = $validated['qty_akan_dikirim'];
            $item->pengiriman_tanggal = $validated['pengiriman_tanggal'];
            $item->selected_po_no = $validated['selected_po_no'];
            $item->sudah_follow = $validated['sudah_follow'] ?? 'YES';
            $item->sudah_follow_edited_at = now();
            $item->pengiriman_tanggal_edited_at = now();
            $item->save();

            $this->syncToMaster($item, [
                'qty_akan_dikirim' => $item->qty_akan_dikirim,
                'pengiriman_tanggal' => $item->pengiriman_tanggal,
                'selected_po_no' => $item->selected_po_no,
                'sudah_follow' => $item->sudah_follow,
                'sudah_follow_edited_at' => $item->sudah_follow_edited_at,
                'pengiriman_tanggal_edited_at' => $item->pengiriman_tanggal_edited_at
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Follow up berhasil diperbarui',
        ]);
    }

    private function syncToMaster($outstandingItem, $data)
    {
        if (!$outstandingItem || empty($outstandingItem->item_code)) {
            return;
        }

        ItemMaster::where('item_code', $outstandingItem->item_code)->update($data);
    }
}
