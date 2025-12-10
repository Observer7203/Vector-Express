<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierZonePostalCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'carrier_zone_id',
        'postal_code_prefix',
        'postal_code_from',
        'postal_code_to',
        'city',
        'region',
        'is_remote_area',
    ];

    protected function casts(): array
    {
        return [
            'is_remote_area' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(CarrierZone::class, 'carrier_zone_id');
    }

    public function matchesPostalCode(string $postalCode): bool
    {
        if ($this->postal_code_prefix && str_starts_with($postalCode, $this->postal_code_prefix)) {
            return true;
        }

        if ($this->postal_code_from && $this->postal_code_to) {
            return $postalCode >= $this->postal_code_from && $postalCode <= $this->postal_code_to;
        }

        return false;
    }
}
