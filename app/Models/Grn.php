<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grn extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_number', 'godown_id', 'receipt_date', 'challan_no',
        'supplier_name', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['receipt_date' => 'date'];
    }

    public function godown()
    {
        return $this->belongsTo(Godown::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(GrnItem::class);
    }
}
