<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_sheet_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_sheet_id')->constrained('dispatch_sheets')->cascadeOnDelete();
            $table->foreignId('sku_id')->constrained('skus');
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            $table->unique(['dispatch_sheet_id', 'sku_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_sheet_items');
    }
};
