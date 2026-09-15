<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sku_id')->constrained('skus');
            $table->foreignId('godown_id')->constrained('godowns');
            $table->enum('movement_type', [
                'stock_in',
                'reserved',
                'reserve_released',
                'dispatch_out',
                'transfer_out',
                'transfer_in',
                'transfer_rejected',
                'adjustment',
            ]);
            $table->decimal('quantity', 12, 3);
            $table->decimal('balance_after', 12, 3);
            $table->string('reference_type', 50);
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('performed_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['sku_id', 'godown_id']);
            $table->index('movement_type');
            $table->index('created_at');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
    }
};
