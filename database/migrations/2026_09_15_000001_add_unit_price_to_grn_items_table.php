<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purchase price per unit on each received line.
     *
     * Nullable because receipts recorded before prices were captured have
     * none; those lines are left out of a product's average price.
     */
    public function up(): void
    {
        Schema::table('grn_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('grn_items', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });
    }
};
