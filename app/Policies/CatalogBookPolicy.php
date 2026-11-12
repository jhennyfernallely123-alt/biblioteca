<?php

namespace App\Policies;

use App\Models\CatalogBook;
use App\Models\User;

class CatalogBookPolicy
{
    public function manage(User $user, ?CatalogBook $catalogBook = null): bool
    {
        return $user->isMaster();
    }
}
