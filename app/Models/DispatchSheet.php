<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispatchSheet extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'ds_number', 'godown_id', 'status', 'created_by', 'customer_name',
        'customer_phone', 'customer_gstin', 'place_of_supply',
        'delivery_address', 'delivery_date', 'dispatched_at', 'dispatched_by',
        'vehicle_no', 'driver_name', 'driver_phone',
        'lr_no', 'eway_no', 'transport_name', 'transport_id', 'notes',
        'cancel_reason', 'cancelled_at', 'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'dispatched_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function godown()
    {
        return $this->belongsTo(Godown::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dispatcher()
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function items()
    {
        return $this->hasMany(DispatchSheetItem::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDispatched($query)
    {
        return $query->where('status', 'dispatched');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function activityLogLabel(): string
    {
        return $this->ds_number;
    }
}
