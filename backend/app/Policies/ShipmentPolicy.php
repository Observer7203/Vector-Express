<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function view(User $user, Shipment $shipment): bool
    {
        return $user->id === $shipment->user_id || $user->isAdmin();
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return $user->id === $shipment->user_id && $shipment->isDraft();
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->id === $shipment->user_id && $shipment->isDraft();
    }

    public function calculate(User $user, Shipment $shipment): bool
    {
        return $user->id === $shipment->user_id && $shipment->isDraft();
    }
}
