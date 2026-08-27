<?php

namespace App\Http\Controllers\Mars;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ItemMaster;
use App\Models\ItemOutstanding;
use App\Models\History;
use App\Models\DataPO;
use App\Models\KedatanganBarang;
use App\Models\FollowUpPO;

class KedatanganBarangController extends Controller
{
    /**
     * Display a listing of arrival items.
     */
    public function index()
    {
        if (!auth()->check() || (!auth()->user()->hasRole(['master', 'whc', 'admin', 'warehouse']) && !in_array(auth()->user()->username, ['master', 'whc', 'admin', 'warehouse']))) {
            abort(403, 'Akses ditolak. Anda tidak diizinkan mengakses halaman ini.');
        }

        $importSummary = Session::get('kedatangan_import_summary', ['items' => [], 'item_count' => 0]);

        $kedatanganItems = KedatanganBarang::orderBy('arrival_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('mars.kedatangan_barang', [
            'importSummary' => $importSummary,
            'kedatanganItems' => $kedatanganItems,
        ]);
    }

    private function getCellValue($value)
    {
        if ($value === null) {
            return '';
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        return trim((string) $value);
    }

    private function parseNumericValue($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $cleaned = preg_replace('/[^0-9.-]/', '', (string) $value);
        return $cleaned !== '' ? (float) $cleaned : 0;
    }

    /**
     * Import Excel file, update outstanding totals, and move arrived items to history.
     */
    public function importExcel(Request $request)
    {
        $this->abortIfGuest();
        
        $validated = $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls',
            'arrival_date' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $file = $request->file('excel_file');
            $arrivalDate = $validated['arrival_date'];

            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                return redirect()->route('mars.kedatangan_barang.index')
                    ->with('error', 'File Excel kosong atau tidak memiliki data.');
            }

            $headerRow = null;
            $columnIndices = [
                'item_code' => null,
                'item_name' => null,
                'supplier_name' => null,
                'scheduled_receipt_qty' => null,
                'po_no' => null,
            ];

            $maxHeaderSearch = min(5, count($rows));
            for ($i = 0; $i < $maxHeaderSearch; $i++) {
                $row = $rows[$i] ?? [];
                foreach ($row as $colIndex => $cellValue) {
                    if (empty($cellValue)) {
                        continue;
                    }
                    $cellLower = strtolower(trim((string)$cellValue));

                    if (preg_match('/item\s*cd|item\s*code|kode\s*item|code/i', $cellLower) && $columnIndices['item_code'] === null) {
                        $columnIndices['item_code'] = $colIndex;
                    }
                    if (preg_match('/item\s*name|nama\s*item|name/i', $cellLower) && $columnIndices['item_name'] === null) {
                        $columnIndices['item_name'] = $colIndex;
                    }
                    if (preg_match('/supplier\s*name|nama\s*supplier|supplier/i', $cellLower) && $columnIndices['supplier_name'] === null) {
                        $columnIndices['supplier_name'] = $colIndex;
                    }
                    if (preg_match('/sched\.?\s*receipt\s*qty|scheduled\s*receipt|receipt\s*qty|qty/i', $cellLower) && $columnIndices['scheduled_receipt_qty'] === null) {
                        $columnIndices['scheduled_receipt_qty'] = $colIndex;
                    }
                    if (preg_match('/po\s*no|purchase\s*order|po\s*number/i', $cellLower) && $columnIndices['po_no'] === null) {
                        $columnIndices['po_no'] = $colIndex;
                    }
                }

                if ($columnIndices['item_code'] !== null && $columnIndices['item_name'] !== null) {
                    $headerRow = $i;
                    break;
                }
            }

            if ($headerRow === null) {
                $headerRow = 0;
                $columnIndices['item_code'] = $columnIndices['item_code'] ?? 0;
                $columnIndices['item_name'] = $columnIndices['item_name'] ?? 1;
                $columnIndices['supplier_name'] = $columnIndices['supplier_name'] ?? 2;
                $columnIndices['scheduled_receipt_qty'] = $columnIndices['scheduled_receipt_qty'] ?? 3;
                $columnIndices['po_no'] = $columnIndices['po_no'] ?? 4;
            }

            $rows = array_slice($rows, $headerRow + 1);

            $updatedItems = 0;
            $movedToHistoryCount = 0;
            $historyDetails = [];
            $summaryItems = [];

            foreach ($rows as $row) {
                $nonEmptyValues = array_filter($row, function ($val) {
                    if ($val === null) return false;
                    $strVal = trim((string) $val);
                    return $strVal !== '' && $strVal !== '-';
                });

                if (empty($nonEmptyValues)) {
                    continue;
                }

                $itemCode = isset($row[$columnIndices['item_code']]) ? trim($this->getCellValue($row[$columnIndices['item_code']])) : '';
                $itemName = isset($row[$columnIndices['item_name']]) ? trim($this->getCellValue($row[$columnIndices['item_name']])) : '';
                $supplierName = isset($row[$columnIndices['supplier_name']]) ? trim($this->getCellValue($row[$columnIndices['supplier_name']])) : '';
                $scheduledReceiptQty = isset($row[$columnIndices['scheduled_receipt_qty']]) ? (int) $this->parseNumericValue($row[$columnIndices['scheduled_receipt_qty']]) : 0;
                $poNo = isset($row[$columnIndices['po_no']]) ? trim($this->getCellValue($row[$columnIndices['po_no']])) : '';

                if (empty($itemCode) || empty($itemName)) {
                    continue;
                }

                $arrivalQty = $scheduledReceiptQty;
                if ($arrivalQty <= 0) {
                    continue;
                }

                $poValidation = 'valid';
                if (!empty($poNo)) {
                    $poItems = DataPO::where('item_code', $itemCode)
                        ->where('item_name', $itemName)
                        ->where('po_no', $poNo)
                        ->get();

                    if ($poItems->isEmpty()) {
                        $poValidation = 'invalid';
                    } else {
                        $maxQty = $poItems->sum('scheduled_receipt_qty');
                        if ($arrivalQty > $maxQty) {
                            $poValidation = 'invalid';
                        }
                    }
                }

                $masterItem = ItemMaster::where('item_code', $itemCode)->first();
                $outstandingItems = ItemOutstanding::where('item_code', $itemCode)
                    ->orderBy('created_at', 'desc')
                    ->get();
                $existsInWarehouse = $outstandingItems->isNotEmpty();

                if (!$masterItem && !$existsInWarehouse) {
                    continue;
                }

                $pengirimanTanggal = null;
                if ($existsInWarehouse) {
                    foreach ($outstandingItems as $req) {
                        if ($req->pengiriman_tanggal) {
                            $pengirimanTanggal = $req->pengiriman_tanggal;
                            break;
                        }
                    }
                }
                if (!$pengirimanTanggal && $masterItem) {
                    $pengirimanTanggal = $masterItem->pengiriman_tanggal;
                }

                $totalDeducted = 0;
                $snapshotRequestWhc = 0;
                $snapshotRequestWhcDate = null;

                if ($masterItem) {
                    $snapshotRequestWhc = $masterItem->request_whc;
                    $snapshotRequestWhcDate = $masterItem->request_whc_date;
                    $currentMasterOutstanding = $masterItem->outstanding;
                    $currentEndingBalance = $masterItem->ending_balance;

                    if ($currentMasterOutstanding > 0 && $arrivalQty > 0) {
                        $masterItem->outstanding = max(0, $currentMasterOutstanding - $arrivalQty);
                        $updatedItems++;
                    }

                    if ($arrivalQty > 0) {
                        $masterItem->ending_balance = $currentEndingBalance + $arrivalQty;
                        $totalDeducted = $arrivalQty;
                    }

                    $masterItem->request_whc = 0;
                    $masterItem->request_whc_edited_at = null;
                    $masterItem->request_whc_date = null;
                    $masterItem->request_whc_date_edited_at = null;

                    $masterItem->sudah_follow = 'NO';
                    $masterItem->sudah_follow_edited_at = null;
                    $masterItem->qty_akan_dikirim = 0;
                    $masterItem->pengiriman_tanggal = null;
                    $masterItem->pengiriman_tanggal_edited_at = null;

                    if (!empty($poNo)) {
                        FollowUpPO::where('item_master_id', $masterItem->id)
                            ->where('po_no', $poNo)
                            ->update([
                                'sudah_follow' => 'NO',
                                'qty_akan_dikirim' => 0,
                                'pengiriman_tanggal' => null
                            ]);
                    }

                    $masterItem->save();

                    $targetWarehouseOutstanding = $masterItem->outstanding;
                    $targetEndingBalance = $masterItem->ending_balance;

                    if ($existsInWarehouse) {
                        $firstReq = $outstandingItems->first();
                        $firstReq->outstanding = $targetWarehouseOutstanding;
                        $firstReq->ending_balance = $targetEndingBalance;
                        $firstReq->request_whc = 0;
                        $firstReq->request_whc_edited_at = null;
                        $firstReq->request_whc_date = null;
                        $firstReq->request_whc_date_edited_at = null;
                        $firstReq->save();

                        foreach ($outstandingItems as $key => $req) {
                            if ($key === 0) continue;
                            $req->outstanding = 0;
                            $req->ending_balance = $targetEndingBalance;
                            $req->save();
                        }
                        $updatedItems++;
                    } else {
                        if ($targetWarehouseOutstanding > 0) {
                            ItemOutstanding::create([
                                'request_date' => now()->format('Y-m-d'),
                                'item_code' => $itemCode,
                                'item_name' => $itemName,
                                'user' => $masterItem->user,
                                'outstanding' => $targetWarehouseOutstanding,
                                'outstanding_pp' => $masterItem->outstanding_pp,
                                'ending_balance' => $targetEndingBalance,
                                'order_point' => $masterItem->order_point,
                                'minimal_stock' => $masterItem->minimal_stock,
                                'imported_at' => now(),
                            ]);
                            $updatedItems++;
                        }
                    }
                } else {
                    if ($existsInWarehouse) {
                        $remainingArrival = $arrivalQty;
                        foreach ($outstandingItems as $req) {
                            if ($remainingArrival <= 0) break;

                            $currentOutstanding = $req->outstanding;
                            $currentEndingBalance = $req->ending_balance;

                            if ($currentOutstanding <= 0) continue;

                            $deductAmount = min($remainingArrival, $currentOutstanding);

                            $req->outstanding = $currentOutstanding - $deductAmount;
                            $req->ending_balance = $currentEndingBalance + $deductAmount;
                            $req->save();

                            if ($deductAmount > 0) {
                                $updatedItems++;
                                $totalDeducted += $deductAmount;
                                $remainingArrival -= $deductAmount;
                            }
                        }
                    }
                }

                KedatanganBarang::create([
                    'item_code' => $itemCode,
                    'item_name' => $itemName,
                    'supplier_name' => $supplierName,
                    'scheduled_receipt_qty' => $scheduledReceiptQty,
                    'po_no' => $poNo,
                    'arrival_date' => $arrivalDate,
                    'arrived_qty' => $arrivalQty,
                    'pengiriman_tanggal' => $pengirimanTanggal,
                    'po_validation' => $poValidation,
                    'imported_at' => now(),
                    'request_whc' => $snapshotRequestWhc ?? 0,
                    'request_whc_date' => $snapshotRequestWhcDate,
                ]);

                if ($totalDeducted > 0) {
                    $history = History::create([
                        'arrival_date' => $arrivalDate,
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'supplier_name' => $supplierName,
                        'po_no' => $poNo,
                        'scheduled_receipt_qty' => $scheduledReceiptQty,
                        'jumlah_item_datang' => $totalDeducted,
                        'pengiriman_tanggal' => $pengirimanTanggal,
                        'request_whc' => $snapshotRequestWhc ?? 0,
                        'request_whc_date' => $snapshotRequestWhcDate,
                    ]);

                    $movedToHistoryCount++;
                    $poInfo = !empty($poNo) ? " (PO: {$poNo})" : '';
                    $historyDetails[] = "{$itemCode} - {$itemName}{$poInfo}: " . number_format($totalDeducted, 0, ',', '.');

                    $summaryItems[] = [
                        'history_id' => $history->id,
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'supplier_name' => $supplierName,
                        'po_no' => $poNo,
                        'scheduled_receipt_qty' => $scheduledReceiptQty,
                        'arrived_qty' => $totalDeducted,
                        'arrival_date' => $arrivalDate,
                        'po_validation' => $poValidation,
                    ];
                }
            }

            ItemOutstanding::where('outstanding', '<=', 0)->delete();

            DB::commit();

            if (!empty($summaryItems)) {
                Session::put('kedatangan_import_summary', [
                    'arrival_date' => $arrivalDate,
                    'item_count' => count($summaryItems),
                    'items' => $summaryItems,
                ]);
            }

            if ($updatedItems === 0) {
                return redirect()->route('mars.kedatangan_barang.index')
                    ->with('error', 'Tidak ada item outstanding yang cocok dengan data Excel.');
            }

            $message = "Import berhasil! Outstanding dari semua halaman diperbarui.";
            if ($movedToHistoryCount > 0) {
                $message .= " {$movedToHistoryCount} item dipindahkan ke History (tanggal kedatangan " . date('d/m/Y', strtotime($arrivalDate)) . ").";
            }
            if (!empty($historyDetails)) {
                $message .= " Detail: " . implode('; ', $historyDetails) . ".";
            }

            return redirect()->route('mars.kedatangan_barang.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Kedatangan Error: ' . $e->getMessage());
            return redirect()->route('mars.kedatangan_barang.index')
                ->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }
}
