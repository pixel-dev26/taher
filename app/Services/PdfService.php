<?php

namespace App\Services;

use App\Models\DispatchSheet;
use App\Models\Setting;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
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

    /**
     * A GST Road/Delivery Challan — separate from the plain Dispatch Sheet
     * PDF above. Not cached to disk: generated fresh on every download, so it
     * always reflects the sheet's current rates and status.
     *
     * Tax is one rate for the whole document (Settings > default_gst_rate),
     * split evenly into CGST + SGST for an intra-state supply — the app has
     * no per-product GST classification or place-of-supply-based IGST logic
     * yet, so that is the one thing worth widening later if it's ever needed.
     */
    public function generateChallanPdf(DispatchSheet $sheet): PdfDocument
    {
        $sheet->load(['items.sku', 'godown', 'creator']);

        $gstRate = (float) Setting::get('default_gst_rate', 18);
        $halfRate = $gstRate / 2;

        $lines = $sheet->items->map(function ($item) use ($halfRate) {
            $quantity = (float) $item->quantity;
            $rate = $item->unit_price !== null ? (float) $item->unit_price : 0.0;
            $taxable = $quantity * $rate;
            $cgstAmount = round($taxable * $halfRate / 100, 2);
            $sgstAmount = round($taxable * $halfRate / 100, 2);

            return (object) [
                'sku' => $item->sku,
                'quantity' => $quantity,
                'rate' => $rate,
                'taxable' => $taxable,
                'cgst_rate' => $halfRate,
                'cgst_amount' => $cgstAmount,
                'sgst_rate' => $halfRate,
                'sgst_amount' => $sgstAmount,
                'total' => $taxable + $cgstAmount + $sgstAmount,
            ];
        });

        $taxableTotal = $lines->sum('taxable');
        $cgstTotal = $lines->sum('cgst_amount');
        $sgstTotal = $lines->sum('sgst_amount');
        $grandTotal = $lines->sum('total');

        $data = [
            'sheet' => $sheet,
            'lines' => $lines,
            'companyName' => Setting::get('company_name', 'Company Name'),
            'companyLogo' => Setting::get('company_logo'),
            'companyAddress' => Setting::get('company_address'),
            'companyPhone' => Setting::get('company_phone'),
            'companyFax' => Setting::get('company_fax'),
            'companyEmail' => Setting::get('company_email'),
            'companyGstin' => Setting::get('company_gstin'),
            'taxableTotal' => $taxableTotal,
            'cgstTotal' => $cgstTotal,
            'sgstTotal' => $sgstTotal,
            'grandTotal' => $grandTotal,
            'amountInWords' => Money::words($grandTotal),
        ];

        $pdf = Pdf::loadView('pdf.delivery-challan', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf;
    }
}
