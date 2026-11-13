<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\LiteraryUniverse;
use App\Models\LiteraryUniverseBook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LiteraryUniverseBookController extends Controller
{
    public function store(Request $request, LiteraryUniverse $universe): RedirectResponse
    {
        $this->authorize('manageBooks', $universe);

        $data = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'reading_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $book = Book::findOrFail($data['book_id']);

        $this->ensureBookOwnership($request, $book->id);

        if ($universe->books()->whereKey($book->id)->exists()) {
            throw ValidationException::withMessages([
                'book_id' => __('ui.universe_book_already_added'),
            ]);
        }

        $readingOrder = $data['reading_order'] ?? $universe->books()->max('reading_order') + 1;

        DB::transaction(function () use ($universe, $book, $readingOrder) {
            if ($readingOrder <= $universe->books()->max('reading_order')) {
                $universe->universeBooks()
                    ->where('reading_order', '>=', $readingOrder)
                    ->increment('reading_order');
            }

            LiteraryUniverseBook::create([
                'literary_universe_id' => $universe->id,
                'book_id' => $book->id,
                'reading_order' => $readingOrder,
            ]);
        });

        return back()->with('success', __('ui.universe_book_added'));
    }

    public function destroy(LiteraryUniverse $universe, Book $book): RedirectResponse
    {
        $this->authorize('manageBooks', $universe);

        $universe->books()->detach($book->id);

        $universe->universeBooks()
            ->where('reading_order', '>', $book->pivot->reading_order ?? 0)
            ->decrement('reading_order');

        return back()->with('success', __('ui.universe_book_removed'));
    }

    public function reorder(Request $request, LiteraryUniverse $universe): RedirectResponse
    {
        $this->authorize('manageBooks', $universe);

        $data = $request->validate([
            'orders' => ['required', 'array'],
            'orders.*' => ['required', 'integer', 'min:1'],
        ]);

        $universeBookIds = $universe->universeBooks()->pluck('id')->all();
        $submitted = collect($data['orders'])->sort()->values()->all();

        if ($universeBookIds !== $submitted) {
            throw ValidationException::withMessages([
                'orders' => __('ui.universe_reorder_error'),
            ]);
        }

        foreach ($data['orders'] as $position => $universeBookId) {
            LiteraryUniverseBook::whereKey($universeBookId)
                ->where('literary_universe_id', $universe->id)
                ->update(['reading_order' => $position]);
        }

        return back()->with('success', __('ui.universe_reordered'));
    }

    private function ensureBookOwnership(Request $request, int $bookId): void
    {
        abort_unless(
            $request->user()->books()->whereKey($bookId)->exists(),
            403
        );
    }
}
