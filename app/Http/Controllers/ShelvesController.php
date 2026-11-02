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
            ->with(['books', 'decorations'])
            ->get()
            ->each(function ($shelf) use ($translator) {
                $shelf->setAttribute(
                    'display_name',
                    app()->getLocale() === 'es'
                        ? $shelf->name
                        : ($translator->translateText($shelf->name, app()->getLocale(), 'es') ?? $shelf->name)
                );
            });

        return Inertia::render('Shelves/Index', [
            'shelves' => $shelves,
        ]);
    }
}
