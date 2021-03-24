<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function updating(User $user): void
    {
        if ($user->block_reason === null) {
            $user->blocked_at = null;
        } else {
            $user->blocked_at = now();
        }
    }
}
