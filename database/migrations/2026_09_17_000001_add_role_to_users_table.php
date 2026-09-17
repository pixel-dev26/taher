<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two roles: admin (full access, including the one destructive action in
     * the app and the Activity Log) and staff (everything day-to-day —
     * receiving, dispatching, transfers, corrections, products — except that
     * and except the log).
     *
     * Defaults new rows to 'staff'. If exactly one user already exists — the
     * account already running the business, true for both the live database
     * and this local copy at the time this was written — it's promoted to
     * admin automatically. With more than one pre-existing account, this
     * can't guess which one(s) should have full access, so it leaves
     * everyone as 'staff' and logs a warning instead of guessing wrong.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'staff'])->default('staff')->after('email');
        });

        $userCount = DB::table('users')->count();

        if ($userCount === 1) {
            DB::table('users')->update(['role' => 'admin']);
        } elseif ($userCount > 1) {
            Log::warning(
                "add_role_to_users_table migration: {$userCount} users already existed; ".
                'none were auto-promoted to admin. Promote the correct account(s) manually '.
                '(e.g. via a one-off query or, once at least one admin exists, the Users screen).'
            );
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
