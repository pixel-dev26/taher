<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Godown extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'address', 'contact_phone', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function stockRecords()
    {
        return $this->hasMany(StockRecord::class);
    }

    public function grns()
    {
        return $this->hasMany(Grn::class);
    }

    public function dispatchSheets()
    {
        return $this->hasMany(DispatchSheet::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
