<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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

    public function activityLogLabel(): string
    {
        return "{$this->name} ({$this->email})";
    }
}
