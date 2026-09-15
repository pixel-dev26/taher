<?php

namespace App\Http\Controllers;

use App\Exports\DispatchRegisterExport;
use App\Exports\StockLedgerExport;
use App\Models\DispatchSheet;
use App\Models\Godown;
use App\Models\Setting;
use App\Models\Sku;
use App\Models\StockLedger;
use App\Models\StockRecord;
use App\Services\StockService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    // stockAsOnDate() and dispatchRegister() lived here until their screens were
    // merged into Stock (with an "as at" date) and Dispatch (History tab). Both
    // URLs now redirect; the exports below are unchanged.

    public function stockLedger(Request $request)
    {
        $godowns = Godown::active()->get();
        $entries = collect();

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query = StockLedger::with(['sku', 'godown', 'performer'])
                ->whereDate('created_at', '>=', $request->date_from)
                ->whereDate('created_at', '<=', $request->date_to);

            if ($request->filled('sku_id')) {
                $query->where('sku_id', $request->sku_id);
            }

            if ($request->filled('godown_id')) {
                $query->where('godown_id', $request->godown_id);
            }

            if ($request->filled('movement_type')) {
                $query->where('movement_type', $request->movement_type);
            }

            $entries = $query->latest('created_at')->paginate(50)->withQueryString();
        }

        return view('reports.stock-ledger', compact('entries', 'godowns'));
    }

    public function exportDispatchRegisterExcel(Request $request)
    {
        return Excel::download(
            new DispatchRegisterExport($request->all()),
            'dispatch-register-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportDispatchRegisterPdf(Request $request)
    {
        // Match the History tab, which defaults to the last 30 days rather than
        // rendering blank until both dates are supplied.
        $query = DispatchSheet::with(['items.sku', 'godown', 'creator'])
            ->whereDate('created_at', '>=', $request->get('date_from', today()->subDays(30)->format('Y-m-d')));

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('godown_id')) $query->where('godown_id', $request->godown_id);

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
        // Reuse the same logic but export as Excel
        return Excel::download(
            new \App\Exports\StockAsOnDateExport($request->all()),
            'stock-as-on-date-' . $request->get('date', today()->format('Y-m-d')) . '.xlsx'
        );
    }

    public function exportStockLedgerExcel(Request $request)
    {
        return Excel::download(
            new StockLedgerExport($request->all()),
            'stock-ledger-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
