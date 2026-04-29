<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use setasign\Fpdi\Fpdi;

class CertificateController extends Controller
{
    public function showForm()
    {
        return view('certificate.form');
    }

    public function generateCertificate(Request $request)
    {
        $name = $request->input('name');
        $requestedDate = $request->input('date');

        if (!$name || !$requestedDate) {
            abort(400, 'Name and date required');
        }

        $user = Auth::user();
        $chapterId = $user->chapter_id;
        $academy = \App\Models\BelieversAcademy::where('chapter_id', $chapterId)->first();

        if (!$academy || !$academy->certificate_template) {
            abort(404, 'Certificate template not found for your chapter');
        }

        $templatePath = Storage::disk('public')->path($academy->certificate_template);

        if (!file_exists($templatePath)) {
            abort(404, 'Certificate template file not found');
        }

        // Create new PDF
        $pdf = new Fpdi();

        // Import the template
        $pageCount = $pdf->setSourceFile($templatePath);
        $tplIdx = $pdf->importPage(1);

        // Get the size of the imported page and use it
        $size = $pdf->getTemplateSize($tplIdx);
        $pageWidth = $size['width'];
        $pageHeight = $size['height'];
        
        // Add a page with the same size as the template
        $pdf->AddPage($size['orientation'], [$pageWidth, $pageHeight]);
        $pdf->useTemplate($tplIdx);

        // Name positioning based on CSS: left: 7.8%, top: 47.9%, width: 65%, text-align: center
        $nameX = $pageWidth * 0.078;
        $nameY = $pageHeight * 0.479;
        $nameBoxWidth = $pageWidth * 0.65;

        // Set font for name - Using 'Courier' as a placeholder for cursive if Lucida is not installed
        // To use Lucida Handwriting, you would need to add the font files (.php and .z) to the FPDF font directory
        $currentFontSize = 36;
        try {
            // Attempt to use Lucida Handwriting if it's been added to the vendor font folder
            $pdf->SetFont('LucidaHandwriting', '', $currentFontSize);
        } catch (\Exception $e) {
            // Fallback to Courier Italic which has a slightly more "handwritten" feel than Arial
            $pdf->SetFont('Courier', 'I', $currentFontSize);
        }
        $pdf->SetTextColor(0, 0, 0);

        // Auto-scale font size if name is too long for the box
        while ($pdf->GetStringWidth($name) > $nameBoxWidth && $currentFontSize > 12) {
            $currentFontSize -= 2;
            $pdf->SetFontSize($currentFontSize);
        }

        // Use a height that matches the font size (~13mm for 36pt)
        $pdf->SetXY($nameX, $nameY);
        $pdf->Cell($nameBoxWidth, 13, $name, 0, 0, 'C');

        // Date positioning based on CSS: left: 12%, top: 82.7%, width: 24% (default text-align: left)
        $dateX = $pageWidth * 0.12;
        $dateY = $pageHeight * 0.827;
        $dateBoxWidth = $pageWidth * 0.24;

        // Format date as "28 Apr 2026"
        $formattedDate = \Carbon\Carbon::parse($requestedDate)->format('d M Y');

        // Set font for date - Using same font as name for consistency
        try {
            $pdf->SetFont('LucidaHandwriting', '', 18);
        } catch (\Exception $e) {
            $pdf->SetFont('Courier', 'I', 18);
        }
        $pdf->SetTextColor(0, 0, 0);

        // Use a height that matches the font size (~7mm for 18pt)
        $pdf->SetXY($dateX, $dateY);
        $pdf->Cell($dateBoxWidth, 7, $formattedDate, 0, 0, 'L');

        // Output the PDF
        $pdf->Output('I', 'certificate.pdf');
    }
}
