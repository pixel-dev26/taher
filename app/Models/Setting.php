<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use LogsActivity;

    public $timestamps = false;

    protected $fillable = ['key', 'value', 'updated_by', 'updated_at'];

    protected function casts(): array
    {
        return ['updated_at' => 'datetime'];
    }

    /** A row whose value was blanked counts as unset, so the default still applies. */
    public static function get(string $key, $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting || $setting->value === null || $setting->value === '') {
            return $default;
        }

        return $setting->value;
    }

    public static function set(string $key, $value, int $userId = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_by' => $userId, 'updated_at' => now()]
        );
    }

    public function activityLogLabel(): string
    {
        return $this->key;
    }

    /**
     * Setting is a generic key/value store, so LogsActivity's normal
     * name-based redaction (a fixed list of column names) can't tell a
     * sensitive row from a harmless one — only this row's own `key` can.
     * Nothing sensitive is stored here today, but the moment a future
     * setting holds a credential (an SMTP password, an API token), this is
     * what keeps it out of the Activity Log.
     */
    protected function activityLogShouldRedact(string $field): bool
    {
        if ($field !== 'value') {
            return false;
        }

        $needles = ['password', 'secret', 'token', 'api_key', 'apikey'];
        $key = strtolower($this->key ?? '');

        foreach ($needles as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }
}
