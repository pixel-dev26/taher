<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
    use HasFactory;

    public $timestamps = false; // Only created_at, no updated_at

    protected $table = 'stock_ledger';

    protected $fillable = [
        'sku_id', 'godown_id', 'movement_type', 'quantity',
        'balance_after', 'reference_type', 'reference_id',
        'performed_by', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'balance_after' => 'decimal:3',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    public function delete()
    {
        throw new \RuntimeException('StockLedger entries cannot be deleted. This is an append-only table.');
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }

    public function godown()
    {
        return $this->belongsTo(Godown::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
