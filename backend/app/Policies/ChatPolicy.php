<?php

namespace App\Policies;

use App\Models\Chat;
use App\Models\User;

class ChatPolicy
{
    public function view(User $user, Chat $chat): bool
    {
        // User must be a participant
        return $chat->participants()->where('user_id', $user->id)->exists()
            || $user->isAdmin();
    }

    public function sendMessage(User $user, Chat $chat): bool
    {
        // User must be a participant
        return $chat->participants()->where('user_id', $user->id)->exists();
    }
}
