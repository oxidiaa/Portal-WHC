<?php

namespace App\Services;

class MonthlyRecapPdfGenerator
{
    /**
     * Generate an A4 Landscape PDF binary string for the Monthly Asset Recap report.
     *
     * @param  array  $rows        Array of associative arrays with keys:
     *                             no_form, date_formatted, kode_barang, nama_barang,
     *                             harga_formatted, estimasi_usia_pakai, department
     * @param  array  $meta        Keys: period_label, total_registration, total_unregistration, total_processed
     * @return string
     */
    public function generate(array $rows, array $meta): string
    {
        // A4 Landscape in points: 841.89 x 595.28
        $pageWidth = 841.89;
        $pageHeight = 595.28;
        $marginLeft = 24.0;
        $marginRight = 24.0;
        $marginTop = 24.0;
        $marginBottom = 28.0;
        $usableWidth = $pageWidth - $marginLeft - $marginRight; // 793.89 pt

        // Column definitions (total = 793.89 pt)
        $columns = [
            ['label' => 'No Form',              'width' => 112.0, 'align' => 'L'],
            ['label' => 'Date',                 'width' => 58.0,  'align' => 'C'],
            ['label' => 'Kode Barang',          'width' => 86.0,  'align' => 'L'],
            ['label' => 'Nama Barang',          'width' => 130.0, 'align' => 'L'],
            ['label' => 'Harga',                'width' => 76.0,  'align' => 'R'],
            ['label' => 'Estimasi Usia Pakai',  'width' => 68.0,  'align' => 'C'],
            ['label' => 'Department',           'width' => 84.0,  'align' => 'L'],
            ['label' => 'Location',             'width' => 112.0, 'align' => 'L'], // Wide blank space for handwriting
            ['label' => 'Qty Saat Ini',         'width' => 67.89, 'align' => 'C'], // Wide blank space for handwriting
        ];

        $headerBlockHeight = 86.0;
        $continuationHeaderHeight = 38.0;
        $tableHeaderHeight = 24.0;
        $rowHeight = 24.0; // Generous row height for handwriting

        // Paginate rows
        $pages = [];
        $currentRows = [];
        $isFirstPage = true;

        $availableForRows = function (bool $first) use ($pageHeight, $marginTop, $marginBottom, $headerBlockHeight, $continuationHeaderHeight, $tableHeaderHeight) {
            $hdr = $first ? $headerBlockHeight : $continuationHeaderHeight;
            return $pageHeight - $marginTop - $marginBottom - $hdr - $tableHeaderHeight;
        };

        $maxRowsFirst = max(1, (int) floor($availableForRows(true) / $rowHeight));
        $maxRowsNext = max(1, (int) floor($availableForRows(false) / $rowHeight));

        if (empty($rows)) {
            $pages[] = [];
        } else {
            $index = 0;
            $total = count($rows);
            while ($index < $total) {
                $limit = $isFirstPage ? $maxRowsFirst : $maxRowsNext;
                $pages[] = array_slice($rows, $index, $limit);
                $index += $limit;
                $isFirstPage = false;
            }
        }

        $totalPages = count($pages);
        $pageStreams = [];

        foreach ($pages as $pageIdx => $pageRows) {
            $pageNum = $pageIdx + 1;
            $stream = '';
            $y = $pageHeight - $marginTop;

            if ($pageNum === 1) {
                // Full Report Header
                $stream .= $this->pdfText($marginLeft, $y - 10, 'SATURNUS', 13, true);
                $y -= 22;
                $stream .= $this->pdfText($marginLeft, $y, 'SMART ASSET TRACKING, UNREGISTRATION & REGISTRATION NETWORK UTILITY SYSTEM', 8, false);
                $y -= 16;
                $stream .= $this->pdfText($marginLeft, $y, 'MONTHLY ASSET RECAP', 12, true);
                $y -= 15;
                $stream .= $this->pdfText($marginLeft, $y, 'Reporting Period: ' . $this->cleanText($meta['period_label'] ?? '-'), 9.5, true);

                // Summary box on right side of header
                $summaryText = sprintf(
                    'Total Registration: %d   |   Total Unregistration: %d   |   Total Processed Items: %d',
                    (int) ($meta['total_registration'] ?? 0),
                    (int) ($meta['total_unregistration'] ?? 0),
                    (int) ($meta['total_processed'] ?? 0)
                );
                $stream .= $this->pdfText($pageWidth - $marginRight - $this->estimateTextWidth($summaryText, 8.5), $y, $summaryText, 8.5, true);
                $y -= 12;

                // Divider line
                $stream .= sprintf("0.8 w 0 0 0 RG %.2f %.2f m %.2f %.2f l S\n", $marginLeft, $y, $pageWidth - $marginRight, $y);
                $y -= 12;
            } else {
                // Continuation Header
                $stream .= $this->pdfText($marginLeft, $y - 10, 'SATURNUS — MONTHLY ASSET RECAP (' . $this->cleanText($meta['period_label'] ?? '-') . ')', 10, true);
                $y -= 24;
                $stream .= sprintf("0.6 w 0 0 0 RG %.2f %.2f m %.2f %.2f l S\n", $marginLeft, $y, $pageWidth - $marginRight, $y);
                $y -= 14;
            }

            // Draw Table Header
            $stream .= $this->drawTableHeader($marginLeft, $y, $tableHeaderHeight, $columns);
            $y -= $tableHeaderHeight;

            if (empty($pageRows)) {
                // Empty state row
                $stream .= sprintf("0.5 w 0 0 0 RG %.2f %.2f %.2f %.2f re S\n", $marginLeft, $y - $rowHeight, $usableWidth, $rowHeight);
                $emptyMsg = 'Tidak ada transaksi Registration maupun Unregistration yang selesai diproses pada periode ini.';
                $tx = $marginLeft + ($usableWidth - $this->estimateTextWidth($emptyMsg, 8.5)) / 2;
                $stream .= $this->pdfText($tx, $y - 15, $emptyMsg, 8.5, false);
            } else {
                foreach ($pageRows as $row) {
                    $values = [
                        $this->cleanText($row['no_form'] ?? '-'),
                        $this->cleanText($row['date_formatted'] ?? '-'),
                        $this->cleanText($row['kode_barang'] ?? '-'),
                        $this->cleanText($row['nama_barang'] ?? '-'),
                        $this->cleanText($row['harga_formatted'] ?? '-'),
                        $this->cleanText($row['estimasi_usia_pakai'] ?? '-'),
                        $this->cleanText($row['department'] ?? '-'),
                        '', // Location MUST remain blank for manual handwriting
                        '', // Qty Saat Ini MUST remain blank for manual handwriting
                    ];

                    $stream .= $this->drawTableRow($marginLeft, $y, $rowHeight, $columns, $values);
                    $y -= $rowHeight;
                }
            }

            // Footer: Page X of Y + Printed timestamp
            $footerY = 14.0;
            $stream .= $this->pdfText($marginLeft, $footerY, 'SATURNUS Monthly Asset Recap — Manual Location & Qty Saat Ini diisi setelah dicetak', 7.5, false);
            $pageText = "Page {$pageNum} of {$totalPages}";
            $stream .= $this->pdfText($pageWidth - $marginRight - $this->estimateTextWidth($pageText, 8), $footerY, $pageText, 8, true);

            $pageStreams[] = $stream;
        }

        return $this->buildPdfDocument($pageStreams, $pageWidth, $pageHeight);
    }

