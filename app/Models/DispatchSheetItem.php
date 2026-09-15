<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispatchSheetItem extends Model
{
    use HasFactory;

    protected $fillable = ['dispatch_sheet_id', 'sku_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }

    public function dispatchSheet()
    {
        return $this->belongsTo(DispatchSheet::class);
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }
}
