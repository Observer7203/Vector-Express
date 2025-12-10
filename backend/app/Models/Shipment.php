<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'origin_country',
        'origin_city',
        'origin_address',
        'destination_country',
        'destination_city',
        'destination_address',
        'transport_type',
        'cargo_type',
        'packaging_type',
        'total_weight',
        'total_volume',
        'declared_value',
        'currency',
        'insurance_required',
        'customs_clearance',
        'door_to_door',
        'pickup_date',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_weight' => 'decimal:2',
            'total_volume' => 'decimal:4',
            'declared_value' => 'decimal:2',
            'insurance_required' => 'boolean',
            'customs_clearance' => 'boolean',
            'door_to_door' => 'boolean',
            'pickup_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function selectedQuote(): HasOne
    {
        return $this->hasOne(Quote::class)->where('is_selected', true);
    }

    public function calculateTotals(): void
    {
        $items = $this->items;

        $this->total_weight = $items->sum(function ($item) {
            return $item->weight * $item->quantity;
        });

        $this->total_volume = $items->sum(function ($item) {
            return ($item->length * $item->width * $item->height / 1000000) * $item->quantity; // cm³ to m³
        });

        $this->save();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isQuoted(): bool
    {
        return $this->status === 'quoted';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }
}
