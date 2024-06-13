<?php

namespace App\Listeners;

use App\Events\UserVerified;
use App\Models\Conversation;
use App\Models\User;

class CreateDefaultConversation
{
    public function handle(UserVerified $event)
    {
        $user = $event->user;

        $admin = User::where('role', 'admin')->first();

        if ($admin) {
            Conversation::firstOrCreate([
                'user1_id' => $user->id,
                'user2_id' => $admin->id,
            ]);
        }
    }
}
