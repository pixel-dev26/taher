<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('ds_number', 30)->unique();
            $table->foreignId('godown_id')->constrained('godowns');
            $table->enum('status', ['pending', 'dispatched', 'cancelled'])->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->string('customer_name', 255)->nullable();
            $table->text('delivery_address')->nullable();
            $table->date('delivery_date')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->unsignedBigInteger('dispatched_by')->nullable();
            $table->string('vehicle_no', 50)->nullable();
            $table->string('driver_name', 255)->nullable();
            $table->string('driver_phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->timestamps();

            $table->foreign('dispatched_by')->references('id')->on('users');
            $table->index('status');
            $table->index('godown_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_sheets');
    }
};
