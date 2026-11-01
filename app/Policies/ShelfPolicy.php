<?php

namespace App\Policies;

use App\Models\Shelf;
use App\Models\User;

class ShelfPolicy
{
    public function update(User $user, Shelf $shelf): bool
    {
        return $user->id === $shelf->user_id && ! $shelf->is_fixed;
    }

    public function delete(User $user, Shelf $shelf): bool
    {
        return $user->id === $shelf->user_id && ! $shelf->is_fixed;
    }
}
