<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, LogsActivity;

    protected $fillable = [
        'name', 'email', 'password', 'role',
        'is_active', 'must_change_password', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    /** Never write the actual hash to the Activity Log — only that it changed. */
    protected array $activityLogHidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    // Relationships
    public function grns()
    {
        return $this->hasMany(Grn::class, 'created_by');
    }

    public function dispatchSheets()
    {
        return $this->hasMany(DispatchSheet::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * After a password change, every other place this account is signed in
     * (a "keep me signed in" cookie on a phone, an open tab elsewhere) must
     * stop working — otherwise resetting a departed employee's password
     * revokes nothing. Rotating remember_token kills every recaller cookie;
     * dropping the other sessions rows kills every other live session.
     */
    public function invalidateOtherSessions(?string $exceptSessionId = null): void
    {
        $this->forceFill(['remember_token' => Str::random(60)])->saveQuietly();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $this->id)
                ->when($exceptSessionId, fn ($q) => $q->where('id', '!=', $exceptSessionId))
                ->delete();
        }
    }

    public function activityLogLabel(): string
    {
        return "{$this->name} ({$this->email})";
    }
}
