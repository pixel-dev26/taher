<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdjustmentItem extends Model
{
    use HasFactory;

    protected $fillable = ['stock_adjustment_id', 'sku_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
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
