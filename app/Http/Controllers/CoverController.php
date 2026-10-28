<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CoverController extends Controller
{
    public function __invoke(Book $book): StreamedResponse
    {
        $this->authorize('view', $book);

        abort_unless($book->cover_path, 404);

        abort_unless(Storage::disk('public')->exists($book->cover_path), 404);

        return Storage::disk('public')->response($book->cover_path);
    }
}
