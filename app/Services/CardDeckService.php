<?php

namespace App\Services;

use App\Models\CardDraw;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserWallet;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Random\Engine\Mt19937;
use Random\Randomizer;

class CardDeckService
{
    public function __construct(private CardCatalogService $catalog) {}

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
        $pool = $this->catalog->poolFor($user);

        if ($pool === []) {
            return [];
        }

        $seed = crc32($user->id.'|'.config('app.key'));
        $randomizer = new Randomizer(new Mt19937($seed));
        $randomizer->shuffleArray($pool);

        $offset = intdiv($day->startOfDay()->getTimestamp(), 86400) % count($pool);
        $pool = array_merge(array_slice($pool, $offset), array_slice($pool, 0, $offset));

        return array_slice($pool, 0, min((int) config('cards.daily_balls'), count($pool)));
    }

    public function draw(User $user, int $ballIndex): array
    {
        $day = $this->today();
        $offers = $this->offersFor($user, $day);

        if ($offers === []) {
            throw ValidationException::withMessages(['ball' => __('ui.cards_empty')]);
        }

        if ($ballIndex < 0 || $ballIndex >= count($offers)) {
            throw ValidationException::withMessages(['ball' => __('ui.cards_invalid_ball')]);
        }

        try {
            return DB::transaction(function () use ($user, $day, $offers, $ballIndex) {
                $existing = CardDraw::query()
                    ->where('user_id', $user->id)
                    ->whereDate('draw_date', $day->toDateString())
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null && ! config('cards.allow_multiple_draws')) {
                    throw ValidationException::withMessages(['ball' => __('ui.cards_already_drawn')]);
                }

                $cardKey = $offers[$ballIndex];
                $userCard = UserCard::query()
                    ->where('user_id', $user->id)
                    ->where('card_key', $cardKey)
                    ->lockForUpdate()
                    ->first();

                $duplicate = $userCard !== null;
                $inkEarned = $duplicate ? (int) config('cards.ink_per_duplicate') : 0;

                $drawAttributes = [
                    'card_key' => $cardKey,
                    'ball_index' => $ballIndex,
                    'duplicate' => $duplicate,
                    'ink_earned' => $inkEarned,
                ];

                if ($existing !== null) {
                    $existing->update($drawAttributes);
                } else {
                    CardDraw::create($drawAttributes + [
                        'user_id' => $user->id,
                        'draw_date' => $day->toDateString(),
                    ]);
                }

                if ($userCard) {
                    $userCard->increment('copies');
                } else {
                    $userCard = UserCard::create([
                        'user_id' => $user->id,
                        'card_key' => $cardKey,
                        'copies' => 1,
                        'first_unlocked_at' => now(),
                    ]);
                }

                $wallet = UserWallet::firstOrCreate(['user_id' => $user->id]);

                if ($inkEarned > 0) {
                    $wallet->increment('ink', $inkEarned);
                }

                return [
                    'card' => $this->catalog->metadata($cardKey),
                    'duplicate' => $duplicate,
                    'copies' => (int) $userCard->fresh()->copies,
                    'ink' => (int) $wallet->fresh()->ink,
                    'inkEarned' => $inkEarned,
                    'resetAt' => $this->nextResetAt()->toIso8601String(),
                ];
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                throw ValidationException::withMessages(['ball' => __('ui.cards_already_drawn')]);
            }

            throw $exception;
        }
    }
}
