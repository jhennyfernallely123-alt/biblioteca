<?php

use App\Services\CardCatalogService;

it('tiene las 20 cartas en español con al menos 250 palabras', function () {
    $keys = array_keys(config('cards.cards'));

    foreach ($keys as $key) {
        $path = resource_path("cards/{$key}/es.md");

        expect(is_file($path))->toBeTrue("Falta {$key}/es.md");

        $text = trim((string) file_get_contents($path));
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        expect(count($words))->toBeGreaterThanOrEqual(120, "{$key}/es.md tiene pocas palabras");
        expect(count($words))->toBeLessThanOrEqual(160, "{$key}/es.md tiene demasiadas palabras");
        expect($text)->not->toContain('<script');
        expect($text)->not->toContain('<a ');
    }
});

it('no deja cartas sin metadatos ni emblemas', function () {
    $catalog = app(CardCatalogService::class);

    foreach (array_keys(config('cards.cards')) as $key) {
        $meta = $catalog->metadata($key);

        expect($catalog->exists($key))->toBeTrue();
        expect($catalog->letter($key, 'es'))->not->toBeNull();
        expect($meta['familyEmblem'])->not->toBe('');
        expect($meta['emblem'])->not->toBe('');
    }
});
