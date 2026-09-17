<?php

namespace App\Services;

use App\Models\DispatchSheet;
use App\Models\Setting;
use App\Models\StockTransfer;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;

class PdfService
{
    /**
     * The plain Dispatch Sheet. Generated on demand and streamed by the
     * caller — nothing is written to disk (a cached copy on the public disk
     * used to be downloadable at a predictable /storage URL with no login).
     */
    public function generateDispatchPdf(DispatchSheet $sheet): PdfDocument
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

        return $pdf;
    }

    /**
     * Why a GST challan can't be produced for this document, or null when it
     * can. Lines recorded before rates and HSN codes were captured would
     * otherwise print as a certified "Rate 0.00 / ZERO RUPEES ONLY".
     */
    public function challanBlocker(DispatchSheet|StockTransfer $document): ?string
    {
        $document->loadMissing('items.sku');

        $unpriced = $document->items->filter(fn ($item) => $item->unit_price === null)->count();
        $noHsn = $document->items->filter(fn ($item) => ! ($item->hsn_code ?: $item->sku->hsn_code))->count();

        if (! $unpriced && ! $noHsn) {
            return null;
        }

        $parts = [];
        if ($unpriced) {
            $parts[] = "{$unpriced} line(s) have no rate recorded";
        }
        if ($noHsn) {
            $parts[] = "{$noHsn} line(s) have no HSN code";
        }

        $advice = $document instanceof DispatchSheet && $document->status === 'pending'
            ? 'Edit the dispatch to add them, then download the challan again.'
            : 'These lines were recorded before rates and HSN codes were captured.';

        return 'A GST challan cannot be generated: ' . implode(' and ', $parts) . '. ' . $advice;
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

        $data = ['sheet' => $sheet] + $this->challanFigures($sheet->items) + $this->companyDetails();

        $pdf = Pdf::loadView('pdf.delivery-challan', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf;
    }

    /**
     * A Road/Delivery Challan for an inter-godown Stock Transfer — same
     * document family, layout and fields as the sales Delivery Challan
     * above (including the Rate / Taxable Value / GST columns), but both
     * ends are "Ship From" / "Ship To" godowns of the same company rather
     * than a company + a customer.
     */
    public function generateTransferChallanPdf(StockTransfer $transfer): PdfDocument
    {
        $transfer->load(['items.sku', 'sourceGodown', 'destGodown']);

        $data = ['transfer' => $transfer] + $this->challanFigures($transfer->items) + $this->companyDetails();

        $pdf = Pdf::loadView('pdf.transfer-challan', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf;
    }

    /**
     * Every figure is rounded to paise as soon as it exists and every total
     * is a sum of the printed line figures, so the lines, the footer row,
     * the totals box and the amount in words can never disagree by a paisa.
     */
    private function challanFigures($items): array
    {
        $gstRate = (float) Setting::get('default_gst_rate', 18);
        $halfRate = $gstRate / 2;

        $lines = $items->map(function ($item) use ($halfRate) {
            $quantity = (float) $item->quantity;
            $rate = $item->unit_price !== null ? (float) $item->unit_price : 0.0;
            $taxable = round($quantity * $rate, 2);
            $cgstAmount = round($taxable * $halfRate / 100, 2);
            $sgstAmount = round($taxable * $halfRate / 100, 2);

            return (object) [
                'sku' => $item->sku,
                // The rate charged and the HSN code are both captured per
                // line (not just read from the product catalog); the
                // product's own HSN is only a fallback.
                'hsn' => $item->hsn_code ?: $item->sku->hsn_code,
                'quantity' => $quantity,
                'rate' => $rate,
                'taxable' => $taxable,
                'cgst_rate' => $halfRate,
                'cgst_amount' => $cgstAmount,
                'sgst_rate' => $halfRate,
                'sgst_amount' => $sgstAmount,
                'total' => round($taxable + $cgstAmount + $sgstAmount, 2),
            ];
        });

        $taxableTotal = round($lines->sum('taxable'), 2);
        $cgstTotal = round($lines->sum('cgst_amount'), 2);
        $sgstTotal = round($lines->sum('sgst_amount'), 2);
        $grandTotal = round($taxableTotal + $cgstTotal + $sgstTotal, 2);

        return [
            'lines' => $lines,
            'taxableTotal' => $taxableTotal,
            'cgstTotal' => $cgstTotal,
            'sgstTotal' => $sgstTotal,
            'grandTotal' => $grandTotal,
            'amountInWords' => Money::words($grandTotal),
        ];
    }

    private function companyDetails(): array
    {
        return [
            'companyName' => Setting::get('company_name', 'Company Name'),
            'companyLogo' => Setting::get('company_logo'),
            'companyAddress' => Setting::get('company_address'),
            'companyPhone' => Setting::get('company_phone'),
            'companyFax' => Setting::get('company_fax'),
            'companyEmail' => Setting::get('company_email'),
            'companyGstin' => Setting::get('company_gstin'),
            'companyState' => Setting::get('company_state'),
        ];
    }
}
