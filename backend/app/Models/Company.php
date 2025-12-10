<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'inn',
        'legal_address',
        'actual_address',
        'phone',
        'email',
        'website',
        'logo',
        'type',
        'rating',
        'rating_count',
        'verified',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'verified_at' => 'datetime',
            'rating' => 'decimal:2',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function carrier(): HasOne
    {
        return $this->hasOne(Carrier::class);
    }

    public function isShipper(): bool
    {
        return $this->type === 'shipper';
    }

    public function isCarrier(): bool
    {
        return $this->type === 'carrier';
    }

    public function updateRating(): void
    {
        $reviews = Review::whereHas('carrier', function ($query) {
            $query->where('company_id', $this->id);
        })->get();

        if ($reviews->count() > 0) {
            $this->rating = $reviews->avg('rating');
            $this->rating_count = $reviews->count();
            $this->save();
        }
    }
}
