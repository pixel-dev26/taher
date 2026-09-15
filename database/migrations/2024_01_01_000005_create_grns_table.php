<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grns', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 30)->unique();
            $table->foreignId('godown_id')->constrained('godowns');
            $table->date('receipt_date');
            $table->string('challan_no', 100)->nullable();
            $table->string('supplier_name', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('godown_id');
            $table->index('receipt_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grns');
    }
};
