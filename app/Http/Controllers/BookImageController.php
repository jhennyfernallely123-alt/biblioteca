<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookImage;
use App\Services\BookImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookImageController extends Controller
{
    public function __construct(
        private BookImageService $images,
    ) {}

    public function show(BookImage $image): StreamedResponse
    {
        $this->authorize('view', $image->book);

        abort_unless(Storage::disk('public')->exists($image->path), 404);

        return Storage::disk('public')->response($image->path);
    }

    public function store(Request $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'slot' => ['required', 'integer', 'min:0', 'max:2'],
        ]);

        $existing = $book->images()->where('slot', $data['slot'])->first();

        if ($existing) {
            $this->images->delete($existing->path);
            $existing->delete();
        }

        $book->images()->create([
            'slot' => $data['slot'],
            'path' => $this->images->storeUploaded($data['image']),
        ]);

        return back();
    }

    public function destroy(BookImage $image): RedirectResponse
    {
        $this->authorize('update', $image->book);

        $this->images->delete($image->path);
        $image->delete();

        return back();
    }
}
