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
        $name = $request->query('name');
        $requestedDate = $request->query('date');

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
        $pageWidth = $pdf->getPageWidth($tplIdx);
        $pageHeight = $pdf->getPageHeight($tplIdx);
        
        // Add a page with the same size as the template
        $pdf->AddPage('L', [$pageWidth, $pageHeight]);
        $pdf->useTemplate($tplIdx);

        // Set font for name - bold, larger size
        $pdf->SetFont('Arial', 'B', 28);
        $pdf->SetTextColor(0, 0, 0); // Black color

        // Calculate width of name text to center it properly
        $nameWidth = $pdf->GetStringWidth($name);
        $pageWidth = $pdf->GetPageWidth();
        $margin = 20; // Margin from edges
        
        // Center the name with proper margins
        $xPos = ($pageWidth - $nameWidth) / 2;
        
        // Ensure name doesn't go beyond margins
        if ($xPos < $margin) {
            // If name is too long, reduce font size
            $pdf->SetFont('Arial', 'B', 20);
            $nameWidth = $pdf->GetStringWidth($name);
            $xPos = ($pageWidth - $nameWidth) / 2;
        }
        
        // Add name (adjusted Y position for better spacing)
        $pdf->SetXY($xPos, 95);
        $pdf->Cell(0, 12, $name, 0, 1, 'L');

        // Format date as "28 Mar 2026" (DD Mon YYYY)
        $formattedDate = \Carbon\Carbon::parse($requestedDate)->format('d M Y');

        // Add date - black color, smaller font
        $pdf->SetFont('Arial', '', 16);
        $pdf->SetTextColor(0, 0, 0); // Black color
        
        $dateText = 'Completed on: ' . $formattedDate;
        $dateWidth = $pdf->GetStringWidth($dateText);
        $pageWidth = $pdf->GetPageWidth();
        $dateXPos = ($pageWidth - $dateWidth) / 2;
        
        $pdf->SetXY($dateXPos, 115);
        $pdf->Cell(0, 10, $dateText, 0, 1, 'L');

        // Output the PDF
        $pdf->Output('I', 'certificate.pdf');
    }
}
