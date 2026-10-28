<?php

namespace App\Http\Controllers;

use App\Services\BookLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookLookupController extends Controller
{
    public function __invoke(Request $request, BookLookupService $lookup): JsonResponse
    {
        $data = $request->validate([
            'isbn' => ['required_without:q', 'string', 'max:20'],
            'q' => ['required_without:isbn', 'string', 'min:3', 'max:120'],
        ]);

        $results = isset($data['isbn'])
            ? $lookup->byIsbn($data['isbn'])
            : $lookup->search($data['q']);

        return response()->json(['results' => $results]);
    }
}
