<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrnItem extends Model
{
    use HasFactory;

    protected $fillable = ['grn_id', 'sku_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
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