    private function drawTableHeader(float $startX, float $topY, float $height, array $columns): string
    {
        $out = '';
        $x = $startX;
        $bottomY = $topY - $height;

        foreach ($columns as $col) {
            // Light gray header fill + black border
            $out .= sprintf("0.92 0.94 0.96 rg %.2f %.2f %.2f %.2f re f\n", $x, $bottomY, $col['width'], $height);
            $out .= sprintf("0.6 w 0 0 0 RG %.2f %.2f %.2f %.2f re S\n", $x, $bottomY, $col['width'], $height);

            $label = $this->fitText($col['label'], $col['width'] - 6, 7.5);
            $textWidth = $this->estimateTextWidth($label, 7.5);
            $tx = $x + max(3.0, ($col['width'] - $textWidth) / 2);
            $ty = $bottomY + ($height - 7.5) / 2 + 1.5;

            $out .= $this->pdfText($tx, $ty, $label, 7.5, true);
            $x += $col['width'];
        }

        return $out;
    }

    private function drawTableRow(float $startX, float $topY, float $height, array $columns, array $values): string
    {
        $out = '';
        $x = $startX;
        $bottomY = $topY - $height;
        $fontSize = 7.5;

        foreach ($columns as $i => $col) {
            $out .= sprintf("0.5 w 0 0 0 RG %.2f %.2f %.2f %.2f re S\n", $x, $bottomY, $col['width'], $height);

            $val = $values[$i] ?? '';
            if ($val !== '') {
                $fitted = $this->fitText($val, $col['width'] - 6, $fontSize);
                $textWidth = $this->estimateTextWidth($fitted, $fontSize);

                if ($col['align'] === 'C') {
                    $tx = $x + max(3.0, ($col['width'] - $textWidth) / 2);
                } elseif ($col['align'] === 'R') {
                    $tx = $x + max(3.0, $col['width'] - $textWidth - 4.0);
                } else {
                    $tx = $x + 4.0;
                }

                $ty = $bottomY + ($height - $fontSize) / 2 + 1.5;
                $out .= $this->pdfText($tx, $ty, $fitted, $fontSize, false);
            }

            $x += $col['width'];
        }

        return $out;
    }

