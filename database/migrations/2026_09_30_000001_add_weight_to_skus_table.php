<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->decimal('weight', 10, 3)->nullable()->after('unit_of_measure');
        });
    }

    public function down(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};
