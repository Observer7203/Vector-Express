<?php

namespace App\Services\Carriers;

class TrackingStatus
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $locationCity = null,
        public readonly ?string $locationCountry = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?string $description = null,
        public readonly ?\DateTimeInterface $timestamp = null,
        public readonly array $events = [],
    ) {}

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function getLocation(): string
    {
        $parts = array_filter([$this->locationCity, $this->locationCountry]);
        return implode(', ', $parts);
    }
}
