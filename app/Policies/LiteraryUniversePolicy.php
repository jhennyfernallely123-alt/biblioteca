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
        return $user->isMaster();
    }

    public function view(User $user, LiteraryUniverse $universe): bool
    {
        return $universe->user->isMaster() || $user->id === $universe->user_id;
    }

    public function update(User $user, LiteraryUniverse $universe): bool
    {
        return $user->isMaster();
    }

    public function delete(User $user, LiteraryUniverse $universe): bool
    {
        return $user->isMaster();
    }

    public function manageBooks(User $user, LiteraryUniverse $universe): bool
    {
        return $user->isMaster();
    }
}