    private function pdfText(float $x, float $y, string $text, float $fontSize, bool $bold): string
    {
        $font = $bold ? '/F2' : '/F1';
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        return sprintf("BT 0 0 0 rg %s %.2f Tf %.2f %.2f Td (%s) Tj ET\n", $font, $fontSize, $x, $y, $escaped);
    }

    private function cleanText(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text));
        // Convert UTF-8 to ISO-8859-1 / ASCII safe for standard PDF Type1 Helvetica
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
        return $converted !== false ? $converted : preg_replace('/[^\x20-\x7E]/', '', $text);
    }

    private function estimateTextWidth(string $text, float $fontSize): float
    {
        // Average Helvetica glyph width ~0.50 * fontSize
        return strlen($text) * ($fontSize * 0.50);
    }

    private function fitText(string $text, float $maxWidth, float $fontSize): string
    {
        if ($this->estimateTextWidth($text, $fontSize) <= $maxWidth) {
            return $text;
        }
        $maxChars = max(1, (int) floor($maxWidth / ($fontSize * 0.50)) - 2);
        return substr($text, 0, $maxChars) . '..';
    }

    private function buildPdfDocument(array $pageStreams, float $width, float $height): string
    {
        $objects = [];

        // Object 1: Catalog
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        // Object 2: Pages (placeholder, filled after page objects)
        $objects[2] = "";
        // Object 3: Regular Font (Helvetica)
        $objects[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        // Object 4: Bold Font (Helvetica-Bold)
        $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        $nextObj = 5;
        $pageObjIds = [];

        foreach ($pageStreams as $stream) {
            $contentObjId = $nextObj++;
            $pageObjId = $nextObj++;

            $objects[$contentObjId] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
            $objects[$pageObjId] = sprintf(
                "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>",
                $width,
                $height,
                $contentObjId
            );
            $pageObjIds[] = $pageObjId;
        }

        $kids = implode(' ', array_map(fn ($id) => "{$id} 0 R", $pageObjIds));
        $objects[2] = "<< /Type /Pages /Kids [ {$kids} ] /Count " . count($pageObjIds) . " >>";

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
