<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Selling price per unit on each dispatch line — needed for the Delivery
     * Challan's Taxable Value / GST columns. Same shape as grn_items.unit_price
     * and adjustment_items.unit_price, but this one is the price charged to
     * the customer, not a purchase/valuation cost.
     *
     * Nullable because dispatch sheets created before this existed have none;
     * those keep their plain (unpriced) layout on screen.
     */
    public function up(): void
    {
        Schema::table('dispatch_sheet_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_sheet_items', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });
    }
};
