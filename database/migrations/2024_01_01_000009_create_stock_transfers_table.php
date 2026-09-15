<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 30)->unique();
            $table->foreignId('source_godown_id')->constrained('godowns');
            $table->foreignId('dest_godown_id')->constrained('godowns');
            $table->enum('status', ['pending', 'completed', 'rejected'])->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('resolved_by')->references('id')->on('users');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
