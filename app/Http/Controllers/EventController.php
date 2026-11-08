<?php

namespace App\Http\Controllers;

use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request, EventService $events): Response
    {
        $user = $request->user();
        $active = $events->activeEvent();
        $event = $active['event'];

        $owned = $user->collectibles()->pluck('collectible_key')->all();

        $tasks = collect($event['tasks'])->map(function (string $taskKey) use ($events, $user) {
            $done = collect($events->todayProgress($user))->contains('task_key', $taskKey);

            return [
                'key' => $taskKey,
                'ink' => (int) config("events.tasks.{$taskKey}.ink", 0),
                'done' => $done,
            ];
        })->values();

        $shop = collect([...$event['stickers'], ...$event['cards']])
            ->map(fn (array $item) => $item + ['owned' => in_array($item['key'], $owned, true)])
            ->values();

        $collection = collect($events->collection($user))
            ->map(function ($row) use ($events) {
                $meta = $events->catalogItem($row->collectible_key);

                return [
                    'key' => $row->collectible_key,
                    'kind' => $row->kind,
                    'eventKey' => $row->event_key,
                    'name' => $meta['name'] ?? $row->collectible_key,
                    'emblem' => $meta['emblem'] ?? null,
                    'initial' => $meta['initial'] ?? null,
                ];
            })->values();

        return Inertia::render('Events/Index', [
            'event' => [
                'key' => $event['key'],
                'name' => $event['name'],
                'theme' => $event['theme'],
                'day' => $active['day'],
                'days' => $active['days'],
            ],
            'tasks' => $tasks,
            'shop' => $shop,
            'collection' => $collection,
            'ink' => (int) ($user->wallet?->ink ?? 0),
        ]);
    }

    public function album(Request $request, EventService $events): Response
    {
        $user = $request->user();
        $owned = $user->collectibles()
            ->where('kind', 'sticker')
            ->get()
            ->keyBy('collectible_key');

        $stickers = collect($events->stickerCatalog())
            ->map(fn (array $sticker) => [
                ...$sticker,
                'owned' => isset($owned[$sticker['key']]),
                'acquired_at' => isset($owned[$sticker['key']])
                    ? $owned[$sticker['key']]->acquired_at?->toISOString()
                    : null,
            ])
            ->values();

        $byRarity = ['comun' => 0, 'rara' => 0, 'epica' => 0];
        foreach ($stickers as $sticker) {
            if ($sticker['owned']) {
                $byRarity[$sticker['rarity']]++;
            }
        }

        return Inertia::render('Events/Album', [
            'stickers' => $stickers,
            'progress' => [
                'owned' => $stickers->where('owned', true)->count(),
                'total' => $stickers->count(),
                'by_rarity' => $byRarity,
            ],
            'ink' => (int) ($user->wallet?->ink ?? 0),
        ]);
    }

    public function buy(Request $request, EventService $events): JsonResponse
    {
        $data = $request->validate(['item' => ['required', 'string', 'max:64']]);

        return response()->json($events->buyCollectible($request->user(), $data['item']));
    }
}
