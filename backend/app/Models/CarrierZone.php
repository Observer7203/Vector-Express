<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarrierZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'carrier_id',
        'zone_code',
        'zone_name',
        'country_code',
        'description',
    ];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function postalCodes(): HasMany
    {
        return $this->hasMany(CarrierZonePostalCode::class);
    }

    public function originRateCards(): HasMany
    {
        return $this->hasMany(CarrierRateCard::class, 'origin_zone_id');
    }

    public function destinationRateCards(): HasMany
    {
        return $this->hasMany(CarrierRateCard::class, 'destination_zone_id');
    }
}
