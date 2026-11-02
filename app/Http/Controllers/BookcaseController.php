<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookcaseController extends Controller
{
    public function move(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'book_id' => ['required', 'integer'],
            'band' => ['required', 'integer', 'min:0'],
            'position' => ['required', 'integer', 'min:0'],
        ]);

        $book = $request->user()->books()->findOrFail($data['book_id']);
        $bands = $this->bandsFor($request->user()->id);

        $this->remove($bands, $book->id);
        $this->insert($bands, $book, (int) $data['band'], (int) $data['position']);
        $this->cascade($bands, (int) $data['band']);

        DB::transaction(function () use ($bands) {
            foreach ($bands as $band => $group) {
                foreach ($group as $position => $item) {
                    DB::table('books')->where('id', $item->id)->update([
                        'band' => $band,
                        'band_position' => $position,
                    ]);
                }
            }
        });

        return back();
    }

    private function bandsFor(int $userId): array
    {
        $bands = [];

        foreach (Book::where('user_id', $userId)->get(['id', 'band', 'band_position']) as $book) {
            $bands[$book->band ?? 0][] = $book;
        }

        foreach ($bands as &$group) {
            usort($group, fn ($a, $b) => [$a->band_position, $a->id] <=> [$b->band_position, $b->id]);
        }

        unset($group);

        return $bands;
    }

    private function remove(array &$bands, int $bookId): void
    {
        foreach (array_keys($bands) as $band) {
            $bands[$band] = array_values(array_filter(
                $bands[$band],
                fn ($item) => $item->id !== $bookId
            ));

            if ($bands[$band] === []) {
                unset($bands[$band]);
            }
        }
    }

    private function insert(array &$bands, Book $book, int $band, int $position): void
    {
        $group = $bands[$band] ?? [];
        $position = max(0, min($position, count($group), Book::BOOKS_PER_BAND - 1));

        array_splice($group, $position, 0, [$book]);
        $bands[$band] = $group;
    }

    private function cascade(array &$bands, int $from): void
    {
        $max = max(array_keys($bands));

        for ($band = $from; $band <= $max; $band++) {
            if (! isset($bands[$band])) {
                continue;
            }

            while (count($bands[$band]) > Book::BOOKS_PER_BAND) {
                $overflow = array_pop($bands[$band]);
                $next = $band + 1;

                $bands[$next] = $bands[$next] ?? [];
                array_unshift($bands[$next], $overflow);
                $max = max($max, $next);
            }
        }
    }
}
