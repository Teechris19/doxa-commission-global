<?php

namespace App\Services;

use App\Models\Report;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Storage;

class ReportPdfGenerator
{
    public function generate(Report $report): string
    {
        $pdf = new Fpdi();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell(0, 10, $this->safeText($report->title), 0, 1);

        $pdf->SetFont('Helvetica', '', 11);
        $pdf->Cell(0, 6, 'Date: ' . $report->report_date?->format('M d, Y') . ' ' . $report->report_date?->format('h:i A'), 0, 1);
        $pdf->Cell(0, 6, 'Level: ' . ucfirst($report->level), 0, 1);
        $pdf->Ln(4);

        if (!empty($report->report_data) && is_array($report->report_data)) {
            $this->renderTable($pdf, $report->report_data);
        } else {
            $text = $this->stripHtml((string) $report->report);
            $pdf->MultiCell(0, 6, $this->safeText($text));
        }

        $path = 'reports/generated/report-' . $report->id . '.pdf';
        Storage::disk('public')->put($path, $pdf->Output('S'));

        return $path;
    }

    private function renderTable(Fpdi $pdf, array $data): void
    {
        $headers = $data['headers'] ?? [];
        $rows = $data['rows'] ?? [];

        if (!empty($headers)) {
            $pdf->SetFont('Helvetica', 'B', 10);
            foreach ($headers as $header) {
                $pdf->Cell(40, 7, $this->safeText((string) $header), 1);
            }
            $pdf->Ln();
        }

        $pdf->SetFont('Helvetica', '', 10);
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                $pdf->Cell(40, 6, $this->safeText((string) $cell), 1);
            }
            $pdf->Ln();
        }
    }

    private function stripHtml(string $html): string
    {
        $text = trim(strip_tags($html));
        return preg_replace('/\s+/', ' ', $text) ?: '';
    }

    private function safeText(string $text): string
    {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;
    }
}
