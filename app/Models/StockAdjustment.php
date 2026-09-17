<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'adjustment_number', 'godown_id', 'reason', 'reason_notes',
        'reference_doc', 'created_by',
    ];

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
        return $this->hasMany(AdjustmentItem::class);
    }

    public function activityLogLabel(): string
    {
        return $this->adjustment_number;
    }
}
