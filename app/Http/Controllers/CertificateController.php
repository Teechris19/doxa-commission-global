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

        // Add a page
        $pdf->AddPage();
        $pdf->useTemplate($tplIdx);

        // Set font
        $pdf->SetFont('Arial', 'B', 24);
        $pdf->SetTextColor(30, 58, 138); // Blue

        // Add name (adjust position as needed)
        $pdf->SetXY(50, 100); // Adjust coordinates
        $pdf->Cell(0, 10, $name, 0, 1, 'C');

        // Add date
        $pdf->SetFont('Arial', '', 16);
        $pdf->SetTextColor(220, 38, 38); // Red
        $pdf->SetXY(50, 120); // Adjust
        $pdf->Cell(0, 10, 'Completed on: ' . $requestedDate, 0, 1, 'C');

        // Output the PDF
        $pdf->Output('I', 'certificate.pdf');
    }
}
