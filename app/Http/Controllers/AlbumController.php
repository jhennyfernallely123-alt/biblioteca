<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CardCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AlbumController extends Controller
{
    public function index(Request $request, CardCatalogService $catalog): Response
    {
        $user = $request->user();

        $cards = $this->cardItems($user, $catalog);
        $stickers = $this->stickerItems($user, $catalog);

        $items = $cards->concat($stickers)->values();

        $progress = [
            'unlocked' => $items->where('unlocked', true)->count(),
            'total' => $items->count(),
            'byRarity' => [
                'comun' => $items->where('unlocked', true)->where('rarity', 'comun')->count(),
                'rara' => $items->where('unlocked', true)->where('rarity', 'rara')->count(),
                'epica' => $items->where('unlocked', true)->where('rarity', 'epica')->count(),
            ],
        ];

        return Inertia::render('Album/Index', [
            'items' => $items,
            'progress' => $progress,
        ]);
    }

    private function cardItems(User $user, CardCatalogService $catalog): Collection
    {
        $pool = $catalog->poolFor($user);
        $owned = $user->userCards()->whereIn('card_key', $pool)->get()->keyBy('card_key');

        return collect($pool)->map(function (string $key) use ($catalog, $owned) {
            $card = $owned->get($key);
            $metadata = $catalog->metadata($key);

            return [
                'type' => 'card',
                'key' => $key,
                'name' => $metadata['character'] ?? $key,
                'category' => $metadata['familyLabel'] ?? $metadata['family'] ?? 'Cartas',
                'rarity' => 'comun',
                'unlocked' => $card !== null,
                'copies' => (int) ($card->copies ?? 0),
                'acquired_at' => $card?->created_at?->toISOString(),
                'image' => null,
            ];
        })->values();
    }

    private function stickerItems(User $user, CardCatalogService $catalog): Collection
    {
        $catalogStickers = $catalog->machineStickers();
        $owned = $user->collectibles()
            ->where('kind', 'sticker')
            ->where('event_key', 'maquina')
            ->get()
            ->keyBy('collectible_key');

        return collect($catalogStickers)->map(function (array $sticker) use ($owned) {
            $record = $owned->get($sticker['key']);

            return [
                'type' => 'sticker',
                'key' => $sticker['key'],
                'name' => __($sticker['name']),
                'category' => 'Stickers de la máquina',
                'rarity' => 'comun',
                'unlocked' => $record !== null,
                'copies' => 1,
                'acquired_at' => $record?->acquired_at?->toISOString(),
                'image' => null,
            ];
        })->values();
    }
}
