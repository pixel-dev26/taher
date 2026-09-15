<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrnItem extends Model
{
    use HasFactory;

    protected $fillable = ['grn_id', 'sku_id', 'quantity', 'unit_price'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
        ];
    }

    /** Quantity x price, or null for lines recorded before prices were captured. */
    public function getAmountAttribute(): ?float
    {
        return $this->unit_price === null ? null : (float) $this->quantity * (float) $this->unit_price;
    }

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }
}
