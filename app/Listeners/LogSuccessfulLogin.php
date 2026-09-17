<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        ActivityLog::create([
            'user_id' => $event->user->id,
            'user_name' => $event->user->name,
            'action' => 'login',
            'subject_type' => get_class($event->user),
            'subject_id' => $event->user->id,
            'subject_label' => $event->user->activityLogLabel(),
        ]);
    }
}
