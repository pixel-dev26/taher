<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skus', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('category', 100)->default('Other');
            $table->json('variant_attributes')->nullable();
            $table->string('unit_of_measure', 20)->default('Pcs');
            $table->integer('low_stock_threshold')->default(10);
            $table->string('hsn_code', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category', 'idx_skus_category');
            $table->index('is_active', 'idx_skus_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skus');
    }
};
