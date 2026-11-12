<?php

namespace App\Services;

use App\Models\User;

class ShelfDisplayService
{
    public function __construct(
        private TranslationService $translator,
    ) {}

    public function forUser(User $user): array
    {
        return $user->shelves()
            ->orderBy('position')
            ->get(['id', 'name'])
            ->map(function ($shelf) {
                $shelf->display_name = app()->getLocale() === 'es'
                    ? $shelf->name
                    : ($this->translator->translateText($shelf->name, app()->getLocale(), 'es') ?? $shelf->name);

                return $shelf;
            })
            ->all();
    }
}
