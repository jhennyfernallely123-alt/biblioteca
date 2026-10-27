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

        $book->update($data);

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

        $book->update(['shelf_id' => $data['shelf_id']]);

        return back();
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $this->covers->delete($book->cover_path);
        $book->delete();

        return back();
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
