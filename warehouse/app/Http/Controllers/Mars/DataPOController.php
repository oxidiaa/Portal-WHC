<?php

namespace App\Http\Controllers\Mars;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataPO;
use App\Models\ItemMaster;
use App\Models\ItemOutstanding;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataPOController extends Controller
{
    /**
     * Display a listing of PO data items.
     */
    public function index()
    {
        $poItems = DataPO::orderBy('item_code')->get();

        return view('mars.data_po', compact('poItems'));
    }

    /**
     * Import Excel file and process the PO data
     */
    public function importExcel(Request $request)
    {
        $this->abortIfGuest();
        
        try {
            $request->validate([
                'excel_file' => 'required|mimes:xlsx,xls',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('mars.data_po.index')
                ->with('error', 'File tidak valid. Pastikan file adalah Excel (.xlsx atau .xls).');
        }

        try {
            $file = $request->file('excel_file');
            
            if (!$file || !$file->isValid()) {
                return redirect()->route('mars.data_po.index')
                    ->with('error', 'File tidak valid atau gagal diupload.');
            }
            
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            if (empty($rows)) {
                return redirect()->route('mars.data_po.index')
                    ->with('error', 'File Excel kosong atau tidak memiliki data.');
            }

            // Find header row and column indices
            $headerRow = null;
            $columnIndices = [
                'item_code' => null,
                'item_name' => null,
                'supplier_name' => null,
                'scheduled_receipt_qty' => null,
                'po_no' => null,
            ];

            // Try to find header row (check first 5 rows)
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

            if ($columnIndices['item_code'] === null || $columnIndices['item_name'] === null) {
                return redirect()->route('mars.data_po.index')
                    ->with('error', 'Format Excel tidak valid. Pastikan file memiliki kolom "Item CD" dan "Item name".');
            }

            $now = Carbon::now('Asia/Jakarta');
            $imported = 0;
            $skipped = 0;
            $affectedItems = [];

            DB::beginTransaction();
            try {
                foreach ($rows as $row) {
                    if (empty(array_filter($row, fn($v) => $v !== null && trim((string)$v) !== '' && trim((string)$v) !== '-'))) {
                        continue;
                    }

                    $itemCode = isset($row[$columnIndices['item_code']]) ? trim($this->getCellValue($row[$columnIndices['item_code']])) : '';
                    $itemName = isset($row[$columnIndices['item_name']]) ? trim($this->getCellValue($row[$columnIndices['item_name']])) : '';
                    
                    if (empty($itemCode) || empty($itemName)) {
                        $skipped++;
                        continue;
                    }
                    
                    $supplierName = isset($row[$columnIndices['supplier_name']]) ? trim($this->getCellValue($row[$columnIndices['supplier_name']])) : '';
                    $scheduledReceiptQty = isset($row[$columnIndices['scheduled_receipt_qty']]) ? $this->parseNumericValue($row[$columnIndices['scheduled_receipt_qty']]) : 0;
                    $poNo = isset($row[$columnIndices['po_no']]) ? trim($this->getCellValue($row[$columnIndices['po_no']])) : '';

                    DataPO::create([
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'supplier_name' => $supplierName,
                        'scheduled_receipt_qty' => $scheduledReceiptQty,
                        'po_no' => $poNo,
                        'imported_at' => $now,
                    ]);
                    $imported++;
                    
                    $affectedItems[strtolower($itemCode . '|' . $itemName)] = [
                        'item_code' => $itemCode,
                        'item_name' => $itemName
                    ];
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
            $this->updateOutstandingForItems($affectedItems);

            if ($imported === 0) {
                 return redirect()->route('mars.data_po.index')
                    ->with('error', 'Tidak ada data yang berhasil diimport. Pastikan format valid.');
            }

            $message = "Import berhasil! {$imported} item PO baru.";
            if ($skipped > 0) $message .= " {$skipped} baris dilewati.";

            return redirect()->route('mars.data_po.index')->with('success', $message);

        } catch (\Exception $e) {
            Log::error('PO Import error: ' . $e->getMessage());
            return redirect()->route('mars.data_po.index')->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Delete all PO items
     */
    public function deleteAll()
    {
        $this->abortIfGuest();
        
        DataPO::truncate();
        ItemMaster::query()->update(['outstanding' => 0]);
        ItemOutstanding::truncate();
        
        return redirect()->route('mars.data_po.index')->with('success', 'Semua data PO berhasil dihapus.');
    }

    /**
     * Delete a single PO item
     */
    public function destroy($id)
    {
        $this->abortIfGuest();
        
        $poItem = DataPO::find($id);
        if (!$poItem) {
             return redirect()->route('mars.data_po.index')->with('error', 'Item tidak ditemukan.');
        }
        
        $itemCode = $poItem->item_code;
        $itemName = $poItem->item_name;
        
        $poItem->delete();
        
        $this->updateOutstandingForItems([
            strtolower($itemCode . '|' . $itemName) => ['item_code' => $itemCode, 'item_name' => $itemName]
        ]);

        return redirect()->route('mars.data_po.index')->with('success', 'Item berhasil dihapus.');
    }
    
    private function updateOutstandingForItems(array $items)
    {
        foreach ($items as $data) {
            $itemCode = $data['item_code'];
            $itemName = $data['item_name'];
            
            $totalOutstanding = DataPO::where('item_code', $itemCode)
                ->where('item_name', $itemName)
                ->sum('scheduled_receipt_qty');
                
            $masterItem = ItemMaster::where('item_code', $itemCode)
                ->where('item_name', $itemName)
                ->first();
                
            $calculatedOutstanding = $totalOutstanding;
            
            if ($masterItem) {
                $masterItem->outstanding = $calculatedOutstanding;
                $masterItem->save();
            }
            
            $currentRequestSum = ItemOutstanding::where('item_code', $itemCode)
                ->where('item_name', $itemName)
                ->sum('outstanding');
                
            $diff = $calculatedOutstanding - $currentRequestSum;
            
            if ($diff != 0) {
                 if ($diff > 0) {
                     $req = ItemOutstanding::where('item_code', $itemCode)
                        ->where('item_name', $itemName)
                        ->orderBy('request_date', 'desc')
                        ->first();
                        
                     if ($req) {
                         $req->outstanding += $diff;
                         $req->save();
                     } else if ($masterItem) {
                         ItemOutstanding::create([
                            'request_date' => now(),
                            'item_code' => $itemCode,
                            'item_name' => $itemName,
                            'user' => $masterItem->user,
                            'outstanding' => $diff,
                            'outstanding_pp' => $masterItem->outstanding_pp,
                            'ending_balance' => $masterItem->ending_balance,
                            'maximal_stock' => $masterItem->maximal_stock,
                            'order_point' => $masterItem->order_point,
                            'minimal_stock' => $masterItem->minimal_stock,
                            'imported_at' => now(),
                         ]);
                     }
                 } else {
                     $requests = ItemOutstanding::where('item_code', $itemCode)
                        ->where('item_name', $itemName)
                        ->orderBy('request_date', 'asc')
                        ->get();
                        
                     $remainingReduce = abs($diff);
                     
                     foreach ($requests as $req) {
                         if ($remainingReduce <= 0) break;
                         
                         if ($req->outstanding >= $remainingReduce) {
                             $req->outstanding -= $remainingReduce;
                             $remainingReduce = 0;
                         } else {
                             $remainingReduce -= $req->outstanding;
                             $req->outstanding = 0;
                         }
                         $req->save();
                     }
                 }
            }
        }
        
        ItemOutstanding::where('outstanding', '<=', 0)->delete();
    }

    private function getCellValue($value)
    {
        if ($value === null) return '';
        if ($value === '') return '';
        if (is_string($value)) return trim($value);
        if (is_numeric($value)) return (string) $value;
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_object($value)) {
            if (method_exists($value, '__toString')) return trim((string) $value);
            return '';
        }
        if (is_array($value)) return $this->getCellValue($value[0] ?? '');
        return '';
    }

    private function parseNumericValue($value)
    {
        $cellValue = $this->getCellValue($value);
        if (empty($cellValue) || $cellValue === '-' || trim($cellValue) === '-') return 0;
        if (is_numeric($cellValue)) return (int) $cellValue;
        $cleaned = preg_replace('/[^\d.-]/', '', $cellValue);
        $cleaned = preg_replace('/\.(?=.*\.)/', '', $cleaned);
        if (is_numeric($cleaned)) return (int) $cleaned;
        return 0;
    }
}
