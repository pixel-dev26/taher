<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * HSN code captured per dispatch line, not just read from the product
     * catalog — most SKUs don't have one set yet, and the Delivery Challan
     * must always show one. Line-items.blade.php pre-fills this from the
     * SKU's own hsn_code when it's already known, so it's only ever typed
     * once per product in practice (DispatchSheetController also backfills
     * the SKU's hsn_code the first time one is entered here).
     */
    public function up(): void
    {
        Schema::table('dispatch_sheet_items', function (Blueprint $table) {
            $table->string('hsn_code', 20)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_sheet_items', function (Blueprint $table) {
            $table->dropColumn('hsn_code');
        });
    }
};
