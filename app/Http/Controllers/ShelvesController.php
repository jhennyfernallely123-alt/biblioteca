<?php

namespace App\Http\Controllers;

use App\Services\TranslationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShelvesController extends Controller
{
    public function index(Request $request, TranslationService $translator): Response
    {
        $shelves = $request->user()
            ->shelves()
            ->with([
                'books' => fn ($query) => $query
                    ->withSum('readingSessions', 'minutes')
                    ->withCount('readingSessions'),
                'decorations',
            ])
            ->get()
            ->each(function ($shelf) use ($translator) {
                $shelf->setAttribute(
                    'display_name',
                    app()->getLocale() === 'es'
                        ? $shelf->name
                        : ($translator->translateText($shelf->name, app()->getLocale(), 'es') ?? $shelf->name)
                );
            });

        $shelves->each(function ($shelf) {
            $shelf->books->each(function ($book) {
                $book->reading_minutes = (int) ($book->reading_sessions_sum_minutes ?? 0);
                $book->progress = $book->readingProgress();
                $book->reading_status = $book->readingStatus();
            });
        });

        $readingBooks = $shelves
            ->flatMap->books
            ->filter(fn ($book) => $book->reading_status === 'reading')
            ->values();

        return Inertia::render('Shelves/Index', [
            'shelves' => $shelves,
            'readingBooks' => $readingBooks,
        ]);
    }
}
