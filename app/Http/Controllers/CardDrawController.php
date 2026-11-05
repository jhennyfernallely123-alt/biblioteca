<?php

namespace App\Http\Controllers;

use App\Services\CardDeckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardDrawController extends Controller
{
    public function store(Request $request, CardDeckService $deck): JsonResponse
    {
        $data = $request->validate([
            'ball' => ['required', 'integer', 'min:0'],
        ]);

        return response()->json($deck->draw($request->user(), $data['ball']));
    }
}
