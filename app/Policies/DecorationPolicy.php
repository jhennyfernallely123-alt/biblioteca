<?php

namespace App\Policies;

use App\Models\Decoration;
use App\Models\User;

class DecorationPolicy
{
    public function update(User $user, Decoration $decoration): bool
    {
        return $user->id === $decoration->user_id;
    }

    public function delete(User $user, Decoration $decoration): bool
    {
        return $user->id === $decoration->user_id;
    }
}
