<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who changed what, and when — for the Admin-only Activity Log.
     *
     * user_name is a denormalized snapshot (not just user_id) so an entry
     * still reads correctly even if that user's name changes later. subject_*
     * identifies the record touched; subject_label is a human-readable
     * reference (a document number, product code, etc.) so the log doesn't
     * force a lookup by numeric id. changes holds a {before, after} pair of
     * only the fields that actually changed, with sensitive fields (e.g.
     * passwords) redacted before they ever reach this table.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('action', 30);
            $table->string('subject_type', 100);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
