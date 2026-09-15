<?php

namespace App\Services;

use App\Models\DispatchSheet;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public function generateDispatchPdf(DispatchSheet $sheet): string
    {
        $sheet->load(['items.sku', 'godown', 'creator']);

        $data = [
            'sheet' => $sheet,
            'companyName' => Setting::get('company_name', 'Company Name'),
            'companyLogo' => Setting::get('company_logo'),
            'companyAddress' => Setting::get('company_address'),
            'companyPhone' => Setting::get('company_phone'),
            'companyFax' => Setting::get('company_fax'),
            'companyEmail' => Setting::get('company_email'),
        ];

        $pdf = Pdf::loadView('pdf.dispatch-sheet', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = "dispatch-sheets/{$sheet->ds_number}.pdf";
        Storage::disk('public')->put($filename, $pdf->output());

        $sheet->update(['pdf_path' => $filename]);

        return $filename;
    }
}
