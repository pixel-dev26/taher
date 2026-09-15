<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdjustmentItem extends Model
{
    use HasFactory;

    protected $fillable = ['stock_adjustment_id', 'sku_id', 'quantity', 'unit_price'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
        ];
    }

    /** Quantity x price for stock added, or null when there is no price. */
    public function getAmountAttribute(): ?float
    {
        return $this->unit_price === null ? null : (float) $this->quantity * (float) $this->unit_price;
    }

    public function stockAdjustment()
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }
}
