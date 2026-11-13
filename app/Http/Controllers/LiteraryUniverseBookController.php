<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\LiteraryUniverse;
use App\Models\LiteraryUniverseBook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LiteraryUniverseBookController extends Controller
{
    public function available(Request $request, LiteraryUniverse $universe): JsonResponse
    {
        $this->authorize('manageBooks', $universe);

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $linked = $universe->universeBooks()
            ->pluck('reading_order', 'book_id')
            ->mapWithKeys(fn ($order, $bookId) => [(int) $bookId => (int) $order]);

        $books = $request->user()
            ->books()
            ->when($data['q'] ?? null, function ($query, string $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('title', 'like', "%{$term}%")
                        ->orWhere('author', 'like', "%{$term}%");
                });
            })
            ->orderBy('title')
            ->limit(200)
            ->get(['id', 'title', 'author', 'cover_path', 'rating'])
            ->map(fn (Book $book) => [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'cover_url' => $book->cover_path ? route('covers.show', $book, false) : null,
                'rating' => $book->rating,
                'in_collection' => $linked->has($book->id),
                'reading_order' => $linked->get($book->id),
            ]);

        return response()->json([
            'books' => $books,
            'total' => $books->count(),
        ]);
    }

    public function store(Request $request, LiteraryUniverse $universe): RedirectResponse
    {
        $this->authorize('manageBooks', $universe);

        $data = $request->validate([
            'book_id' => ['required_without:book_ids', 'integer', 'exists:books,id'],
            'book_ids' => ['required_without:book_id', 'array', 'min:1'],
            'book_ids.*' => ['integer', 'distinct', 'exists:books,id'],
            'reading_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $bookIds = array_map('intval', $data['book_ids'] ?? [$data['book_id']]);

        $owned = $request->user()->books()->whereKey($bookIds)->count();

        abort_unless($owned === count($bookIds), 403);

        $alreadyLinked = $universe->universeBooks()
            ->whereIn('book_id', $bookIds)
            ->exists();

        if ($alreadyLinked) {
            $field = isset($data['book_id']) ? 'book_id' : 'book_ids';

            throw ValidationException::withMessages([
                $field => __('ui.universe_book_already_added'),
            ]);
        }

        $count = DB::transaction(function () use ($universe, $bookIds, $data) {
            $position = (int) $universe->books()->max('reading_order') + 1;

            if (isset($data['book_id'], $data['reading_order'])) {
                $position = (int) $data['reading_order'];

                $universe->universeBooks()
                    ->where('reading_order', '>=', $position)
                    ->increment('reading_order');
            }

            foreach ($bookIds as $bookId) {
                LiteraryUniverseBook::create([
                    'literary_universe_id' => $universe->id,
                    'book_id' => $bookId,
                    'reading_order' => $position++,
                ]);
            }

            return count($bookIds);
        });

        if (! isset($data['book_id'])) {
            return back()->with('success', trans_choice('ui.universe_books_added', $count, ['count' => $count]));
        }

        return back()->with('success', __('ui.universe_book_added'));
    }

    public function destroy(LiteraryUniverse $universe, Book $book): RedirectResponse
    {
        $this->authorize('manageBooks', $universe);

        $entry = $universe->universeBooks()->where('book_id', $book->id)->first();

        if (! $entry) {
            return back()->withErrors([
                'book_id' => __('ui.universe_book_not_in_universe'),
            ]);
        }

        DB::transaction(function () use ($universe, $entry, $book) {
            $universe->books()->detach($book->id);

            $universe->universeBooks()
                ->where('reading_order', '>', $entry->reading_order)
                ->decrement('reading_order');
        });

        return back()->with('success', __('ui.universe_book_removed'));
    }

    public function reorder(Request $request, LiteraryUniverse $universe): RedirectResponse
    {
        $this->authorize('manageBooks', $universe);

        $data = $request->validate([
            'orders' => ['required', 'array'],
            'orders.*' => ['required', 'integer', 'min:1'],
        ]);

        $bookIds = $universe->universeBooks()
            ->pluck('book_id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $submittedIds = collect(array_keys($data['orders']))
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $positions = collect($data['orders'])
            ->map(fn ($position) => (int) $position)
            ->sort()
            ->values()
            ->all();

        if ($submittedIds !== $bookIds || $positions !== range(1, count($bookIds))) {
            throw ValidationException::withMessages([
                'orders' => __('ui.universe_reorder_error'),
            ]);
        }

        DB::transaction(function () use ($data, $universe) {
            foreach ($data['orders'] as $bookId => $position) {
                $universe->universeBooks()
                    ->where('book_id', (int) $bookId)
                    ->update(['reading_order' => (int) $position]);
            }
        });

        return back()->with('success', __('ui.universe_reordered'));
    }
}
