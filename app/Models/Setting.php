<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'value', 'updated_by', 'updated_at'];

    protected function casts(): array
    {
        return ['updated_at' => 'datetime'];
    }

    public static function get(string $key, $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value, int $userId = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_by' => $userId, 'updated_at' => now()]
        );
    }
}
