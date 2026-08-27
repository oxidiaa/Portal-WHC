<?php

namespace App\Http\Controllers\Mars;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ItemMaster;
use App\Models\DataPO;
use App\Models\FollowUpPO;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ItemMinimController extends Controller
{
    /**
     * Display a listing of items where ending_balance <= order_point AND outstanding > 0.
     */
    public function index()
    {
        $minimItems = ItemMaster::whereColumn('ending_balance', '<=', 'order_point')
            ->where('outstanding', '>', 0)
            ->orderBy('item_code')
            ->get();
        
        if ($minimItems->isNotEmpty()) {
            $itemCodes = $minimItems->pluck('item_code')->unique()->toArray();
            
            $poItems = DataPO::whereIn('item_code', $itemCodes)->get();
            $followUps = FollowUpPO::whereIn('item_master_id', $minimItems->pluck('id'))->get()->groupBy('item_master_id');
            
            $duplicatePoNos = DataPO::select('po_no', DB::raw('COUNT(*) as count'))
                ->whereNotNull('po_no')
                ->where('po_no', '!=', '')
                ->where('po_no', '!=', '-')
                ->groupBy('po_no')
                ->having('count', '>', 1)
                ->pluck('po_no')
                ->toArray();
            
            $poLookup = [];
            foreach ($poItems as $po) {
                $key = strtolower(trim($po->item_code));
                if (!isset($poLookup[$key])) {
                    $poLookup[$key] = [];
                }
                $poLookup[$key][] = $po;
            }
            
            foreach ($minimItems as $item) {
                $key = strtolower(trim($item->item_code));
                $matchingPOs = $poLookup[$key] ?? [];
                
                $poGroups = [];
                foreach ($matchingPOs as $po) {
                    $poNo = trim($po->po_no);
                    if (empty($poNo)) $poNo = '-';
                    
                    if (!isset($poGroups[$poNo])) {
                        $poGroups[$poNo] = [
                            'po_no' => $poNo,
                            'total_qty' => 0,
                            'supplier_name' => $po->supplier_name ?? '-',
                            'items' => []
                        ];
                    }
                    $poGroups[$poNo]['total_qty'] += (int)$po->scheduled_receipt_qty;
                    $poGroups[$poNo]['items'][] = $po;
                }
                
                $itemFollowUps = $followUps[$item->id] ?? collect();
                $followUpMap = $itemFollowUps->keyBy(function ($f) {
                    return trim($f->po_no) === '' ? '-' : trim($f->po_no);
                });
                $totalFollowedQty = 0;

                foreach ($poGroups as &$group) {
                    $poNoKey = $group['po_no'];
                    $group['followed'] = false;
                    $group['followed_status'] = 'NO';
                    $group['followed_qty'] = 0;
                    $group['followed_pengiriman_tanggal'] = null;

                    if ($followUpMap->has($poNoKey)) {
                        $fu = $followUpMap->get($poNoKey);
                        $group['followed_status'] = $fu->sudah_follow ?? 'NO';
                        $group['followed'] = ($group['followed_status'] === 'YES');
                        $group['followed_qty'] = (int) ($fu->qty_akan_dikirim ?? 0);
                        $group['followed_pengiriman_tanggal'] = $fu->pengiriman_tanggal
                            ? $fu->pengiriman_tanggal->format('Y-m-d')
                            : null;
                        $group['followed_edited_at'] = $fu->updated_at;
                        
                        $totalFollowedQty += $group['followed_qty'];
                    } else {
                        $group['followed_edited_at'] = null;
                    }
                }
                unset($group);

                $item->po_data = array_values($poGroups);
                $item->has_multiple_po = count($poGroups) > 1;
                
                $activePoGroup = null;
                if (!empty($poGroups)) {
                    $selectedPo = trim($item->selected_po_no ?? '');
                    if ($selectedPo && isset($poGroups[$selectedPo])) {
                        $activePoGroup = $poGroups[$selectedPo];
                    } else {
                        $activePoGroup = reset($poGroups);
                    }
                }

                if ($activePoGroup) {
                    $item->sudah_follow = $activePoGroup['followed_status'];
                    $item->qty_akan_dikirim = $activePoGroup['followed_qty'];
                    $item->pengiriman_tanggal = $activePoGroup['followed_pengiriman_tanggal'];
                    $item->sudah_follow_edited_at = $activePoGroup['followed_edited_at'] ?? null;
                    $item->pengiriman_tanggal_edited_at = ($activePoGroup['followed_pengiriman_tanggal'] ?? null) ? ($activePoGroup['followed_edited_at'] ?? null) : null;
                } else {
                    $item->sudah_follow = 'NO';
                    $item->qty_akan_dikirim = 0;
                    $item->pengiriman_tanggal = null;
                    $item->sudah_follow_edited_at = null;
                    $item->pengiriman_tanggal_edited_at = null;
                }

                $item->total_receipt_qty = array_sum(array_column($poGroups, 'total_qty'));
                $item->total_followed_qty = $totalFollowedQty;
            }
        }

        return view('mars.item_minim', compact('minimItems'));
    }

    /**
     * Update note for a minim item.
     */
    public function updateNote(Request $request, $id)
    {
        $this->abortIfGuest();
        
        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $item = ItemMaster::find($id);
        if ($item) {
            $item->note = $validated['note'] ?? null;
            $item->save();
            return response()->json(['success' => true, 'message' => 'Note berhasil diperbarui']);
        }

        return response()->json(['success' => false, 'message' => 'Item not found'], 404);
    }

    /**
     * Update a minim item.
     */
    public function update(Request $request, $id)
    {
        $this->abortIfGuest();
        
        $validated = $request->validate([
            'item_code' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'outstanding' => 'required|integer|min:0',
            'ending_balance' => 'required|integer|min:0',
            'maximal_stock' => 'required|integer|min:0',
            'order_point' => 'required|integer|min:0',
            'minimal_stock' => 'required|integer|min:0',
            'user' => 'nullable|string|max:255',
            'outstanding_pp' => 'nullable|string|max:255',
        ]);

        $item = ItemMaster::find($id);
        if (!$item) {
             return redirect()->route('mars.item_minim.index')->with('error', 'Item tidak ditemukan.');
        }

        $item->update($validated);

        return redirect()->route('mars.item_minim.index')->with('success', 'Item berhasil diperbarui.');
    }

    /**
     * Delete a minim item.
     */
    public function destroy($id)
    {
        $this->abortIfGuest();
        
        $item = ItemMaster::find($id);
        if (!$item) {
             return redirect()->route('mars.item_minim.index')->with('error', 'Item tidak ditemukan.');
        }
        
        $item->delete();

        return redirect()->route('mars.item_minim.index')->with('success', 'Item berhasil dihapus.');
    }

    /**
     * Update follow up for a minim item.
     */
    public function updateFollowUp(Request $request, $id)
    {
        $this->abortIfGuest();
        
        if (!auth()->check() || (!auth()->user()->hasRole(['purchasing', 'master', 'admin']) && !in_array(auth()->user()->username, ['purchasing', 'master', 'admin']))) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya user purchasing atau master yang dapat mengakses fitur ini.'
            ], 403);
        }

        $validated = $request->validate([
            'qty_akan_dikirim' => 'nullable|integer|min:0',
            'pengiriman_tanggal' => 'nullable|date',
            'selected_po_no' => 'nullable|string|max:255',
            'sudah_follow' => 'nullable|string|in:YES,NO,',
        ]);

        $item = ItemMaster::find($id);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan'], 404);
        }

        $selectedPo = $validated['selected_po_no'] ?? '';
        if (empty($selectedPo)) {
            return response()->json(['success' => false, 'message' => 'Silakan pilih NO PO terlebih dahulu'], 422);
        }

        FollowUpPO::updateOrCreate(
            [
                'item_master_id' => $item->id,
                'po_no' => $selectedPo
            ],
            [
                'qty_akan_dikirim' => $validated['qty_akan_dikirim'] ?? null,
                'pengiriman_tanggal' => $validated['pengiriman_tanggal'] ?? null,
                'sudah_follow' => $validated['sudah_follow'] ?? 'NO',
            ]
        );

        $totalFollowed = FollowUpPO::where('item_master_id', $item->id)->sum('qty_akan_dikirim');
        $item->qty_akan_dikirim = $totalFollowed;
        $item->sudah_follow = 'YES';
        $item->sudah_follow_edited_at = now();
        $item->pengiriman_tanggal = $validated['pengiriman_tanggal'] ?? null;
        $item->pengiriman_tanggal_edited_at = $validated['pengiriman_tanggal'] ? now() : null;
        $item->selected_po_no = $selectedPo;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Follow up berhasil diperbarui',
        ]);
    }

    /**
     * Export Item Minim data to Excel.
     */
    public function export(Request $request)
    {
        $minimItems = ItemMaster::whereColumn('ending_balance', '<=', 'order_point')
            ->where('outstanding', '>', 0)
            ->orderBy('item_code')
            ->get();
        
        if ($minimItems->isNotEmpty()) {
            $itemCodes = $minimItems->pluck('item_code')->unique()->toArray();
            $poItems = DataPO::whereIn('item_code', $itemCodes)->get();
            $followUps = FollowUpPO::whereIn('item_master_id', $minimItems->pluck('id'))->get()->groupBy('item_master_id');
            
            $poLookup = [];
            foreach ($poItems as $po) {
                $key = strtolower(trim($po->item_code));
                if (!isset($poLookup[$key])) {
                    $poLookup[$key] = [];
                }
                $poLookup[$key][] = $po;
            }
            
            foreach ($minimItems as $item) {
                $key = strtolower(trim($item->item_code));
                $matchingPOs = $poLookup[$key] ?? [];
                
                $poGroups = [];
                foreach ($matchingPOs as $po) {
                    $poNo = trim($po->po_no);
                    if (empty($poNo)) $poNo = '-';
                    
                    if (!isset($poGroups[$poNo])) {
                        $poGroups[$poNo] = [
                            'po_no' => $poNo,
                            'total_qty' => 0,
                            'supplier_name' => $po->supplier_name ?? '-',
                        ];
                    }
                    $poGroups[$poNo]['total_qty'] += (int)$po->scheduled_receipt_qty;
                }
                
                $itemFollowUps = $followUps[$item->id] ?? collect();
                $followUpMap = $itemFollowUps->keyBy(function ($f) {
                    return trim($f->po_no) === '' ? '-' : trim($f->po_no);
                });
                
                $activePoGroup = null;
                if (!empty($poGroups)) {
                    $selectedPo = trim($item->selected_po_no ?? '');
                    if ($selectedPo && isset($poGroups[$selectedPo])) {
                        $activePoGroup = $poGroups[$selectedPo];
                    } else {
                        $activePoGroup = reset($poGroups);
                    }
                }
                
                if ($activePoGroup) {
                    $poNoKey = $activePoGroup['po_no'];
                    if ($followUpMap->has($poNoKey)) {
                        $fu = $followUpMap->get($poNoKey);
                        $item->sudah_follow = $fu->sudah_follow ?? 'NO';
                        $item->qty_akan_dikirim = (int) ($fu->qty_akan_dikirim ?? 0);
                        $item->pengiriman_tanggal = $fu->pengiriman_tanggal
                            ? $fu->pengiriman_tanggal->format('Y-m-d')
                            : null;
                    } else {
                        $item->sudah_follow = 'NO';
                        $item->qty_akan_dikirim = 0;
                        $item->pengiriman_tanggal = null;
                    }
                    $item->po_no = $activePoGroup['po_no'];
                    $item->supplier_name = $activePoGroup['supplier_name'];
                    $item->total_receipt_qty = $activePoGroup['total_qty'];
                } else {
                    $item->sudah_follow = 'NO';
                    $item->qty_akan_dikirim = 0;
                    $item->pengiriman_tanggal = null;
                    $item->po_no = '-';
                    $item->supplier_name = '-';
                    $item->total_receipt_qty = 0;
                }
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Item Minim');

        $headers = [
            'No', 'Item Code', 'ITEM NAME', 'PO', 'Supplier Name',
            'OUTSTANDING', 'Request WHC', 'Request WHC Date', 'ENDING BALANCE',
            'MAX', 'ORDER POINT', 'MIN', 'User', 'Outstanding PP',
            'Sched. receipt qty.', 'QTY akan dikirim', 'SUDAH FOLLOW UP?',
            'PENGIRIMAN TANGGAL', 'Import', 'Note',
        ];

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $columnLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T'];
        $colIndex = 0;
        foreach ($headers as $header) {
            $colLetter = $columnLetters[$colIndex];
            $sheet->setCellValue($colLetter . '1', $header);
            $sheet->getStyle($colLetter . '1')->applyFromArray($headerStyle);
            $colIndex++;
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(25);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(15);
        $sheet->getColumnDimension('L')->setWidth(12);
        $sheet->getColumnDimension('M')->setWidth(15);
        $sheet->getColumnDimension('N')->setWidth(18);
        $sheet->getColumnDimension('O')->setWidth(20);
        $sheet->getColumnDimension('P')->setWidth(18);
        $sheet->getColumnDimension('Q')->setWidth(18);
        $sheet->getColumnDimension('R')->setWidth(20);
        $sheet->getColumnDimension('S')->setWidth(18);
        $sheet->getColumnDimension('T')->setWidth(30);

        $sheet->getRowDimension(1)->setRowHeight(25);

        $row = 2;
        $no = 1;
        foreach ($minimItems as $item) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $item->item_code ?? '-');
            $sheet->setCellValue('C' . $row, $item->item_name ?? '-');
            $sheet->setCellValue('D' . $row, $item->po_no ?? '-');
            $sheet->setCellValue('E' . $row, $item->supplier_name ?? '-');
            $sheet->setCellValue('F' . $row, $item->outstanding ?? 0);
            $sheet->setCellValue('G' . $row, $item->request_whc ?? 0);
            
            if ($item->request_whc_date) {
                $sheet->setCellValue('H' . $row, Carbon::parse($item->request_whc_date)->format('d/m/Y'));
            } else {
                $sheet->setCellValue('H' . $row, '-');
            }
            
            $sheet->setCellValue('I' . $row, $item->ending_balance ?? 0);
            $sheet->setCellValue('J' . $row, $item->maximal_stock ?? 0);
            $sheet->setCellValue('K' . $row, $item->order_point ?? 0);
            $sheet->setCellValue('L' . $row, $item->minimal_stock ?? 0);
            $sheet->setCellValue('M' . $row, $item->user ?? '-');
            $sheet->setCellValue('N' . $row, $item->outstanding_pp ?? '-');
            $sheet->setCellValue('O' . $row, $item->total_receipt_qty ?? 0);
            $sheet->setCellValue('P' . $row, $item->qty_akan_dikirim ?? 0);
            $sheet->setCellValue('Q' . $row, $item->sudah_follow ?? 'NO');
            
            if ($item->pengiriman_tanggal) {
                $sheet->setCellValue('R' . $row, Carbon::parse($item->pengiriman_tanggal)->format('d/m/Y'));
            } else {
                $sheet->setCellValue('R' . $row, '-');
            }
            
            if ($item->imported_at) {
                $sheet->setCellValue('S' . $row, Carbon::parse($item->imported_at)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB');
            } else {
                $sheet->setCellValue('S' . $row, '-');
            }
            
            $sheet->setCellValue('T' . $row, $item->note ?? '-');

            $sheet->getStyle('A' . $row . ':T' . $row)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('K' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('L' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('O' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('P' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('Q' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('R' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('S' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
            $no++;
        }

        $sheet->freezePane('A2');
        $filename = 'Item_Minim_' . date('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
