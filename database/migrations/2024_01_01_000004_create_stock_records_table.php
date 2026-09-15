<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sku_id')->constrained('skus');
            $table->foreignId('godown_id')->constrained('godowns');
            $table->decimal('on_hand', 12, 3)->default(0);
            $table->decimal('reserved', 12, 3)->default(0);
            $table->timestamps();

            $table->unique(['sku_id', 'godown_id']);
        });

        DB::statement('ALTER TABLE stock_records ADD CONSTRAINT chk_on_hand_non_negative CHECK (on_hand >= 0)');
        DB::statement('ALTER TABLE stock_records ADD CONSTRAINT chk_reserved_non_negative CHECK (reserved >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_records');
    }
};
