<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLiteraryUniverseRequest;
use App\Http\Requests\UpdateLiteraryUniverseRequest;
use App\Models\Book;
use App\Models\LiteraryUniverse;
use App\Models\LiteraryUniverseBook;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LiteraryUniverseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LiteraryUniverse::class);

        $masterIds = User::query()->where('is_master', true)->pluck('id');

        return Inertia::render('LiteraryUniverses/Index', [
            'canManage' => $request->user()->isMaster(),
            'universes' => LiteraryUniverse::query()
                ->where(function ($query) use ($masterIds, $request) {
                    $query->whereIn('user_id', $masterIds)
                        ->orWhere('user_id', $request->user()->id);
                })
                ->withCount('books')
                ->with('books')
                ->orderBy('name')
                ->get()
                ->map(function (LiteraryUniverse $universe) {
                    $first = $universe->books->first();

                    return [
                        'id' => $universe->id,
                        'name' => $universe->name,
                        'description' => $universe->description,
                        'type' => $universe->type,
                        'type_label' => __($universe::TYPES[$universe->type] ?? 'ui.universe_type_other'),
                        'total_books' => $universe->books_count,
                        'cover_url' => $first?->cover_path ? route('covers.show', $first, false) : null,
                        'books' => $universe->books
                            ->map(fn (Book $book) => [
                                'id' => $book->id,
                                'reading_order' => $book->pivot->reading_order,
                                'title' => $book->title,
                                'author' => $book->author,
                                'rating' => $book->rating,
                                'cover_url' => $book->cover_path
                                    ? route('covers.show', $book, false)
                                    : null,
                            ])
                            ->values()
                            ->all(),
                    ];
                }),
        ]);
    }

    public function show(Request $request, LiteraryUniverse $universe): Response
    {
        $this->authorize('view', $universe);

        $universe->load('universeBooks.book');

        return Inertia::render('LiteraryUniverses/Show', [
            'canManage' => request()->user()->isMaster(),
            'openAdd' => (bool) $request->session()->pull('openAdd'),
            'universe' => [
                'id' => $universe->id,
                'name' => $universe->name,
                'description' => $universe->description,
                'type' => $universe->type,
                'type_label' => __($universe::TYPES[$universe->type] ?? 'ui.universe_type_other'),
                'total_books' => $universe->universeBooks->count(),
            ],
            'books' => $universe->universeBooks->map(fn (LiteraryUniverseBook $ub) => [
                'id' => $ub->book->id,
                'title' => $ub->book->title,
                'author' => $ub->book->author,
                'reading_order' => $ub->reading_order,
                'cover_url' => $ub->book->cover_path
                    ? route('covers.show', $ub->book)
                    : null,
                'isbn' => $ub->book->isbn,
                'publisher' => $ub->book->publisher,
                'published_year' => $ub->book->published_year,
                'reading_status' => $ub->book->readingStatus(),
                'reading_progress' => $ub->book->readingProgress(),
            ])->sortBy('reading_order')->values()->all(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', LiteraryUniverse::class);

        return Inertia::render('LiteraryUniverses/Create', [
            'types' => collect(LiteraryUniverse::TYPES)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => __($label),
            ])->values()->all(),
        ]);
    }

    public function store(StoreLiteraryUniverseRequest $request): RedirectResponse
    {
        $this->authorize('create', LiteraryUniverse::class);

        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $universe = LiteraryUniverse::create($data);

        $redirect = redirect()->route('literary-universes.show', $universe);

        if ($request->boolean('add')) {
            $redirect->with('openAdd', true);
        }

        return $redirect->with('success', __('ui.universe_created'));
    }

    public function edit(LiteraryUniverse $universe): Response
    {
        $this->authorize('update', $universe);

        return Inertia::render('LiteraryUniverses/Edit', [
            'universe' => [
                'id' => $universe->id,
                'name' => $universe->name,
                'description' => $universe->description,
                'type' => $universe->type,
            ],
            'types' => collect(LiteraryUniverse::TYPES)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => __($label),
            ])->values()->all(),
        ]);
    }

    public function update(UpdateLiteraryUniverseRequest $request, LiteraryUniverse $universe): RedirectResponse
    {
        $this->authorize('update', $universe);

        $universe->update($request->validated());

        return redirect()->route('literary-universes.index')
            ->with('success', __('ui.universe_updated'));
    }

    public function destroy(LiteraryUniverse $universe): RedirectResponse
    {
        $this->authorize('delete', $universe);

        $universe->delete();

        return redirect()->route('literary-universes.index')
            ->with('success', __('ui.universe_deleted'));
    }
}
