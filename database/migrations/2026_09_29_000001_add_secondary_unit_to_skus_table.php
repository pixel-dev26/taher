<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A product can optionally have a second unit alongside its base
     * unit_of_measure — e.g. tracked in Pieces, but also bought or sold by
     * the Metre. conversion_rate means "1 base unit = conversion_rate
     * secondary units". Both columns are null together for the (typical)
     * product with only one unit.
     */
    public function up(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->string('secondary_unit_of_measure', 20)->nullable()->after('unit_of_measure');
            $table->decimal('conversion_rate', 12, 4)->nullable()->after('secondary_unit_of_measure');
        });
    }

    public function down(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->dropColumn(['secondary_unit_of_measure', 'conversion_rate']);
        });
    }
};
