<?php

namespace App\Services;

use App\Models\CardDraw;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserCollectible;
use App\Models\UserWallet;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Random\Engine\Mt19937;
use Random\Randomizer;

class CardDeckService
{
    public function __construct(
        private CardCatalogService $catalog,
    ) {}

    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('cards.timezone'))->startOfDay();
    }

    public function nextResetAt(): CarbonImmutable
    {
        return $this->today()->addDay()->utc();
    }

    public function drawnToday(User $user): ?CardDraw
    {
        return CardDraw::query()
            ->where('user_id', $user->id)
            ->whereDate('draw_date', $this->today()->toDateString())
            ->first();
    }

    public function offersFor(User $user, ?CarbonImmutable $day = null): array
    {
        $day ??= $this->today();
        $limit = (int) config('cards.daily_balls');

        $cards = $this->shuffledForDay($this->unownedCardKeys($user), $user, $day);
        $stickers = $this->shuffledForDay($this->unownedStickerKeys($user), $user, $day);

        $cardSlots = min(4, count($cards));
        $stickerSlots = min($limit - $cardSlots, count($stickers));

        if ($cardSlots + $stickerSlots < $limit) {
            $cardSlots = min(count($cards), $limit - $stickerSlots);
        }

        $offers = [
            ...array_map(
                fn (string $key) => ['kind' => 'card', 'key' => $key],
                array_slice($cards, 0, $cardSlots),
            ),
            ...array_map(
                fn (string $key) => ['kind' => 'sticker', 'key' => $key],
                array_slice($stickers, 0, $stickerSlots),
            ),
        ];

        $randomizer = new Randomizer(new Mt19937(
            crc32($user->id.'|'.config('app.key').'|mix|'.$day->toDateString())
        ));

        return $randomizer->shuffleArray($offers);
    }

    public function draw(User $user, int $ballIndex): array
    {
        $day = $this->today();
        $offers = $this->offersFor($user, $day);

        if ($offers === []) {
            throw ValidationException::withMessages(['ball' => __('ui.cards_complete')]);
        }

        if ($ballIndex < 0 || $ballIndex >= count($offers)) {
            throw ValidationException::withMessages(['ball' => __('ui.cards_invalid_ball')]);
        }

        $item = $offers[$ballIndex];

        try {
            $result = DB::transaction(function () use ($user, $day, $ballIndex, $item) {
                $existing = CardDraw::query()
                    ->where('user_id', $user->id)
                    ->whereDate('draw_date', $day->toDateString())
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null && ! config('cards.allow_multiple_draws')) {
                    throw ValidationException::withMessages(['ball' => __('ui.cards_already_drawn')]);
                }

                if ($item['kind'] === 'card') {
                    $owned = UserCard::firstOrCreate(
                        ['user_id' => $user->id, 'card_key' => $item['key']],
                        ['copies' => 1, 'first_unlocked_at' => now()],
                    );

                    if (! $owned->wasRecentlyCreated) {
                        throw ValidationException::withMessages(['ball' => __('ui.cards_already_drawn')]);
                    }

                    $payload = [
                        'kind' => 'card',
                        'card' => $this->catalog->metadata($item['key']),
                        'sticker' => null,
                    ];
                } else {
                    $owned = UserCollectible::firstOrCreate(
                        ['user_id' => $user->id, 'collectible_key' => $item['key']],
                        [
                            'kind' => 'sticker',
                            'event_key' => 'maquina',
                            'source' => 'machine',
                            'acquired_at' => now(),
                        ],
                    );

                    if (! $owned->wasRecentlyCreated) {
                        throw ValidationException::withMessages(['ball' => __('ui.cards_already_drawn')]);
                    }

                    $payload = [
                        'kind' => 'sticker',
                        'card' => null,
                        'sticker' => $this->catalog->machineStickerMetadata($item['key']),
                    ];
                }

                $drawAttributes = [
                    'kind' => $item['kind'],
                    'item_key' => $item['key'],
                    'ball_index' => $ballIndex,
                    'duplicate' => false,
                    'ink_earned' => 0,
                ];

                if ($existing !== null) {
                    $existing->update($drawAttributes);
                } else {
                    CardDraw::create($drawAttributes + [
                        'user_id' => $user->id,
                        'draw_date' => $day->toDateString(),
                    ]);
                }

                $wallet = UserWallet::firstOrCreate(['user_id' => $user->id]);

                return $payload + [
                    'ink' => (int) $wallet->fresh()->ink,
                    'resetAt' => $this->nextResetAt()->toIso8601String(),
                ];
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                throw ValidationException::withMessages(['ball' => __('ui.cards_already_drawn')]);
            }

            throw $exception;
        }

        return $result;
    }

    private function unownedCardKeys(User $user): array
    {
        $owned = $user->userCards()->pluck('card_key')->all();

        return array_values(array_diff($this->catalog->poolFor($user), $owned));
    }

    private function unownedStickerKeys(User $user): array
    {
        $owned = $user->collectibles()
            ->where('kind', 'sticker')
            ->where('event_key', 'maquina')
            ->pluck('collectible_key')
            ->all();

        return array_values(array_filter(
            array_column($this->catalog->machineStickers(), 'key'),
            fn (string $key) => ! in_array($key, $owned, true),
        ));
    }

    private function shuffledForDay(array $keys, User $user, CarbonImmutable $day): array
    {
        if ($keys === []) {
            return [];
        }

        $randomizer = new Randomizer(new Mt19937(
            crc32($user->id.'|'.config('app.key').'|'.$day->toDateString())
        ));

        return $randomizer->shuffleArray($keys);
    }
}
