<?php

namespace App\Http\Controllers;

use App\Models\CardDraw;
use App\Models\User;
use App\Services\CardCatalogService;
use App\Services\CardDeckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $offers = collect($deck->offersFor($user))
            ->values()
            ->map(fn (array $offer, int $index) => ['index' => $index]);

        return Inertia::render('Cards/Index', [
            'cards' => $this->cardsData($pool, $catalog, $owned),
            'offers' => $offers,
            'drawn' => $drawn ? $this->drawnData($drawn, $catalog, $owned) : null,
            'ink' => (int) ($user->wallet?->ink ?? 0),
            'progress' => [
                'unlocked' => $owned->count(),
                'total' => count($pool),
            ],
            'resetAt' => $deck->nextResetAt()->toIso8601String(),
            'dailyBalls' => (int) config('cards.daily_balls'),
            'complete' => $offers->isEmpty(),
        ] + $this->stickerData($user, $catalog));
    }

    public function album(Request $request, CardCatalogService $catalog): Response
    {
        $user = $request->user();
        $pool = $catalog->poolFor($user);
        $owned = $user->userCards()->whereIn('card_key', $pool)->get()->keyBy('card_key');

        return Inertia::render('Cards/Album', [
            'cards' => $this->cardsData($pool, $catalog, $owned),
            'progress' => [
                'unlocked' => $owned->count(),
                'total' => count($pool),
            ],
            'ink' => (int) ($user->wallet?->ink ?? 0),
        ] + $this->stickerData($user, $catalog));
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

    private function cardsData(array $pool, CardCatalogService $catalog, Collection $owned): Collection
    {
        return collect($pool)->map(function (string $key) use ($catalog, $owned) {
            $card = $owned->get($key);

            return $catalog->metadata($key) + [
                'unlocked' => $card !== null,
                'copies' => (int) ($card->copies ?? 0),
            ];
        })->values();
    }

    private function stickerData(User $user, CardCatalogService $catalog): array
    {
        $catalogStickers = $catalog->machineStickers();
        $owned = $user->collectibles()
            ->where('kind', 'sticker')
            ->where('event_key', 'maquina')
            ->get()
            ->keyBy('collectible_key');

        $stickers = collect($catalogStickers)->map(function (array $sticker) use ($owned) {
            $record = $owned->get($sticker['key']);

            return [
                'key' => $sticker['key'],
                'name' => __($sticker['name']),
                'unlocked' => $record !== null,
                'acquired_at' => $record?->acquired_at?->toISOString(),
            ];
        })->values();

        return [
            'stickers' => $stickers,
            'stickerProgress' => [
                'unlocked' => $owned->count(),
                'total' => count($catalogStickers),
            ],
        ];
    }

    private function drawnData(CardDraw $draw, CardCatalogService $catalog, Collection $ownedCards): array
    {
        if ($draw->kind === 'sticker') {
            return [
                'kind' => 'sticker',
                'card' => null,
                'sticker' => $catalog->machineStickerMetadata($draw->item_key),
            ];
        }

        return [
            'kind' => 'card',
            'card' => $catalog->metadata($draw->item_key),
            'sticker' => null,
            'copies' => (int) ($ownedCards->get($draw->item_key)?->copies ?? 0),
        ];
    }
}
