<?php

namespace App\Policies;

use App\Models\LiteraryUniverse;
use App\Models\User;

class LiteraryUniversePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, LiteraryUniverse $universe): bool
    {
        return $user->id === $universe->user_id;
    }

    public function update(User $user, LiteraryUniverse $universe): bool
    {
        return $user->id === $universe->user_id;
    }

    public function delete(User $user, LiteraryUniverse $universe): bool
    {
        return $user->id === $universe->user_id;
    }

    public function manageBooks(User $user, LiteraryUniverse $universe): bool
    {
        return $user->id === $universe->user_id;
    }
}
