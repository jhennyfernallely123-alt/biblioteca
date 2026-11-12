<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Str;

class CardCatalogService
{
    public function families(): array
    {
        return config('cards.families');
    }

    public function cards(): array
    {
        return config('cards.cards');
    }

    public function machineStickers(): array
    {
        return config('cards.machine_stickers', []);
    }

    public function machineSticker(string $key): ?array
    {
        foreach ($this->machineStickers() as $sticker) {
            if ($sticker['key'] === $key) {
                return $sticker;
            }
        }

        return null;
    }

    public function machineStickerMetadata(string $key): ?array
    {
        $sticker = $this->machineSticker($key);

        if ($sticker === null) {
            return null;
        }

        return [
            'key' => $sticker['key'],
            'name' => __($sticker['name']),
        ];
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->cards());
    }

    public function normalize(string $value): string
    {
        $value = preg_replace('/\([^)]*\)/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', Str::slug($value, ' ')) ?? '');
    }

    public function familyForBook(string $title, ?string $author): ?string
    {
        $normalizedTitle = $this->normalize($title);
        $normalizedAuthor = $author ? $this->normalize($author) : null;

        foreach ($this->families() as $key => $family) {
            $titles = array_map(fn (string $value) => $this->normalize($value), $family['titles']);

            if (! in_array($normalizedTitle, $titles, true)) {
                continue;
            }

            if ($normalizedAuthor === null || $family['authors'] === []) {
                return $key;
            }

            foreach ($family['authors'] as $candidate) {
                if (str_contains($normalizedAuthor, $this->normalize($candidate))) {
                    return $key;
                }
            }
        }

        return null;
    }

    public function poolFor(User $user): array
    {
        $families = $user->books()
            ->get(['title', 'author'])
            ->map(fn (Book $book) => $this->familyForBook($book->title, $book->author))
            ->filter()
            ->unique()
            ->all();

        return collect($this->cards())
            ->filter(fn (array $card) => in_array($card['family'], $families, true))
            ->keys()
            ->all();
    }

    public function metadata(string $key): array
    {
        $card = $this->cards()[$key];
        $family = $this->families()[$card['family']];

        return [
            'key' => $key,
            'family' => $card['family'],
            'familyLabel' => $family['label'],
            'familyEmblem' => $family['emblem'],
            'character' => $card['character'],
            'monogram' => $card['monogram'],
            'emblem' => $card['emblem'],
        ];
    }

    public function inPool(User $user, string $key): bool
    {
        return in_array($key, $this->poolFor($user), true);
    }

    public function isUnlocked(User $user, string $key): bool
    {
        return $user->userCards()->where('card_key', $key)->exists();
    }

    public function letter(string $key, string $locale): ?string
    {
        if (! $this->exists($key)) {
            return null;
        }

        $curated = config('languages.curated');
        $candidates = in_array($locale, $curated, true) ? [$locale, 'es'] : ['es'];

        foreach (array_unique($candidates) as $candidate) {
            $path = resource_path("cards/{$key}/{$candidate}.md");

            if (is_file($path)) {
                return trim((string) file_get_contents($path));
            }
        }

        return null;
    }
}
