<?php

namespace App\Services;

use App\Models\EventProgress;
use App\Models\User;
use App\Models\UserCollectible;
use App\Models\UserWallet;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class EventService
{
    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('events.timezone'))->startOfDay();
    }

    public function activeEvent(?CarbonImmutable $day = null): array
    {
        $day ??= $this->today();
        $catalog = config('events.catalog');
        $epoch = CarbonImmutable::parse(config('events.epoch'), config('events.timezone'))->startOfDay();

        $dayNumber = (int) $epoch->diffInDays($day);
        $cycleDays = array_sum(array_column($catalog, 'days'));
        $offset = $dayNumber % $cycleDays;

        foreach ($catalog as $event) {
            if ($offset < $event['days']) {
                return [
                    'event' => $event,
                    'day' => $offset + 1,
                    'days' => $event['days'],
                ];
            }

            $offset -= $event['days'];
        }

        return [
            'event' => $catalog[0],
            'day' => 1,
            'days' => $catalog[0]['days'],
        ];
    }

    public function todayProgress(User $user): array
    {
        $active = $this->activeEvent();

        return EventProgress::query()
            ->where('user_id', $user->id)
            ->where('event_key', $active['event']['key'])
            ->whereDate('day_date', $this->today()->toDateString())
            ->get()
            ->all();
    }

    public function completeTask(User $user, string $taskKey): void
    {
        if (! config('events.enabled', true)) {
            return;
        }

        try {
            $active = $this->activeEvent();
            $event = $active['event'];

            if (! in_array($taskKey, $event['tasks'], true)) {
                return;
            }

            $reward = (int) config("events.tasks.{$taskKey}.ink", 0);

            DB::transaction(function () use ($user, $event, $reward, $taskKey) {
                $attributes = [
                    'user_id' => $user->id,
                    'event_key' => $event['key'],
                    'day_date' => $this->today()->toDateString(),
                    'task_key' => $taskKey,
                ];

                if (EventProgress::query()->where($attributes)->exists()) {
                    return;
                }

                EventProgress::create($attributes + ['ink_earned' => $reward]);

                if ($reward > 0) {
                    $wallet = UserWallet::firstOrCreate(['user_id' => $user->id]);
                    $wallet->increment('ink', $reward);
                }
            });
        } catch (Throwable) {
            return;
        }
    }

    public function shop(): array
    {
        return $this->activeEvent()['event'];
    }

    public function stickerCatalog(): array
    {
        $stickers = [];

        foreach (config('events.catalog') as $event) {
            foreach ($event['stickers'] as $sticker) {
                $stickers[] = $sticker + [
                    'event_key' => $event['key'],
                    'event_name' => $event['name'],
                    'theme' => $event['theme'],
                ];
            }
        }

        return $stickers;
    }

    public function ownedCollectibleKeys(User $user): array
    {
        return $user->collectibles()->pluck('collectible_key')->all();
    }

    public function collection(User $user): array
    {
        return $user->collectibles()->orderByDesc('acquired_at')->get()->all();
    }

    public function findItem(string $key): ?array
    {
        $event = $this->activeEvent()['event'];

        foreach ($event['stickers'] as $sticker) {
            if ($sticker['key'] === $key) {
                return $sticker + ['kind' => 'sticker'];
            }
        }

        foreach ($event['cards'] as $card) {
            if ($card['key'] === $key) {
                return $card + ['kind' => 'card'];
            }
        }

        return null;
    }

    public function catalogItem(string $key): ?array
    {
        foreach (config('events.catalog') as $event) {
            foreach ($event['stickers'] as $sticker) {
                if ($sticker['key'] === $key) {
                    return $sticker + ['kind' => 'sticker', 'event_key' => $event['key']];
                }
            }

            foreach ($event['cards'] as $card) {
                if ($card['key'] === $key) {
                    return $card + ['kind' => 'card', 'event_key' => $event['key']];
                }
            }
        }

        return null;
    }

    public function grantCollectible(User $user, string $key, string $source): UserCollectible
    {
        $item = $this->findItem($key);

        return UserCollectible::create([
            'user_id' => $user->id,
            'collectible_key' => $key,
            'kind' => $item['kind'] ?? 'sticker',
            'event_key' => $this->activeEvent()['event']['key'],
            'source' => $source,
            'acquired_at' => now(),
        ]);
    }

    public function buyCollectible(User $user, string $key): array
    {
        $item = $this->findItem($key);

        if ($item === null) {
            throw ValidationException::withMessages(['item' => __('ui.events_item_missing')]);
        }

        $owned = UserCollectible::query()
            ->where('user_id', $user->id)
            ->where('collectible_key', $key)
            ->exists();

        if ($owned) {
            abort(409);
        }

        $wallet = UserWallet::firstOrCreate(['user_id' => $user->id]);

        if ($wallet->ink < $item['cost']) {
            throw ValidationException::withMessages(['item' => __('ui.events_not_enough_ink')]);
        }

        return DB::transaction(function () use ($user, $item, $wallet) {
            $wallet->decrement('ink', $item['cost']);
            $collectible = $this->grantCollectible($user, $item['key'], 'shop');

            return [
                'collectible' => ['key' => $collectible->collectible_key, 'kind' => $collectible->kind],
                'ink' => (int) $wallet->fresh()->ink,
            ];
        });
    }
}
