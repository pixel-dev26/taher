<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same per-line fields as dispatch_sheet_items — a valuation rate and an
     * HSN code — so the Stock Transfer Road Challan can show the same Rate /
     * Taxable Value / GST columns as the sales Delivery Challan. Nullable
     * because transfers created before this existed have neither.
     */
    public function up(): void
    {
        Schema::table('transfer_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->nullable()->after('quantity');
            $table->string('hsn_code', 20)->nullable()->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('transfer_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'hsn_code']);
        });
    }
};
