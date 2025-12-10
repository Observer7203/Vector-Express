<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        // Owner can view
        if ($user->id === $order->user_id) {
            return true;
        }

        // Carrier can view
        if ($user->isCarrier() && $user->company?->carrier?->id === $order->carrier_id) {
            return true;
        }

        // Admin can view
        return $user->isAdmin();
    }

    public function update(User $user, Order $order): bool
    {
        // Only owner can update basic details
        return $user->id === $order->user_id;
    }

    public function cancel(User $user, Order $order): bool
    {
        // Owner can cancel
        if ($user->id === $order->user_id) {
            return true;
        }

        // Carrier can cancel
        if ($user->isCarrier() && $user->company?->carrier?->id === $order->carrier_id) {
            return true;
        }

        return $user->isAdmin();
    }

    public function confirm(User $user, Order $order): bool
    {
        // Only carrier can confirm
        if (!$user->isCarrier()) {
            return false;
        }

        return $user->company?->carrier?->id === $order->carrier_id;
    }

    public function updateStatus(User $user, Order $order): bool
    {
        // Only carrier can update status
        if (!$user->isCarrier()) {
            return false;
        }

        return $user->company?->carrier?->id === $order->carrier_id;
    }
}
