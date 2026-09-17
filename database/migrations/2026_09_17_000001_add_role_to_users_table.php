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
            // Leaving everyone as 'staff' would lock every account out of
            // the Users screen with no way back except raw SQL. The oldest
            // active account is the one that set the business up; promote it
            // and say so, so it can be corrected from the Users screen.
            $original = DB::table('users')->where('is_active', true)->orderBy('id')->first()
                ?? DB::table('users')->orderBy('id')->first();

            DB::table('users')->where('id', $original->id)->update(['role' => 'admin']);

            Log::warning(
                "add_role_to_users_table migration: {$userCount} users already existed; ".
                "only the oldest account ({$original->email}) was promoted to admin. ".
                'Review the roles on the Users screen.'
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
