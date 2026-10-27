<?php

use App\Models\User;
use App\Services\CatalogService;
use App\Services\CatalogTranslationService;
use Illuminate\Support\Facades\Http;

it('tiene todas las claves en los idiomas revisados', function () {
    $catalog = app(CatalogService::class);
    $keys = $catalog->keys();

    foreach (config('languages.curated') as $locale) {
        $missing = array_values(array_diff($keys, array_keys($catalog->all($locale))));

        expect($missing)->toBe([], "Faltan claves en {$locale}");
    }
});

it('prepara un idioma automático por lotes', function () {
    $catalog = app(CatalogService::class);

    $missing = $catalog->missing('af');
    $batch = array_slice($missing, 0, 2);

    Http::fake([
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => ['translatedText' => 'Traducido'],
        ]),
    ]);

    app(CatalogTranslationService::class)->translateKeys('af', $batch);

    expect(count($catalog->missing('af')))->toBeLessThan(count($missing));
});

it('no activa un idioma incompleto', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('settings.locale'), ['locale' => 'af'])
        ->assertSessionHasErrors('locale');

    expect($user->fresh()->locale)->toBe('es');
});
