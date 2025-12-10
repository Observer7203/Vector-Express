<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'carrier_id',
        'price',
        'currency',
        'delivery_days',
        'estimated_delivery_date',
        'transport_type',
        'services_included',
        'valid_until',
        'is_selected',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'estimated_delivery_date' => 'date',
            'services_included' => 'array',
            'valid_until' => 'datetime',
            'is_selected' => 'boolean',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function isValid(): bool
    {
        return $this->valid_until === null || $this->valid_until->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    public function select(): void
    {
        // Deselect all other quotes for this shipment
        Quote::where('shipment_id', $this->shipment_id)
            ->where('id', '!=', $this->id)
            ->update(['is_selected' => false]);

        $this->is_selected = true;
        $this->save();
    }
}
