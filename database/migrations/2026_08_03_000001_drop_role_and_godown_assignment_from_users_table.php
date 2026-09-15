<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['assigned_godown_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'assigned_godown_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'manager', 'storekeeper'])->default('admin');
            $table->unsignedBigInteger('assigned_godown_id')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('assigned_godown_id')->references('id')->on('godowns');
        });
    }
};
