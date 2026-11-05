<?php

namespace App\Http\Controllers;

use App\Services\CardCatalogService;
use App\Services\CardDeckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CardController extends Controller
{
    public function index(
        Request $request,
        CardCatalogService $catalog,
        CardDeckService $deck,
    ): Response {
        $user = $request->user();
        $pool = $catalog->poolFor($user);
        $owned = $user->userCards()->whereIn('card_key', $pool)->get()->keyBy('card_key');
        $drawn = config('cards.allow_multiple_draws') ? null : $deck->drawnToday($user);

        $cards = collect($pool)->map(function (string $key) use ($catalog, $owned) {
            $card = $owned->get($key);

            return $catalog->metadata($key) + [
                'unlocked' => $card !== null,
                'copies' => (int) ($card->copies ?? 0),
            ];
        })->values();

        $offers = collect($deck->offersFor($user))->values()->map(function (string $key, int $index) use ($catalog) {
            $meta = $catalog->metadata($key);

            return [
                'index' => $index,
                'family' => $meta['family'],
                'familyLabel' => $meta['familyLabel'],
                'familyEmblem' => $meta['familyEmblem'],
            ];
        });

        return Inertia::render('Cards/Index', [
            'cards' => $cards,
            'offers' => $offers,
            'drawn' => $drawn ? [
                'card' => $catalog->metadata($drawn->card_key),
                'duplicate' => $drawn->duplicate,
                'copies' => (int) ($owned->get($drawn->card_key)?->copies ?? 0),
                'inkEarned' => (int) $drawn->ink_earned,
            ] : null,
            'ink' => (int) ($user->wallet?->ink ?? 0),
            'progress' => [
                'unlocked' => $owned->count(),
                'total' => count($pool),
            ],
            'resetAt' => $deck->nextResetAt()->toIso8601String(),
            'dailyBalls' => (int) config('cards.daily_balls'),
        ]);
    }

    public function album(Request $request, CardCatalogService $catalog): Response
    {
        $user = $request->user();
        $pool = $catalog->poolFor($user);
        $owned = $user->userCards()->whereIn('card_key', $pool)->get()->keyBy('card_key');

        $cards = collect($pool)->map(function (string $key) use ($catalog, $owned) {
            $card = $owned->get($key);

            return $catalog->metadata($key) + [
                'unlocked' => $card !== null,
                'copies' => (int) ($card->copies ?? 0),
            ];
        })->values();

        return Inertia::render('Cards/Album', [
            'cards' => $cards,
            'progress' => [
                'unlocked' => $owned->count(),
                'total' => count($pool),
            ],
            'ink' => (int) ($user->wallet?->ink ?? 0),
        ]);
    }

    public function show(Request $request, string $card, CardCatalogService $catalog): JsonResponse
    {
        abort_unless($catalog->exists($card), 404);

        $user = $request->user();

        abort_unless($catalog->inPool($user, $card), 403);

        $owned = $user->userCards()->where('card_key', $card)->first();

        abort_if($owned === null, 403);

        $letter = $catalog->letter($card, app()->getLocale());

        abort_if($letter === null, 404);

        return response()->json([
            'card' => $catalog->metadata($card) + ['letter' => $letter],
            'duplicate' => $owned->copies > 1,
            'copies' => (int) $owned->copies,
        ]);
    }
}
