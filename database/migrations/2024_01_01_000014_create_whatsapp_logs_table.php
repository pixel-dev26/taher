<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_sheet_id')->constrained('dispatch_sheets');
            $table->string('recipient_phone', 20);
            $table->text('message_content');
            $table->enum('status', ['sent', 'failed']);
            $table->text('api_response')->nullable();
            $table->foreignId('sent_by')->constrained('users');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
