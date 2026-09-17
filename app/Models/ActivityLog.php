<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false; // Only created_at, no updated_at

    protected $fillable = [
        'user_id', 'user_name', 'action', 'subject_type', 'subject_id',
        'subject_label', 'changes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    /** An audit trail that can be edited is not an audit trail. */
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new \RuntimeException('ActivityLog entries cannot be modified. This is an append-only table.');
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new \RuntimeException('ActivityLog entries cannot be deleted. This is an append-only table.');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** The model's own short name, e.g. "App\Models\Sku" -> "Sku". */
    public function getSubjectTypeShortAttribute(): string
    {
        return class_basename($this->subject_type);
    }
}
