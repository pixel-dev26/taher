<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number', 30)->unique();
            $table->foreignId('godown_id')->constrained('godowns');
            $table->enum('reason', ['initial_load', 'grn_correction', 'physical_count', 'damage_loss', 'other']);
            $table->text('reason_notes');
            $table->string('reference_doc', 255)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
