<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sku extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code', 'name', 'category', 'variant_attributes',
        'unit_of_measure', 'secondary_unit_of_measure', 'conversion_rate', 'weight',
        'price', 'low_stock_threshold', 'hsn_code', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variant_attributes' => 'array',
            'price' => 'decimal:2',
            'conversion_rate' => 'decimal:4',
            'weight' => 'decimal:3',
            'is_active' => 'boolean',
            'low_stock_threshold' => 'integer',
        ];
    }

    public function hasSecondaryUnit(): bool
    {
        return $this->secondary_unit_of_measure !== null && (float) $this->conversion_rate > 0;
    }

    /**
     * Quantities and prices are always stored in the base unit — see
     * App\Http\Requests\Concerns\ConvertsLineItemUnits, which calls these
     * before anything reaches validation or the database. conversion_rate
     * means "1 base unit = conversion_rate secondary units".
     */
    public function toBaseQuantity(float $quantity, string $unit): float
    {
        return $unit === 'secondary' && $this->hasSecondaryUnit()
            ? round($quantity / (float) $this->conversion_rate, 3)
            : $quantity;
    }

    public function toBaseUnitPrice(float $price, string $unit): float
    {
        return $unit === 'secondary' && $this->hasSecondaryUnit()
            ? round($price * (float) $this->conversion_rate, 2)
            : $price;
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

    public function activityLogLabel(): string
    {
        return $this->code;
    }
}
