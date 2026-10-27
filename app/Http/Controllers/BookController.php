<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Services\CoverService;
use App\Services\Isbn;
use App\Services\TranslationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function __construct(
        private CoverService $covers,
        private TranslationService $translator,
    ) {}

    public function create(Request $request): Response
    {
        return Inertia::render('Books/Create', [
            'shelves' => $this->shelvesFor($request),
        ]);
    }

    public function edit(Request $request, Book $book): Response
    {
        $this->authorize('update', $book);

        return Inertia::render('Books/Edit', [
            'book' => $book,
            'shelves' => $this->shelvesFor($request),
        ]);
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $this->ensureShelfOwnership($request, $request->integer('shelf_id'));

        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        unset($data['cover'], $data['cover_url']);

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $this->covers->storeUploaded($request->file('cover'));
        } elseif ($request->filled('cover_url')) {
            $data['cover_path'] = $this->covers->storeFromUrl($request->input('cover_url'));
        }

        $data['isbn'] = $request->filled('isbn')
            ? Isbn::normalize($request->input('isbn'))
            : null;

        $data['position'] = $this->nextPosition((int) $data['shelf_id']);

        $request->user()->books()->create($data);

        return redirect()->route('library.index');
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $data = $request->validated();

        if (isset($data['shelf_id'])) {
            $this->ensureShelfOwnership($request, $data['shelf_id']);
        }

        unset($data['cover'], $data['cover_url']);

        $oldCover = $book->cover_path;

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $this->covers->storeUploaded($request->file('cover'));
        } elseif ($request->filled('cover_url')) {
            $data['cover_path'] = $this->covers->storeFromUrl($request->input('cover_url'));
        }

        $data['isbn'] = $request->filled('isbn')
            ? Isbn::normalize($request->input('isbn'))
            : null;

        $oldShelfId = (int) $book->shelf_id;
        $shelfChanged = isset($data['shelf_id']) && (int) $data['shelf_id'] !== $oldShelfId;

        if ($shelfChanged) {
            $data['position'] = $this->nextPosition((int) $data['shelf_id']);
        }

        $book->update($data);

        if ($shelfChanged) {
            $this->resequenceShelf($oldShelfId);
            $this->resequenceShelf((int) $book->shelf_id);
        }

        if ($oldCover && $book->cover_path !== $oldCover) {
            $this->covers->delete($oldCover);
        }

        return back();
    }

    public function move(Request $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $data = $request->validate([
            'shelf_id' => ['required', 'integer', 'exists:shelves,id'],
        ]);

        $this->ensureShelfOwnership($request, $data['shelf_id']);

        $sourceShelfId = (int) $book->shelf_id;
        $targetShelfId = (int) $data['shelf_id'];

        DB::transaction(function () use ($book, $sourceShelfId, $targetShelfId) {
            $book->update([
                'shelf_id' => $targetShelfId,
                'position' => $this->nextPosition($targetShelfId),
            ]);

            $this->resequenceShelf($sourceShelfId);
            $this->resequenceShelf($targetShelfId);
        });

        return back();
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shelf_id' => ['required', 'integer'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $this->ensureShelfOwnership($request, $data['shelf_id']);

        $shelf = $request->user()->shelves()->findOrFail($data['shelf_id']);
        $expected = $shelf->books()->pluck('id')->sort()->values()->all();
        $submitted = collect($data['ids'])->sort()->values()->all();

        if ($expected !== $submitted) {
            throw ValidationException::withMessages([
                'ids' => __('ui.book_reorder_error'),
            ]);
        }

        DB::transaction(function () use ($data, $shelf) {
            foreach ($data['ids'] as $position => $id) {
                Book::where('id', $id)
                    ->where('shelf_id', $shelf->id)
                    ->update(['position' => $position]);
            }
        });

        return back();
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $this->covers->delete($book->cover_path);
        $book->delete();

        return back();
    }

    private function nextPosition(int $shelfId): int
    {
        $max = Book::where('shelf_id', $shelfId)->max('position');

        return $max === null ? 0 : (int) $max + 1;
    }

    private function resequenceShelf(int $shelfId): void
    {
        Book::where('shelf_id', $shelfId)
            ->orderBy('position')
            ->orderBy('title')
            ->orderBy('id')
            ->get(['id'])
            ->each(fn (Book $book, int $position) => $book->update(['position' => $position]));
    }

    private function ensureShelfOwnership(Request $request, int $shelfId): void
    {
        abort_unless(
            $request->user()->shelves()->whereKey($shelfId)->exists(),
            403
        );
    }

    private function shelvesFor(Request $request): array
    {
        return $request->user()->shelves()
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
