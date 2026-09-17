<?php

namespace App\Http\Controllers;

use App\Exports\DispatchRegisterExport;
use App\Exports\StockAsOnDateExport;
use App\Exports\StockLedgerExport;
use App\Http\Controllers\Concerns\SanitizesFilters;
use App\Models\DispatchSheet;
use App\Models\Godown;
use App\Models\Setting;
use App\Models\StockLedger;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    use SanitizesFilters;

    // stockAsOnDate() and dispatchRegister() lived here until their screens were
    // merged into Stock (with an "as at" date) and Dispatch (History tab). Both
    // URLs now redirect; the exports below are unchanged.

    private const FILTER_RULES = [
        'date' => 'nullable|date_format:Y-m-d',
        'date_from' => 'nullable|date_format:Y-m-d',
        'date_to' => 'nullable|date_format:Y-m-d',
        'sku_id' => 'nullable|integer|exists:skus,id',
        'godown_id' => 'nullable|integer|exists:godowns,id',
        'status' => 'nullable|string|in:pending,dispatched,cancelled',
        'movement_type' => 'nullable|string|max:50',
        'category' => 'nullable|string|max:100',
        'search' => 'nullable|string|max:255',
        'hide_zero' => 'nullable|boolean',
    ];

    public function stockLedger(Request $request)
    {
        $godowns = Godown::active()->get();
        $entries = collect();
        $filters = $this->filters($request, self::FILTER_RULES);

        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $query = StockLedger::with(['sku', 'godown', 'performer'])
                ->whereDate('created_at', '>=', $filters['date_from'])
                ->whereDate('created_at', '<=', $filters['date_to']);

            if (! empty($filters['sku_id'])) {
                $query->where('sku_id', $filters['sku_id']);
            }

            if (! empty($filters['godown_id'])) {
                $query->where('godown_id', $filters['godown_id']);
            }

            if (! empty($filters['movement_type'])) {
                $query->where('movement_type', $filters['movement_type']);
            }

            $entries = $query->latest('created_at')->latest('id')->paginate(50)->withQueryString();
        }

        return view('reports.stock-ledger', compact('entries', 'godowns'));
    }

    public function exportDispatchRegisterExcel(Request $request)
    {
        return Excel::download(
            new DispatchRegisterExport($this->filters($request, self::FILTER_RULES)),
            'dispatch-register-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportDispatchRegisterPdf(Request $request)
    {
        $filters = $this->filters($request, self::FILTER_RULES);

        // Match the History tab, which defaults to the last 30 days rather than
        // rendering blank until both dates are supplied.
        $query = DispatchSheet::with(['items.sku', 'godown', 'creator'])
            ->whereDate('created_at', '>=', $filters['date_from'] ?? today()->subDays(30)->format('Y-m-d'));

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['status'])) $query->where('status', $filters['status']);
        if (! empty($filters['godown_id'])) $query->where('godown_id', $filters['godown_id']);

        $sheets = $query->latest()->get();

        $company = [
            'name' => Setting::get('company_name', 'Company Name'),
            'logo' => Setting::get('company_logo'),
            'address' => Setting::get('company_address'),
            'phone' => Setting::get('company_phone'),
            'fax' => Setting::get('company_fax'),
            'email' => Setting::get('company_email'),
        ];

        $pdf = Pdf::loadView('reports.dispatch-register-pdf', compact('sheets', 'company'));
        return $pdf->download('dispatch-register-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportStockAsOnDateExcel(Request $request)
    {
        $filters = $this->filters($request, self::FILTER_RULES);

        return Excel::download(
            new StockAsOnDateExport($filters),
            'stock-as-on-date-' . ($filters['date'] ?? today()->format('Y-m-d')) . '.xlsx'
        );
    }

    public function exportStockLedgerExcel(Request $request)
    {
        return Excel::download(
            new StockLedgerExport($this->filters($request, self::FILTER_RULES)),
            'stock-ledger-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
