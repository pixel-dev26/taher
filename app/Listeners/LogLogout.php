<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Logout;

/** Auto-discovered by its type-hint, like LogSuccessfulLogin. */
class LogLogout
{
    public function handle(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        ActivityLog::create([
            'user_id' => $event->user->id,
            'user_name' => $event->user->name,
            'action' => 'logout',
            'subject_type' => get_class($event->user),
            'subject_id' => $event->user->id,
            'subject_label' => $event->user->activityLogLabel(),
        ]);
    }
}
