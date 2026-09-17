<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;

/**
 * Auto-discovered by its type-hint (like LogSuccessfulLogin). A failed
 * attempt is worth a row so a run of guesses against an account shows up in
 * the Activity Log; only the email is recorded, never the password tried.
 */
class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        $email = (string) ($event->credentials['email'] ?? '');

        ActivityLog::create([
            'user_id' => $event->user?->id,
            'user_name' => $event->user?->name ?? 'Unknown',
            'action' => 'login_failed',
            'subject_type' => User::class,
            'subject_id' => $event->user?->id,
            'subject_label' => $event->user?->activityLogLabel() ?? $email,
            'changes' => ['after' => ['email' => $email, 'ip' => request()->ip()]],
        ]);
    }
}
