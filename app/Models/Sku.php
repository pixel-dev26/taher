<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sku extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'category', 'variant_attributes',
        'unit_of_measure', 'low_stock_threshold', 'hsn_code', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variant_attributes' => 'array',
            'is_active' => 'boolean',
            'low_stock_threshold' => 'integer',
        ];
    }

    public function stockRecords()
    {
        return $this->hasMany(StockRecord::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getTotalAvailableAttribute(): float
    {
        return $this->stockRecords->sum(function ($record) {
            return $record->on_hand - $record->reserved;
        });
    }
}
