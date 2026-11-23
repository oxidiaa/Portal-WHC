<?php

namespace App\Http\Controllers\Saturnus;

use App\Http\Controllers\Controller;
use App\Models\FormApproval;
use App\Models\FormItem;
use App\Models\UnregistrasiApproval;
use App\Models\UnregistrasiItem;
use App\Services\MonthlyRecapPdfGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MonthlyRecapController extends Controller
{
    private const MONTH_NAMES = [
        1  => 'January',
        2  => 'February',
        3  => 'March',
        4  => 'April',
        5  => 'May',
        6  => 'June',
        7  => 'July',
        8  => 'August',
        9  => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    /**
     * Display the Monthly Asset Recap page.
     */
    public function index(Request $request)
    {
        $recap = $this->buildRecapData($request);

        $perPage = 25;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $filtered = $recap['filteredRows'];

        $paginatedItems = new LengthAwarePaginator(
            $filtered->forPage($currentPage, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $currentPage,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('saturnus.monthly_recap', [
            'items'               => $paginatedItems,
            'printRows'           => $filtered,
            'allPeriodRowsCount'  => $recap['allRows']->count(),
            'month'               => $recap['month'],
            'year'                => $recap['year'],
            'monthNames'          => self::MONTH_NAMES,
            'years'               => $recap['years'],
            'periodLabel'         => $recap['periodLabel'],
            'search'              => $recap['search'],
            'type'                => $recap['type'],
            'totalProcessed'      => $recap['totalProcessed'],
            'totalRegistration'   => $recap['totalRegistration'],
            'totalUnregistration' => $recap['totalUnregistration'],
            'dailyActivity'       => $recap['dailyActivity'],
        ]);
    }

    /**
     * Export Monthly Asset Recap to Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        $recap = $this->buildRecapData($request);
        $rows = $recap['filteredRows'];
        $periodLabel = $recap['periodLabel'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Monthly Asset Recap');

        // Report Title Header
        $sheet->setCellValue('A1', 'SATURNUS — MONTHLY ASSET RECAP');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '0F172A']],
        ]);

        $sheet->setCellValue('A2', 'SMART ASSET TRACKING, UNREGISTRATION & REGISTRATION NETWORK UTILITY SYSTEM');
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '475569']],
        ]);

        $sheet->setCellValue('A3', 'Reporting Period: ' . $periodLabel);
        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '0F172A']],
        ]);

        $summaryInfo = sprintf(
            'Total Registration: %d   |   Total Unregistration: %d   |   Total Processed Items: %d',
            $recap['totalRegistration'],
            $recap['totalUnregistration'],
            $recap['totalProcessed']
        );
        $sheet->setCellValue('F3', $summaryInfo);
        $sheet->mergeCells('F3:I3');
        $sheet->getStyle('F3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9.5, 'color' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        // Table Header (Row 5) - Exact 9 columns
        $headers = [
            'No Form',
            'Date',
            'Kode Barang',
            'Nama Barang',
            'Harga',
            'Estimasi Usia Pakai',
            'Department',
            'Location',
            'Qty Saat Ini',
        ];

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        $colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        foreach ($headers as $idx => $header) {
            $col = $colLetters[$idx];
            $sheet->setCellValue($col . '5', $header);
            $sheet->getStyle($col . '5')->applyFromArray($headerStyle);
        }
        $sheet->getRowDimension(5)->setRowHeight(24);

        $rowNum = 6;
        $cellBorder = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        foreach ($rows as $item) {
            $txLabel = $item['transaction_label'] ?? ($item['transaction_type'] === 'unregistration' ? 'Unregistrasi' : 'Registrasi');
            $sheet->setCellValue('A' . $rowNum, $item['no_form'] . "\n(" . $txLabel . ')');
            $sheet->setCellValue('B' . $rowNum, $item['date_formatted']);
            $sheet->setCellValue('C' . $rowNum, $item['kode_barang']);
            $sheet->setCellValue('D' . $rowNum, $item['nama_barang']);
            $sheet->setCellValue('E' . $rowNum, $item['harga_formatted']);
            $sheet->setCellValue('F' . $rowNum, $item['estimasi_usia_pakai']);
            $sheet->setCellValue('G' . $rowNum, $item['department']);
            $sheet->setCellValue('H' . $rowNum, ''); // Location MUST remain blank
            $sheet->setCellValue('I' . $rowNum, ''); // Qty Saat Ini MUST remain blank

            $sheet->getStyle("A{$rowNum}:I{$rowNum}")->applyFromArray($cellBorder);
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("B{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($rowNum)->setRowHeight(28);

            $rowNum++;
        }

        // Column widths (wide enough for Location & Qty Saat Ini handwriting)
        $widths = [
            'A' => 26,
            'B' => 14,
            'C' => 22,
            'D' => 32,
            'E' => 18,
            'F' => 20,
            'G' => 22,
            'H' => 26, // Location
            'I' => 18, // Qty Saat Ini
        ];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Landscape A4 page setup for Excel printing too
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

        $safePeriod = str_replace(' ', '_', $periodLabel);
        $fileName = "Monthly_Asset_Recap_{$safePeriod}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Monthly Asset Recap to PDF (A4 Landscape).
     */
    public function exportPdf(Request $request, MonthlyRecapPdfGenerator $pdfGenerator)
    {
        $recap = $this->buildRecapData($request);
        $rows = $recap['filteredRows']->values()->all();

        $pdfBinary = $pdfGenerator->generate($rows, [
            'period_label'         => $recap['periodLabel'],
            'total_registration'   => $recap['totalRegistration'],
            'total_unregistration' => $recap['totalUnregistration'],
            'total_processed'      => $recap['totalProcessed'],
        ]);

        $safePeriod = str_replace(' ', '_', $recap['periodLabel']);
        $fileName = "Monthly_Asset_Recap_{$safePeriod}.pdf";

        return response($pdfBinary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Query and assemble actual Registration & Unregistration data for the selected month and year.
     */
    private function buildRecapData(Request $request): array
    {
        $month = (int) $request->input('month', date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }

        $year = (int) $request->input('year', date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $search = trim((string) $request->input('search', ''));
        $type = strtolower(trim((string) $request->input('type', 'all')));
        if (!in_array($type, ['all', 'registration', 'unregistration'], true)) {
            $type = 'all';
        }

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $currentUser = auth()->user();
        $itemController = app(ItemController::class);

        // 1. Completed Registration Approvals in selected month/year
        $completedRegApprovals = FormApproval::where('status', 'Item Telah didaftarkan')
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('warehouse_signed_at', [$startOfMonth, $endOfMonth])
                    ->orWhere(function ($q2) use ($startOfMonth, $endOfMonth) {
                        $q2->whereNull('warehouse_signed_at')
                            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth]);
                    });
            })
            ->get()
            ->keyBy('form_number');

        $regRows = collect();
        if ($completedRegApprovals->isNotEmpty()) {
            $regItems = FormItem::whereIn('form_number', $completedRegApprovals->keys()->all())
                ->get();

            foreach ($regItems as $item) {
                $approval = $completedRegApprovals->get($item->form_number);
                if (!$approval) {
                    continue;
                }

                $dept = $approval->requestor_dept ?? $item->created_by_dept ?? '-';
                if ($currentUser && !$itemController->isDepartmentAllowed($currentUser, $dept)) {
                    continue;
                }

                $txDate = $approval->warehouse_signed_at
                    ? Carbon::parse($approval->warehouse_signed_at)
                    : ($approval->updated_at ? Carbon::parse($approval->updated_at) : Carbon::parse($item->created_at));

                $hargaVal = $item->harga !== null && (float) $item->harga > 0 ? (float) $item->harga : null;
                $usiaVal = trim((string) ($item->estimasi_usia_pakai ?? ''));

                $regRows->push([
                    'id'                  => 'reg_' . $item->id,
                    'transaction_type'    => 'registration',
                    'transaction_label'   => 'Registrasi',
                    'no_form'             => trim((string) ($item->form_number ?? '')) ?: '-',
                    'date_obj'            => $txDate,
                    'date_formatted'      => $txDate->format('d/m/Y'),
                    'day'                 => (int) $txDate->format('j'),
                    'kode_barang'         => trim((string) ($item->kode_barang ?? '')) ?: '-',
                    'nama_barang'         => trim((string) ($item->nama_barang ?? '')) ?: '-',
                    'harga'               => $hargaVal,
                    'harga_formatted'     => $hargaVal !== null ? 'Rp ' . number_format($hargaVal, 0, ',', '.') : '-',
                    'estimasi_usia_pakai' => $usiaVal !== '' ? $usiaVal : '-',
                    'department'          => trim((string) $dept) ?: '-',
                    'location'            => '',
                    'qty_saat_ini'        => '',
                ]);
            }
        }

        // 2. Completed Unregistration Approvals in selected month/year
        $completedUnregApprovals = UnregistrasiApproval::whereIn('status', [
                'Telah Discontinue oleh Warehouse Consumable',
                'Telah Discontinue',
            ])
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('warehouse_signed_at', [$startOfMonth, $endOfMonth])
                    ->orWhere(function ($q2) use ($startOfMonth, $endOfMonth) {
                        $q2->whereNull('warehouse_signed_at')
                            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth]);
                    });
            })
            ->get()
            ->keyBy('form_number');

        $unregRows = collect();
        if ($completedUnregApprovals->isNotEmpty()) {
            $unregItems = UnregistrasiItem::whereIn('form_number', $completedUnregApprovals->keys()->all())
                ->get();

            // Lookup existing harga & estimasi_usia_pakai from form_items by kode_barang if available
            $kodeList = $unregItems->pluck('kode_barang')
                ->map(fn ($k) => trim((string) $k))
                ->filter(fn ($k) => $k !== '' && $k !== '-')
                ->unique()
                ->values()
                ->all();

            $assetSpecsByKode = [];
            if (!empty($kodeList)) {
                $matchedFormItems = FormItem::whereIn('kode_barang', $kodeList)
                    ->orderByDesc('id')
                    ->get();
                foreach ($matchedFormItems as $fi) {
                    $k = trim((string) $fi->kode_barang);
                    if (!isset($assetSpecsByKode[$k])) {
                        $assetSpecsByKode[$k] = [
                            'harga'               => $fi->harga !== null && (float) $fi->harga > 0 ? (float) $fi->harga : null,
                            'estimasi_usia_pakai' => trim((string) ($fi->estimasi_usia_pakai ?? '')),
                        ];
                    }
                }
            }

            foreach ($unregItems as $item) {
                $approval = $completedUnregApprovals->get($item->form_number);
                if (!$approval) {
                    continue;
                }

                $dept = $approval->requestor_dept ?? $item->created_by_dept ?? '-';
                if ($currentUser && !$itemController->isDepartmentAllowed($currentUser, $dept)) {
                    continue;
                }

                $txDate = $approval->warehouse_signed_at
                    ? Carbon::parse($approval->warehouse_signed_at)
                    : ($approval->updated_at ? Carbon::parse($approval->updated_at) : Carbon::parse($item->created_at));

                $kode = trim((string) ($item->kode_barang ?? ''));
                $spec = $kode !== '' ? ($assetSpecsByKode[$kode] ?? null) : null;
                $hargaVal = $spec['harga'] ?? null;
                $usiaVal = $spec['estimasi_usia_pakai'] ?? '';

                $unregRows->push([
                    'id'                  => 'unreg_' . $item->id,
                    'transaction_type'    => 'unregistration',
                    'transaction_label'   => 'Unregistrasi',
                    'no_form'             => trim((string) ($item->form_number ?? '')) ?: '-',
                    'date_obj'            => $txDate,
                    'date_formatted'      => $txDate->format('d/m/Y'),
                    'day'                 => (int) $txDate->format('j'),
                    'kode_barang'         => $kode !== '' ? $kode : '-',
                    'nama_barang'         => trim((string) ($item->nama_barang ?? '')) ?: '-',
                    'harga'               => $hargaVal,
                    'harga_formatted'     => $hargaVal !== null ? 'Rp ' . number_format($hargaVal, 0, ',', '.') : '-',
                    'estimasi_usia_pakai' => $usiaVal !== '' ? $usiaVal : '-',
                    'department'          => trim((string) $dept) ?: '-',
                    'location'            => '',
                    'qty_saat_ini'        => '',
                ]);
            }
        }

        // 3. Merge and sort newest transaction date first
        $allRows = $regRows->concat($unregRows)
            ->sortByDesc(fn ($row) => $row['date_obj']->timestamp)
            ->values();

        $totalRegistration = $regRows->count();
        $totalUnregistration = $unregRows->count();
        $totalProcessed = $allRows->count();

        // 4. Daily activity series for the selected month
        $daysInMonth = $startOfMonth->daysInMonth;
        $regByDay = array_fill(1, $daysInMonth, 0);
        $unregByDay = array_fill(1, $daysInMonth, 0);

        foreach ($regRows as $r) {
            $d = $r['day'];
            if (isset($regByDay[$d])) {
                $regByDay[$d]++;
            }
        }
        foreach ($unregRows as $u) {
            $d = $u['day'];
            if (isset($unregByDay[$d])) {
                $unregByDay[$d]++;
            }
        }

        $dailyActivity = [
            'labels'         => range(1, $daysInMonth),
            'registrations'  => array_values($regByDay),
            'unregistrations' => array_values($unregByDay),
        ];

        // 5. Apply table filters (Transaction Type + Search)
        $filteredRows = $allRows;
        if ($type !== 'all') {
            $filteredRows = $filteredRows->where('transaction_type', $type);
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $filteredRows = $filteredRows->filter(function ($row) use ($needle) {
                return str_contains(mb_strtolower($row['no_form']), $needle)
                    || str_contains(mb_strtolower($row['kode_barang']), $needle)
                    || str_contains(mb_strtolower($row['nama_barang']), $needle);
            });
        }

        $filteredRows = $filteredRows->values();

        // Available years dropdown (range covering existing data & current year)
        $currentYear = (int) date('Y');
        $years = range(max(2024, $currentYear - 3), $currentYear + 1);
        if (!in_array($year, $years, true)) {
            $years[] = $year;
            sort($years);
        }

        $periodLabel = (self::MONTH_NAMES[$month] ?? date('F')) . ' ' . $year;

        return [
            'allRows'             => $allRows,
            'filteredRows'        => $filteredRows,
            'month'               => $month,
            'year'                => $year,
            'years'               => $years,
            'periodLabel'         => $periodLabel,
            'search'              => $search,
            'type'                => $type,
            'totalProcessed'      => $totalProcessed,
            'totalRegistration'   => $totalRegistration,
            'totalUnregistration' => $totalUnregistration,
            'dailyActivity'       => $dailyActivity,
        ];
    }
}
