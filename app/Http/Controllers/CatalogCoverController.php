<?php

namespace App\Http\Controllers;

use App\Models\CatalogBook;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogCoverController extends Controller
{
    public function __invoke(CatalogBook $catalogBook): StreamedResponse
    {
        abort_unless($catalogBook->cover_path, 404);
        abort_unless(Storage::disk('public')->exists($catalogBook->cover_path), 404);

        return Storage::disk('public')->response($catalogBook->cover_path);
    }
}
