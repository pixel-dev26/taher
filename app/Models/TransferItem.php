<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferItem extends Model
{
    use HasFactory;

    protected $fillable = ['stock_transfer_id', 'sku_id', 'quantity', 'unit_price', 'hsn_code'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }
}
