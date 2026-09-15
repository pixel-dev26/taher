<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRecord extends Model
{
    use HasFactory;

    protected $fillable = ['sku_id', 'godown_id', 'on_hand', 'reserved'];

    protected function casts(): array
    {
        return [
            'on_hand' => 'decimal:3',
            'reserved' => 'decimal:3',
        ];
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }

    public function godown()
    {
        return $this->belongsTo(Godown::class);
    }

    public function getAvailableAttribute(): float
    {
        return (float)$this->on_hand - (float)$this->reserved;
    }
}
