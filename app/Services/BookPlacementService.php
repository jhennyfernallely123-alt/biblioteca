<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;

class BookPlacementService
{
    public function nextPosition(int $shelfId): int
    {
        $max = Book::where('shelf_id', $shelfId)->max('position');

        return $max === null ? 0 : (int) $max + 1;
    }

    public function nextBand(User $user): array
    {
        $last = $user->books()->orderByDesc('band')->first(['band']);
        $band = $last?->band ?? 0;
        $count = $user->books()->where('band', $band)->count();

        if ($count >= Book::BOOKS_PER_BAND) {
            return ['band' => $band + 1, 'position' => 0];
        }

        return ['band' => $band, 'position' => $count];
    }
}
