<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\LiteraryUniverse;
use App\Models\LiteraryUniverseBook;
use App\Services\BookPlacementService;
use App\Services\CoverService;
use App\Services\DefaultShelvesService;
use App\Services\Isbn;
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

    public function fromLookup(Request $request, BookPlacementService $placement, CoverService $covers, LiteraryUniverse $universe): JsonResponse
    {
        $this->authorize('manageBooks', $universe);

        $data = $request->validate([
            'books' => ['required', 'array', 'min:1'],
            'books.*' => ['array'],
            'books.*.title' => ['required', 'string', 'max:255'],
            'books.*.author' => ['nullable', 'string', 'max:255'],
            'books.*.isbn' => ['nullable', 'string', 'max:20'],
            'books.*.publisher' => ['nullable', 'string', 'max:150'],
            'books.*.published_year' => ['nullable', 'integer', 'min:0', 'max:2100'],
            'books.*.cover_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $user = $request->user();
        $shelf = $user->shelves()->first();

        if (! $shelf) {
            app(DefaultShelvesService::class)->createFor($user);
            $shelf = $user->shelves()->first();
        }

        $linked = $universe->universeBooks()->pluck('book_id')->all();

        $added = DB::transaction(function () use ($request, $universe, $user, $shelf, $placement, $covers, $linked): array {
            $added = [];
            $position = (int) $universe->books()->max('reading_order') + 1;
            $nextBand = $placement->nextBand($user);
            $band = $nextBand['band'];
            $bandPosition = $nextBand['position'];

            foreach ($request->input('books') as $input) {
                $title = trim((string) ($input['title'] ?? ''));
                $author = trim((string) ($input['author'] ?? ''));
                $isbn = ! empty($input['isbn'] ?? null) ? Isbn::normalize($input['isbn']) : null;

                if ($title === '') {
                    continue;
                }

                $book = null;

                if ($isbn) {
                    $book = $user->books()->where('isbn', $isbn)->first();
                }

                if (! $book) {
                    $book = $user->books()
                        ->where('title', $title)
                        ->when($author !== '', fn ($q) => $q->where('author', $author))
                        ->first();
                }

                if (! $book) {
                    $coverPath = null;

                    if (! empty($input['cover_url'])) {
                        try {
                            $coverPath = $covers->storeFromUrl($input['cover_url']);
                        } catch (\Throwable $e) {
                            $coverPath = null;
                        }
                    }

                    $book = $user->books()->create([
                        'shelf_id' => $shelf->id,
                        'position' => $placement->nextPosition($shelf->id),
                        'band' => $band,
                        'band_position' => $bandPosition,
                        'view_mode' => $user->default_view_mode ?? 'covers',
                        'title' => $title,
                        'author' => $author === '' ? null : $author,
                        'isbn' => $isbn,
                        'publisher' => $input['publisher'] ?? null,
                        'published_year' => $input['published_year'] ?? null,
                        'cover_path' => $coverPath,
                    ]);

                    if (++$bandPosition >= Book::BOOKS_PER_BAND) {
                        $band++;
                        $bandPosition = 0;
                    }
                }

                if (in_array($book->id, $linked)) {
                    continue;
                }

                $universe->books()->attach($book->id, ['reading_order' => $position]);

                $linked[] = $book->id;

                $added[] = [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'cover_url' => $book->cover_path ? route('covers.show', $book, false) : null,
                    'reading_order' => $position,
                ];

                $position++;
            }

            return $added;
        });

        return response()->json([
            'added' => $added,
            'count' => count($added),
        ], 201);
    }
}
