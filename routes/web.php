<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DispatchFulfillmentController;
use App\Http\Controllers\DispatchSheetController;
use App\Http\Controllers\GodownController;
use App\Http\Controllers\GrnController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SkuController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockSearchController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/', fn() => redirect('/login'));

// Force password change
Route::get('/change-password', [PasswordController::class, 'showChangeForm'])->middleware(['auth', 'active'])->name('change-password');
Route::post('/change-password', [PasswordController::class, 'change'])->middleware(['auth', 'active']);

// All authenticated routes — two roles, admin and staff (see EnsureIsAdmin /
// the 'admin' middleware for what's admin-only: Users, the Activity Log, and
// deactivating a product). Everything else is open to both.
Route::middleware(['auth', 'active', 'ensurePasswordChanged'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // My Account
    Route::get('account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('account', [AccountController::class, 'update'])->name('account.update');

    // Users (Admin only)
    Route::resource('users', UserController::class)->except(['destroy', 'show'])->middleware('admin');

    // Activity Log (Admin only)
    Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index')->middleware('admin');

    // Products (SKU Master) — deactivating one is the app's one destructive
    // action, so that verb alone is Admin only; everything else is open.
    Route::resource('skus', SkuController::class)->middlewareFor('destroy', 'admin');

    // Godowns
    Route::resource('godowns', GodownController::class)->except(['show', 'destroy']);

    // Stock Corrections
    Route::resource('stock-adjustments', StockAdjustmentController::class)->only(['index', 'create', 'store', 'show']);

    // Stock Search
    Route::get('stock-search', [StockSearchController::class, 'index'])->name('stock-search');

    // Dispatch Sheets (create/track)
    Route::resource('dispatch-sheets', DispatchSheetController::class)->except(['destroy']);
    Route::patch('dispatch-sheets/{dispatch_sheet}/cancel', [DispatchSheetController::class, 'cancel'])->name('dispatch-sheets.cancel');
    Route::get('dispatch-sheets/{dispatch_sheet}/pdf', [DispatchSheetController::class, 'downloadPdf'])->name('dispatch-sheets.pdf');
    Route::get('dispatch-sheets/{dispatch_sheet}/challan', [DispatchSheetController::class, 'downloadChallan'])->name('dispatch-sheets.challan');

    // Dispatch Fulfillment (mark as actually dispatched)
    // The queue is now the "To send" tab on the Dispatch screen; this URL
    // redirects there. The detail view stays for deep links and printing.
    Route::get('fulfillment', function () {
        return redirect()->route('dispatch-sheets.index', ['tab' => 'to-send']);
    })->name('fulfillment.index');
    Route::get('fulfillment/{dispatch_sheet}', [DispatchFulfillmentController::class, 'show'])->name('fulfillment.show');
    Route::patch('fulfillment/{dispatch_sheet}/confirm', [DispatchFulfillmentController::class, 'confirm'])->name('fulfillment.confirm');

    // Stock IN (GRN)
    Route::resource('grn', GrnController::class)->only(['index', 'create', 'store', 'show']);

    // Stock Transfers (send / accept / reject between any two godowns)
    Route::resource('stock-transfers', StockTransferController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('stock-transfers/{stock_transfer}/accept', [StockTransferController::class, 'accept'])->name('stock-transfers.accept');
    Route::patch('stock-transfers/{stock_transfer}/reject', [StockTransferController::class, 'reject'])->name('stock-transfers.reject');
    Route::get('stock-transfers/{stock_transfer}/challan', [StockTransferController::class, 'downloadChallan'])->name('stock-transfers.challan');

    // Settings
    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        // Folded into the Stock screen, which now takes an "as at" date.
        // Kept as a redirect so old links and bookmarks still work.
        Route::get('stock-as-on-date', function (Request $request) {
            return redirect()->route('stock-search', $request->query());
        })->name('stock-as-on-date');

        // Folded into the Dispatch screen's History tab; kept as a redirect.
        Route::get('dispatch-register', function (Request $request) {
            return redirect()->route('dispatch-sheets.index', ['tab' => 'history'] + $request->query());
        })->name('dispatch-register');
        Route::get('stock-ledger', [ReportController::class, 'stockLedger'])->name('stock-ledger');

        // Exports
        Route::get('dispatch-register/export/excel', [ReportController::class, 'exportDispatchRegisterExcel'])->name('dispatch-register.export.excel');
        Route::get('dispatch-register/export/pdf', [ReportController::class, 'exportDispatchRegisterPdf'])->name('dispatch-register.export.pdf');
        Route::get('stock-as-on-date/export/excel', [ReportController::class, 'exportStockAsOnDateExcel'])->name('stock-as-on-date.export.excel');
        Route::get('stock-ledger/export/excel', [ReportController::class, 'exportStockLedgerExcel'])->name('stock-ledger.export.excel');
    });

    // JSON endpoints used by the shared product picker (public/js/line-items.js)
    Route::get('api/sku-search', [SkuController::class, 'apiSearch'])->name('api.sku-search');
    Route::get('api/stock-availability/{sku}/{godown}', [StockSearchController::class, 'apiGetAvailability'])->name('api.stock-availability');
});

require __DIR__.'/auth.php';
