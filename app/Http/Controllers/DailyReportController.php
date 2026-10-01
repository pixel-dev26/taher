<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SanitizesFilters;
use App\Models\Setting;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Every day's stock in, stock out, transfers and corrections — rebuilt
 * fresh from the (append-only) stock ledger every time, for today or any
 * past date. See StockService::dailyReport() and PdfService's own docblock
 * for why nothing here is ever written to disk: a report is always exactly
 * reproducible from the ledger, so there is nothing a stored copy would add
 * except a stale file to keep in sync and clean up.
 */
class DailyReportController extends Controller
{
    use SanitizesFilters;

    public function __construct(private StockService $stockService)
    {
    }

    public function index(Request $request)
    {
        $date = $this->resolveDate($request);

        return view('reports.daily', $this->stockService->dailyReport($date));
    }

    public function downloadPdf(Request $request)
    {
        $date = $this->resolveDate($request);
        $data = $this->stockService->dailyReport($date) + [
            'companyName' => Setting::get('company_name', 'Company Name'),
            'companyLogo' => Setting::get('company_logo'),
            'companyAddress' => Setting::get('company_address'),
            'companyPhone' => Setting::get('company_phone'),
        ];

        $pdf = Pdf::loadView('pdf.daily-report', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("daily-report-{$date}.pdf");
    }

    /** Never an unparseable date (a stale bookmark, a tampered link), and never a future one. */
    private function resolveDate(Request $request): string
    {
        $filters = $this->filters($request, ['date' => 'nullable|date_format:Y-m-d']);
        $today = today()->format('Y-m-d');

        return min($filters['date'] ?? $today, $today);
    }
}
