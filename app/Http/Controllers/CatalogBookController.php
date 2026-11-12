<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCatalogBookRequest;
use App\Http\Requests\UpdateCatalogBookRequest;
use App\Models\CatalogBook;
use App\Services\BookPlacementService;
use App\Services\CoverService;
use App\Services\ShelfDisplayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CatalogBookController extends Controller
{
    public function __construct(
        private CoverService $covers,
        private BookPlacementService $placement,
        private ShelfDisplayService $shelves,
    ) {}

    public function index(): Response
    {
        $this->authorize('manage', CatalogBook::class);

        return Inertia::render('Catalog/Index', [
            'books' => CatalogBook::query()
                ->orderBy('title')
                ->get()
                ->map(fn (CatalogBook $book) => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'isbn' => $book->isbn,
                    'publisher' => $book->publisher,
                    'published_year' => $book->published_year,
                    'cover_url' => $book->cover_path
                        ? route('catalog-covers.show', $book, false)
                        : null,
                ])
                ->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('manage', CatalogBook::class);

        return Inertia::render('Catalog/Create', [
            'shelves' => $this->shelves->forUser($request->user()),
        ]);
    }

    public function store(StoreCatalogBookRequest $request): RedirectResponse
    {
        $this->authorize('manage', CatalogBook::class);

        $data = $request->validated();
        $shelfId = (int) $data['shelf_id'];

        $this->ensureShelfOwnership($request, $shelfId);

        $coverPath = null;
        $bookCoverPath = null;

        try {
            if ($request->hasFile('cover')) {
                $coverPath = $this->covers->storeUploaded($request->file('cover'));
            } elseif ($request->filled('cover_url')) {
                $coverPath = $this->covers->storeFromUrl($request->input('cover_url'));

                if ($coverPath === null) {
                    throw ValidationException::withMessages([
                        'cover_url' => __('ui.catalog_cover_error'),
                    ]);
                }
            }

            DB::transaction(function () use ($request, $data, $shelfId, $coverPath, &$bookCoverPath) {
                $catalogBook = CatalogBook::create([
                    'created_by' => $request->user()->id,
                    'title' => $data['title'],
                    'author' => $data['author'] ?? null,
                    'isbn' => $data['isbn'] ?? null,
                    'publisher' => $data['publisher'] ?? null,
                    'published_year' => $data['published_year'] ?? null,
                    'cover_path' => $coverPath,
                ]);

                $bookCoverPath = $catalogBook->cover_path
                    ? $this->covers->copy($catalogBook->cover_path)
                    : null;

                $nextBand = $this->placement->nextBand($request->user());

                $request->user()->books()->create([
                    'shelf_id' => $shelfId,
                    'position' => $this->placement->nextPosition($shelfId),
                    'band' => $nextBand['band'],
                    'band_position' => $nextBand['position'],
                    'view_mode' => $request->user()->default_view_mode ?? 'covers',
                    'title' => $catalogBook->title,
                    'author' => $catalogBook->author,
                    'isbn' => $catalogBook->isbn,
                    'publisher' => $catalogBook->publisher,
                    'published_year' => $catalogBook->published_year,
                    'cover_path' => $bookCoverPath,
                    'catalog_book_id' => $catalogBook->id,
                ]);
            });
        } catch (\Throwable $e) {
            $this->covers->delete($coverPath);
            $this->covers->delete($bookCoverPath);

            throw $e;
        }

        return redirect()->route('catalog.index')
            ->with('success', __('ui.catalog_created'));
    }

    public function edit(CatalogBook $catalogBook): Response
    {
        $this->authorize('manage', CatalogBook::class);

        return Inertia::render('Catalog/Edit', [
            'book' => [
                'id' => $catalogBook->id,
                'title' => $catalogBook->title,
                'author' => $catalogBook->author,
                'isbn' => $catalogBook->isbn,
                'publisher' => $catalogBook->publisher,
                'published_year' => $catalogBook->published_year,
                'cover_url' => $catalogBook->cover_path
                    ? route('catalog-covers.show', $catalogBook, false)
                    : null,
            ],
        ]);
    }

    public function update(UpdateCatalogBookRequest $request, CatalogBook $catalogBook): RedirectResponse
    {
        $this->authorize('manage', CatalogBook::class);

        $data = $request->validated();
        unset($data['cover'], $data['cover_url']);

        $oldCover = $catalogBook->cover_path;
        $newCover = null;

        try {
            if ($request->hasFile('cover')) {
                $newCover = $this->covers->storeUploaded($request->file('cover'));
            } elseif ($request->filled('cover_url')) {
                $newCover = $this->covers->storeFromUrl($request->input('cover_url'));

                if ($newCover === null) {
                    throw ValidationException::withMessages([
                        'cover_url' => __('ui.catalog_cover_error'),
                    ]);
                }
            }

            if ($newCover !== null) {
                $data['cover_path'] = $newCover;
            }

            $catalogBook->update($data);

            if ($oldCover && $newCover !== null && $newCover !== $oldCover) {
                $this->covers->delete($oldCover);
            }
        } catch (\Throwable $e) {
            $this->covers->delete($newCover);

            throw $e;
        }

        return redirect()->route('catalog.index')
            ->with('success', __('ui.catalog_updated'));
    }

    public function destroy(CatalogBook $catalogBook): RedirectResponse
    {
        $this->authorize('manage', CatalogBook::class);

        $cover = $catalogBook->cover_path;
        $catalogBook->delete();
        $this->covers->delete($cover);

        return redirect()->route('catalog.index')
            ->with('success', __('ui.catalog_deleted'));
    }

    private function ensureShelfOwnership(Request $request, int $shelfId): void
    {
        abort_unless(
            $request->user()->shelves()->whereKey($shelfId)->exists(),
            403
        );
    }
}
