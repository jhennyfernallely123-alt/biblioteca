<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    public function index(Request $request): Response
    {
        $books = $request->user()
            ->shelves()
            ->orderBy('position')
            ->orderBy('id')
            ->with('books')
            ->get()
            ->flatMap->books
            ->values();

        return Inertia::render('Library/Index', [
            'books' => $books,
            'decorations' => $request->user()->decorations()->get(),
        ]);
    }
}
