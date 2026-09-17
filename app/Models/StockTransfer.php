<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'transfer_number', 'source_godown_id', 'dest_godown_id',
        'status', 'created_by', 'resolved_by', 'notes', 'resolved_at',
        'vehicle_no', 'driver_name', 'driver_phone',
        'lr_no', 'eway_no', 'transport_name', 'transport_id',
    ];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function sourceGodown()
    {
        return $this->belongsTo(Godown::class, 'source_godown_id');
    }

    public function destGodown()
    {
        return $this->belongsTo(Godown::class, 'dest_godown_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function items()
    {
        return $this->hasMany(TransferItem::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                     ->where('created_at', '<', now()->subHours(48));
    }

    public function activityLogLabel(): string
    {
        return $this->transfer_number;
    }
}
