<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prices wherever stock or products come in.
     *
     * skus.price is the product's starting price: it values stock that has no
     * price of its own (opening stock loaded before prices were tracked) and is
     * shown until priced stock arrives. adjustment_items.unit_price prices
     * stock added through a correction. Both nullable for existing records.
     */
    public function up(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable()->after('unit_of_measure');
        });

        Schema::table('adjustment_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('adjustment_items', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });

        Schema::table('skus', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
