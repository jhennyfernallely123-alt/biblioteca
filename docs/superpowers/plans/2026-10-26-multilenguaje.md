# Multilingüe de Biblioteca — Plan de implementación

> **Para agentes:** ejecutar tarea por tarea. No hacer `git add`, `git commit`, `git push` ni `git tag`: los commits los hace la usuaria (ver `AGENTS.md`). Sustituir cada «Checkpoint» por una pausa breve para revisar, no por un commit.

**Objetivo:** permitir que cada usuaria elija el idioma de toda la aplicación autenticada, con 8 idiomas revisados y el resto traducidos automáticamente, y corregir la traducción de los títulos de búsqueda.

**Arquitectura:** catálogo único de claves planas. `lang/es.json` es el maestro de interfaz; `lang/{locale}.json` contiene los idiomas revisados; `storage/app/i18n/{locale}.json` contiene los automáticos generados con MyMemory. Laravel y Vue leen las mismas claves. El idioma se guarda en `users.locale` y lo aplica un middleware.

**Stack:** Laravel 13, Inertia 2, Vue 3, Tailwind 4, Pest 5, Vitest 5, MySQL, MyMemory.

---

## Mapa de archivos

### Backend

- Crear `config/languages.php`: idiomas soportados, revisados, RTL y código de MyMemory.
- Crear `app/Services/CatalogService.php`: carga, aplana, valida y guarda catálogos.
- Crear `app/Services/CatalogTranslationService.php`: traduce claves con MyMemory y protege marcadores.
- Crear `app/Http/Middleware/SetLocale.php`: aplica el idioma de la usuaria o español.
- Crear `app/Http/Controllers/LocaleController.php`: preparar, traducir y aplicar idioma.
- Crear `app/Http/Requests/UpdateLocaleRequest.php`.
- Crear `database/migrations/2026_10_26_000000_add_locale_to_users_table.php`.
- Modificar `app/Models/User.php`: `locale` rellenable y valor por defecto.
- Modificar `app/Http/Middleware/HandleInertiaRequests.php`: compartir idioma, dirección, traducciones y flash.
- Modificar `app/Providers/AppServiceProvider.php`: registrar `storage/app/i18n` como ruta JSON.
- Modificar `bootstrap/app.php`: registrar `SetLocale`.
- Modificar `app/Services/TranslationService.php`: destino configurable, caché por idioma y traducción de textos sueltos.
- Modificar `app/Services/BookLookupService.php`: traducir después de la caché cruda.
- Modificar `app/Services/GoogleBooksService.php`: devolver `language`.
- Modificar `app/Services/OpenLibraryService.php`: devolver `language`.
- Modificar `config/services.php`: `mymemory.email`.
- Modificar `app/Http/Controllers/SettingsController.php`: pasar idiomas y `locale`; aplicar idioma.
- Modificar `app/Http/Controllers/LibraryController.php` y `BookController.php`: añadir `display_name` a estanterías.
- Modificar `routes/web.php`: rutas de idioma.

### Catálogos

- Crear `lang/es.json` (maestro de interfaz).
- Crear `lang/en.json`, `lang/pt.json`, `lang/fr.json`, `lang/de.json`, `lang/it.json`, `lang/ca.json`, `lang/gl.json`.
- Mantener `lang/es/validation.php`, `auth.php` y `passwords.php` como fuente española.
- Crear `storage/app/i18n/.gitignore` con `*` y `!.gitignore` si el directorio no existe.

### Frontend

- Crear `resources/js/i18n.js`: `useI18n()` y `t()`.
- Crear `resources/js/Components/LocaleSync.vue`: actualiza `lang` y `dir`.
- Crear `resources/js/Components/LanguageSelect.vue`: selector buscable.
- Modificar `resources/js/Components/AppShell.vue`: textos con `t()`, `LocaleSync`, utilidades lógicas.
- Modificar `resources/js/Pages/Settings/Index.vue`: sección Idioma y textos.
- Modificar `resources/js/Pages/Library/Index.vue`, `Books/Create.vue`, `Books/Edit.vue`.
- Modificar `resources/js/Components/BookCard.vue`, `BookSpine.vue`, `Shelf.vue`, `EmptyShelf.vue`, `RatingStars.vue`, `BarcodeScanner.vue`.
- Modificar los partials de `Profile` para traducirlos.
- Modificar `resources/js/Layouts/GuestLayout.vue`: `LocaleSync` (aunque el invitado siempre es `es`).
- Modificar `resources/views/app.blade.php`: `dir`.
- No tocar las pantallas `Auth/*`: quedan en español.

### Documentación

- `docs/DISENO.md`: sección de idioma, selector, etiquetas, progreso y RTL.
- `docs/DESPLIEGUE.md`: `MYMEMORY_EMAIL` y `storage/app/i18n`.
- `.env.example`: `MYMEMORY_EMAIL`.
- `AGENTS.md`: convención de textos visibles con claves.

---

## Tarea 1: Traducción de libros conectada y con idioma destino

**Archivos:**
- Modificar `app/Services/TranslationService.php`
- Modificar `app/Services/BookLookupService.php`
- Modificar `app/Services/GoogleBooksService.php`
- Modificar `app/Services/OpenLibraryService.php`
- Modificar `config/services.php`
- Probar `tests/Feature/BookLookupServiceTest.php`

- [ ] **Paso 1: Añadir `language` a Google Books**

En `GoogleBooksService::map()` añadir:

```php
'language' => isset($info['language']) ? strtolower(substr($info['language'], 0, 2)) : null,
```

- [ ] **Paso 2: Añadir `language` a Open Library**

En `OpenLibraryService::search()` añadir `language` a `fields` y al resultado:

```php
'fields' => 'title,author_name,isbn,publisher,first_publish_year,cover_i,cover_edition_key,edition_key,language',
```

```php
'language' => isset($doc['language'][0])
    ? strtolower(substr($doc['language'][0], 0, 2))
    : null,
```

En `byIsbn()` añadir al resultado:

```php
'language' => null,
```

- [ ] **Paso 3: Añadir configuración de MyMemory**

En `config/services.php` antes de `resend`:

```php
'mymemory' => [
    'email' => env('MYMEMORY_EMAIL'),
],
```

- [ ] **Paso 4: Reescribir `TranslationService`**

```php
<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    public function translateText(string $text, string $targetLocale, ?string $sourceLanguage = null): ?string
    {
        if (trim($text) === '' || $this->isSameLanguage($sourceLanguage, $targetLocale)) {
            return null;
        }

        $key = $this->cacheKey($targetLocale, $text);
        $cached = Cache::get($key);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $translated = $this->fetch([[$text, $sourceLanguage]], $targetLocale)[0] ?? null;

        if ($translated !== null) {
            Cache::put($key, $translated, now()->addDays(30));
        }

        return $translated;
    }

    public function translateResults(array $results, string $targetLocale): array
    {
        $positions = [];
        $texts = [];

        foreach ($results as $index => $result) {
            $title = $result['title'] ?? null;
            $source = $result['language'] ?? null;

            if (! $title || $this->isSameLanguage($source, $targetLocale)) {
                continue;
            }

            $cached = Cache::get($this->cacheKey($targetLocale, $title));

            if (is_string($cached) && $cached !== '') {
                $results[$index]['original_title'] = $title;
                $results[$index]['title'] = $cached;

                continue;
            }

            $positions[] = $index;
            $texts[] = [$title, $source];
        }

        $translations = $this->fetch($texts, $targetLocale);

        foreach ($positions as $position => $index) {
            $translated = $translations[$position] ?? null;

            if ($translated === null) {
                continue;
            }

            $title = $results[$index]['title'];
            Cache::put($this->cacheKey($targetLocale, $title), $translated, now()->addDays(30));
            $results[$index]['original_title'] = $title;
            $results[$index]['title'] = $translated;
        }

        return $results;
    }

    private function fetch(array $items, string $targetLocale): array
    {
        if ($items === []) {
            return [];
        }

        $target = $this->apiCode($targetLocale);

        try {
            $responses = Http::pool(function (Pool $pool) use ($items, $target) {
                foreach ($items as $index => [$text, $source]) {
                    $pool->as('t'.$index)
                        ->timeout(5)
                        ->get('https://api.mymemory.translated.net/get', $this->params(
                            $text,
                            $source ?: 'en',
                            $target
                        ));
                }
            });
        } catch (\Throwable $e) {
            Log::warning('No se pudieron traducir los textos: '.$e->getMessage());

            return [];
        }

        $translations = [];

        foreach ($items as $index => [$text]) {
            $response = $responses['t'.$index] ?? null;

            if (! $response instanceof Response || ! $response->ok()) {
                continue;
            }

            $translated = $response->json('responseData.translatedText');

            if (! is_string($translated)
                || trim($translated) === ''
                || str_contains(strtolower($translated), 'mymemory warning')) {
                continue;
            }

            $translations[$index] = trim(html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return $translations;
    }

    private function params(string $text, string $source, string $target): array
    {
        $params = [
            'q' => $text,
            'langpair' => $this->apiCode($source).'|'.$target,
        ];

        if ($email = config('services.mymemory.email')) {
            $params['de'] = $email;
        }

        return $params;
    }

    private function apiCode(string $locale): string
    {
        return config("languages.supported.{$locale}", $locale);
    }

    private function isSameLanguage(?string $source, string $target): bool
    {
        if (! $source) {
            return false;
        }

        return substr(strtolower($source), 0, 2) === substr(strtolower($target), 0, 2);
    }

    private function cacheKey(string $locale, string $text): string
    {
        return 'translation:'.$locale.':'.md5($text);
    }
}
```

- [ ] **Paso 5: Integrar en `BookLookupService`**

```php
public function __construct(
    private GoogleBooksService $google,
    private OpenLibraryService $openLibrary,
    private TranslationService $translator,
) {}

public function byIsbn(string $value): array
{
    $isbn = Isbn::normalize($value);

    if (! Isbn::isValid($isbn)) {
        throw ValidationException::withMessages([
            'isbn' => 'El ISBN no es válido.',
        ]);
    }

    $results = Cache::remember(
        "lookup:v4:isbn:{$isbn}",
        now()->addDay(),
        fn () => $this->google->byIsbn($isbn) ?: $this->openLibrary->byIsbn($isbn)
    );

    return $this->translator->translateResults($results, app()->getLocale());
}

public function search(string $query): array
{
    $query = trim($query);

    if (mb_strlen($query) < 3) {
        return [];
    }

    $results = Cache::remember(
        'lookup:v4:q:'.md5($query),
        now()->addDay(),
        fn () => $this->google->search($query) ?: $this->openLibrary->search($query)
    );

    return $this->translator->translateResults($results, app()->getLocale());
}
```

- [ ] **Paso 6: Ejecutar la prueba existente**

```bash
php artisan test tests/Feature/BookLookupServiceTest.php
```

Esperado: las 8 pruebas pasan, incluida «traduce al español los títulos de libros en inglés».

- [ ] **Paso 7: Añadir pruebas de idioma destino**

En `tests/Feature/BookLookupServiceTest.php` añadir:

```php
it('traduce al idioma activo de la usuaria', function () {
    app()->setLocale('fr');

    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [[
                'volumeInfo' => [
                    'title' => 'The Hobbit',
                    'authors' => ['J. R. R. Tolkien'],
                    'language' => 'en',
                ],
            ]],
        ]),
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => ['translatedText' => 'Le Hobbit'],
        ]),
    ]);

    $result = app(BookLookupService::class)->search('the hobbit');

    expect($result[0]['title'])->toBe('Le Hobbit')
        ->and($result[0]['original_title'])->toBe('The Hobbit');
});

it('no traduce si el libro ya está en el idioma activo', function () {
    app()->setLocale('en');

    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [[
                'volumeInfo' => [
                    'title' => 'The Hobbit',
                    'language' => 'en',
                ],
            ]],
        ]),
    ]);

    $result = app(BookLookupService::class)->search('the hobbit');

    expect($result[0]['title'])->toBe('The Hobbit')
        ->and($result[0])->not->toHaveKey('original_title');
});
```

- [ ] **Paso 8: Ejecutar**

```bash
php artisan test tests/Feature/BookLookupServiceTest.php
```

Esperado: 10 pruebas pasan.

---

## Tarea 2: Configuración de idiomas, migración, middleware y catálogo

**Archivos:**
- Crear `config/languages.php`
- Crear `database/migrations/2026_10_26_000000_add_locale_to_users_table.php`
- Modificar `app/Models/User.php`
- Crear `app/Http/Middleware/SetLocale.php`
- Modificar `bootstrap/app.php`
- Modificar `app/Providers/AppServiceProvider.php`
- Modificar `app/Http/Middleware/HandleInertiaRequests.php`
- Crear `app/Services/CatalogService.php`
- Probar `tests/Feature/LocaleTest.php`

- [ ] **Paso 1: Crear `config/languages.php`**

```php
<?php

$api = [
    'en' => 'en-GB',
    'es' => 'es-ES',
    'pt' => 'pt-PT',
    'fr' => 'fr-FR',
    'de' => 'de-DE',
    'it' => 'it-IT',
    'ca' => 'ca-ES',
    'gl' => 'gl-ES',
    'zh' => 'zh-CN',
    'zh-TW' => 'zh-TW',
    'sr' => 'sr-Latn-RS',
    'ku' => 'kmr-TR',
    'ckb' => 'ckb-IQ',
    'he' => 'he-IL',
    'yi' => 'yi-YD',
    'dv' => 'dv-MV',
    'ug' => 'ug-CN',
    'ps' => 'ps-PK',
    'sd' => 'sd-PK',
    'no' => 'nb-NO',
    'tl' => 'tl-PH',
    'hmn' => 'hmn-CN',
    'haw' => 'haw-US',
    'ceb' => 'ceb-PH',
    'jw' => 'jv-ID',
    'or' => 'or-IN',
    'gd' => 'gd-GB',
    'st' => 'st-LS',
    'sn' => 'sn-ZW',
    'si' => 'si-LK',
    'su' => 'su-ID',
    'tg' => 'tg-TJ',
    'ta' => 'ta-IN',
    'te' => 'te-IN',
    'th' => 'th-TH',
    'tr' => 'tr-TR',
    'uk' => 'uk-UA',
    'ur' => 'ur-PK',
    'uz' => 'uz-UZ',
    'vi' => 'vi-VN',
    'cy' => 'cy-GB',
    'xh' => 'xh-ZA',
    'yo' => 'yo-NG',
    'zu' => 'zu-ZA',
];

$codes = [
    'af', 'sq', 'am', 'ar', 'hy', 'az', 'eu', 'be', 'bn', 'bs', 'bg', 'ca',
    'ceb', 'zh', 'zh-TW', 'co', 'hr', 'cs', 'da', 'nl', 'en', 'eo', 'et',
    'tl', 'fi', 'fr', 'gl', 'ka', 'de', 'el', 'gu', 'ht', 'ha', 'haw', 'he',
    'hi', 'hmn', 'hu', 'is', 'ig', 'id', 'ga', 'it', 'ja', 'jw', 'kn', 'kk',
    'km', 'ko', 'ku', 'ckb', 'ky', 'lo', 'la', 'lv', 'lt', 'lb', 'mk', 'mg',
    'ms', 'ml', 'mt', 'mi', 'mr', 'mn', 'my', 'ne', 'no', 'or', 'ps', 'fa',
    'pl', 'pt', 'pa', 'ro', 'ru', 'sm', 'gd', 'sr', 'st', 'sn', 'sd', 'si',
    'sk', 'sl', 'so', 'es', 'su', 'sw', 'sv', 'tg', 'ta', 'te', 'th', 'tr',
    'uk', 'ur', 'uz', 'vi', 'cy', 'xh', 'yi', 'yo', 'zu',
];

$supported = [];
foreach ($codes as $code) {
    $supported[$code] = $api[$code] ?? $code;
}

return [
    'curated' => ['es', 'en', 'pt', 'fr', 'de', 'it', 'ca', 'gl'],
    'rtl' => ['ar', 'he', 'fa', 'ur', 'ps', 'sd', 'ug', 'yi', 'dv', 'ku', 'ckb'],
    'supported' => $supported,
];
```

- [ ] **Paso 2: Migración de `locale`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 10)->default('es')->after('default_view_mode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
```

- [ ] **Paso 3: Modelo `User`**

```php
#[Fillable(['name', 'email', 'password', 'default_view_mode', 'locale'])]
```

```php
protected $attributes = [
    'default_view_mode' => 'covers',
    'locale' => 'es',
];
```

- [ ] **Paso 4: Middleware `SetLocale`**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale ?? 'es';

        if (! array_key_exists($locale, config('languages.supported'))) {
            $locale = 'es';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
```

- [ ] **Paso 5: Registrar middleware**

En `bootstrap/app.php`:

```php
use App\Http\Middleware\SetLocale;
```

```php
$middleware->web(append: [
    SetLocale::class,
    HandleInertiaRequests::class,
    AddLinkHeadersForPreloadedAssets::class,
]);
```

- [ ] **Paso 6: Registrar ruta JSON y catálogo**

En `AppServiceProvider::boot()`:

```php
use Illuminate\Support\Facades\File;

$path = storage_path('app/i18n');
File::ensureDirectoryExists($path);
app('translator')->getLoader()->addJsonPath($path);
```

- [ ] **Paso 7: Crear `CatalogService`**

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class CatalogService
{
    public function source(): array
    {
        return array_merge(
            $this->json(lang_path('es.json')),
            $this->flatten(require lang_path('es/validation.php'), 'validation'),
            $this->flatten(require lang_path('es/auth.php'), 'auth'),
            $this->flatten(require lang_path('es/passwords.php'), 'passwords'),
        );
    }

    public function keys(): array
    {
        return array_keys($this->source());
    }

    public function all(string $locale): array
    {
        if ($locale === 'es') {
            return $this->source();
        }

        return array_merge(
            $this->json(lang_path("{$locale}.json")),
            $this->json($this->storagePath($locale)),
        );
    }

    public function ui(string $locale): array
    {
        $all = $this->all($locale);

        return array_filter(
            $all,
            fn (string $key) => str_starts_with($key, 'ui.'),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function missing(string $locale): array
    {
        if ($locale === 'es' || in_array($locale, config('languages.curated'), true)) {
            return array_values(array_diff($this->keys(), array_keys($this->all($locale))));
        }

        return array_values(array_diff($this->keys(), array_keys($this->all($locale))));
    }

    public function isComplete(string $locale): bool
    {
        return $this->missing($locale) === [];
    }

    public function writeGenerated(string $locale, array $lines): void
    {
        $path = $this->storagePath($locale);
        File::ensureDirectoryExists(dirname($path));
        $tmp = $path.'.tmp';
        File::put($tmp, json_encode($lines, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::move($tmp, $path);
    }

    public function storagePath(string $locale): string
    {
        return storage_path("app/i18n/{$locale}.json");
    }

    private function json(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $data = json_decode(File::get($path), true);

        return is_array($data) ? $data : [];
    }

    private function flatten(array $lines, string $prefix): array
    {
        $flat = [];

        foreach ($lines as $key => $value) {
            $full = "{$prefix}.{$key}";

            if (is_array($value)) {
                $flat += $this->flatten($value, $full);
            } else {
                $flat[$full] = $value;
            }
        }

        return $flat;
    }
}
```

- [ ] **Paso 8: Compartir props en Inertia**

En `HandleInertiaRequests::share()`:

```php
use App\Services\CatalogService;

$locale = app()->getLocale();

return [
    ...parent::share($request),
    'auth' => [
        'user' => $request->user(),
    ],
    'locale' => $locale,
    'direction' => in_array($locale, config('languages.rtl'), true) ? 'rtl' : 'ltr',
    'translations' => app(CatalogService::class)->ui($locale),
    'flash' => [
        'success' => fn () => $request->session()->get('success'),
    ],
];
```

- [ ] **Paso 9: Pruebas de idioma**

Crear `tests/Feature/LocaleTest.php`:

```php
<?php

use App\Models\User;

it('usa español por defecto', function () {
    expect((new User())->locale)->toBe('es');
});

it('aplica el idioma de la usuaria autenticada', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->get(route('library.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
            ->where('direction', 'ltr')
            ->where('translations.ui.nav_library', 'Library'));
});

it('usa español para invitados', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'es'));
});

it('cae en español si el idioma guardado no está soportado', function () {
    $user = User::factory()->create(['locale' => 'xx']);

    $this->actingAs($user)
        ->get(route('library.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'es'));
});
```

- [ ] **Paso 10: Ejecutar**

```bash
php artisan migrate
php artisan test tests/Feature/LocaleTest.php
```

Esperado: 4 pruebas pasan.

---

## Tarea 3: Catálogo maestro, traducciones revisadas y endpoints

**Archivos:**
- Crear `lang/es.json`
- Crear `lang/en.json`, `lang/pt.json`, `lang/fr.json`, `lang/de.json`, `lang/it.json`, `lang/ca.json`, `lang/gl.json`
- Crear `app/Services/CatalogTranslationService.php`
- Crear `app/Http/Requests/UpdateLocaleRequest.php`
- Crear `app/Http/Controllers/LocaleController.php`
- Modificar `routes/web.php`
- Probar `tests/Feature/CatalogTest.php` y `tests/Feature/LocaleSettingsTest.php`

- [ ] **Paso 1: Crear `lang/es.json` con estas claves**

```json
{
    "ui.nav_library": "Biblioteca",
    "ui.nav_add": "Añadir",
    "ui.nav_settings": "Ajustes",
    "ui.logout": "Cerrar sesión",
    "ui.exit": "Salir",
    "ui.library_title": "Mi biblioteca",
    "ui.add_book": "Añadir libro",
    "ui.books_count": ":count libros",
    "ui.one_book": "1 libro",
    "ui.show_spines": "Ver lomos",
    "ui.show_covers": "Ver portadas",
    "ui.empty_shelf": "Aún no hay libros en esta estantería.",
    "ui.cover_alt": "Portada de :title",
    "ui.unknown_author_lower": "autor desconocido",
    "ui.unknown_author": "Autor desconocido",
    "ui.rating_label": ":rating de 5 estrellas",
    "ui.rating_star": ":star de 5 estrellas",
    "ui.rating_heading": "Valoración",
    "ui.search_tab": "Buscar",
    "ui.manual_tab": "Manual",
    "ui.close_camera": "Cerrar cámara",
    "ui.scan_camera": "Escanear código de barras con la cámara",
    "ui.isbn": "ISBN",
    "ui.search_isbn": "Buscar ISBN",
    "ui.search_placeholder": "Título o autor",
    "ui.search": "Buscar",
    "ui.searching": "Buscando…",
    "ui.search_hint": "Escribe al menos 3 letras; los resultados aparecen solos.",
    "ui.isbn_not_found": "No encontramos ese ISBN. Puedes añadirlo manualmente.",
    "ui.catalog_error": "No pudimos consultar el catálogo. Revisa tu conexión e inténtalo de nuevo.",
    "ui.check_data": "Revisa los datos e inténtalo de nuevo.",
    "ui.no_results": "No encontramos resultados. Prueba con otro título o autor.",
    "ui.title_placeholder": "Título *",
    "ui.author_placeholder": "Autor",
    "ui.publisher_placeholder": "Editorial",
    "ui.year_placeholder": "Año",
    "ui.notes_placeholder": "Notas",
    "ui.save": "Guardar",
    "ui.delete_book_confirm": "¿Eliminar este libro de tu biblioteca?",
    "ui.delete": "Eliminar",
    "ui.move_heading": "Mover a otra estantería",
    "ui.move": "Mover",
    "ui.edit_book_title": "Editar libro",
    "ui.edit_page_title": "Editar :title",
    "ui.scanner_error": "No se pudo acceder a la cámara. Escribe el ISBN manualmente.",
    "ui.settings_title": "Configuraciones",
    "ui.profile_heading": "Perfil",
    "ui.name": "Nombre",
    "ui.email": "Correo",
    "ui.covers": "Portadas",
    "ui.spines": "Lomos",
    "ui.save_profile": "Guardar perfil",
    "ui.password_heading": "Contraseña",
    "ui.current_password": "Contraseña actual",
    "ui.new_password": "Nueva contraseña",
    "ui.confirm_password": "Confirmar contraseña",
    "ui.change_password": "Cambiar contraseña",
    "ui.shelves_heading": "Estanterías",
    "ui.new_shelf_placeholder": "Nombre de la nueva estantería",
    "ui.create": "Crear",
    "ui.rename": "Renombrar",
    "ui.up": "Subir",
    "ui.down": "Bajar",
    "ui.delete_shelf_confirm": "¿Eliminar la estantería «:name»? Sus libros se moverán a otra estantería.",
    "ui.language_heading": "Idioma",
    "ui.language_help": "Elige el idioma de la aplicación.",
    "ui.language_apply": "Aplicar idioma",
    "ui.language_preparing": "Preparando idioma… :percent%",
    "ui.language_progress": ":percent% completado",
    "ui.language_prepare_failed": "No se pudo preparar el idioma. Se mantuvo tu idioma actual.",
    "ui.language_updated": "Idioma actualizado.",
    "ui.language_reviewed": "Revisado",
    "ui.language_automatic": "Automático",
    "ui.language_search": "Buscar idioma",
    "ui.language_no_results": "No hay idiomas que coincidan.",
    "ui.language_current": "Idioma actual",
    "ui.language_select": "Selecciona un idioma",
    "ui.profile_title": "Perfil",
    "ui.profile_info_heading": "Información del perfil",
    "ui.profile_info_help": "Actualiza la información de tu cuenta y tu correo electrónico.",
    "ui.profile_unverified": "Tu correo no está verificado.",
    "ui.profile_resend": "Haz clic aquí para reenviar el correo de verificación.",
    "ui.profile_verification_sent": "Se envió un nuevo enlace de verificación a tu correo.",
    "ui.profile_saved": "Guardado.",
    "ui.profile_password_heading": "Actualizar contraseña",
    "ui.profile_password_help": "Asegúrate de usar una contraseña larga y aleatoria para mantener tu cuenta segura.",
    "ui.profile_delete_heading": "Eliminar cuenta",
    "ui.profile_delete_help": "Una vez eliminada tu cuenta, todos sus recursos y datos se borrarán permanentemente. Antes de eliminarla, descarga cualquier dato que quieras conservar.",
    "ui.profile_delete_button": "Eliminar cuenta",
    "ui.profile_delete_confirm_title": "¿Seguro que quieres eliminar tu cuenta?",
    "ui.profile_delete_confirm_help": "Una vez eliminada tu cuenta, todos sus recursos y datos se borrarán permanentemente. Escribe tu contraseña para confirmar.",
    "ui.profile_password": "Contraseña",
    "ui.cancel": "Cancelar"
}
```

- [ ] **Paso 2: Crear las traducciones revisadas**

Crear los 7 archivos `lang/{en,pt,fr,de,it,ca,gl}.json` con **todas** las claves de `lang/es.json` más las claves aplanadas de `validation`, `auth` y `passwords`. Para generarlas, usar como referencia `lang/es.json`, `lang/es/validation.php`, `lang/es/auth.php` y `lang/es/passwords.php`. No dejar ninguna clave sin traducir.

Ejemplo de estructura para `lang/en.json`:

```json
{
    "ui.nav_library": "Library",
    "ui.nav_add": "Add",
    "validation.required": ":attribute is required.",
    "validation.attributes.title": "the title",
    "auth.failed": "These credentials do not match our records.",
    "passwords.reset": "Your password has been reset."
}
```

- [ ] **Paso 3: Crear `CatalogTranslationService`**

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CatalogTranslationService
{
    public function __construct(private CatalogService $catalog) {}

    public function translateKeys(string $locale, array $keys): array
    {
        $source = $this->catalog->source();
        $staging = $this->staging($locale);
        $pending = [];

        foreach ($keys as $key) {
            if (! isset($source[$key]) || isset($staging[$key])) {
                continue;
            }

            $cached = Cache::get($this->cacheKey($locale, $source[$key]));

            if (is_string($cached) && $cached !== '') {
                $staging[$key] = $cached;

                continue;
            }

            $pending[$key] = $source[$key];
        }

        if ($pending !== []) {
            $translated = $this->fetch($pending, $locale);

            if (count($translated) !== count($pending)) {
                $this->saveStaging($locale, $staging);

                return ['remaining' => count($this->catalog->missing($locale)), 'failed' => count($pending) - count($translated)];
            }

            foreach ($translated as $key => $value) {
                $staging[$key] = $value;
                Cache::put($this->cacheKey($locale, $source[$key]), $value, now()->addDays(30));
            }
        }

        $this->saveStaging($locale, $staging);

        return ['remaining' => count($this->catalog->missing($locale)), 'failed' => 0];
    }

    public function nextKeys(string $locale, int $limit = 20): array
    {
        return array_slice($this->catalog->missing($locale), 0, $limit);
    }

    public function finalize(string $locale): bool
    {
        $staging = $this->staging($locale);

        if (count(array_diff($this->catalog->keys(), array_keys($staging))) > 0) {
            return false;
        }

        $this->catalog->writeGenerated($locale, $staging);
        File::delete($this->stagingPath($locale));

        return true;
    }

    private function fetch(array $items, string $locale): array
    {
        $target = config("languages.supported.{$locale}", $locale);
        $responses = [];

        try {
            $responses = Http::pool(function ($pool) use ($items, $target) {
                foreach ($items as $key => $text) {
                    [$protected, $tokens] = $this->protect($text);

                    $pool->as($key)
                        ->timeout(8)
                        ->get('https://api.mymemory.translated.net/get', $this->params($protected, $target));
                }
            });
        } catch (\Throwable $e) {
            Log::warning('No se pudo preparar el catálogo: '.$e->getMessage());

            return [];
        }

        $result = [];

        foreach ($items as $key => $text) {
            $response = $responses[$key] ?? null;

            if (! $response || ! $response->ok()) {
                continue;
            }

            $translated = $response->json('responseData.translatedText');

            if (! is_string($translated)
                || trim($translated) === ''
                || str_contains(strtolower($translated), 'mymemory warning')) {
                continue;
            }

            [$protected, $tokens] = $this->protect($text);
            $translated = html_entity_decode(trim($translated), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            foreach ($tokens as $token => $original) {
                $translated = str_replace($token, $original, $translated);
            }

            foreach (array_keys($tokens) as $token) {
                if (! str_contains($translated, $token) && ! str_contains($text, $token)) {
                    continue 2;
                }
            }

            $result[$key] = $translated;
        }

        return $result;
    }

    private function protect(string $text): array
    {
        $tokens = [];
        $protected = preg_replace_callback('/:[a-zA-Z_][a-zA-Z0-9_]*|\|/', function ($match) use (&$tokens) {
            $token = '__T'.count($tokens).'__';
            $tokens[$token] = $match[0];

            return $token;
        }, $text);

        return [$protected, $tokens];
    }

    private function params(string $text, string $target): array
    {
        $params = [
            'q' => $text,
            'langpair' => 'es-ES|'.$target,
        ];

        if ($email = config('services.mymemory.email')) {
            $params['de'] = $email;
        }

        return $params;
    }

    private function staging(string $locale): array
    {
        $path = $this->stagingPath($locale);

        if (! is_file($path)) {
            return [];
        }

        $data = json_decode(File::get($path), true);

        return is_array($data) ? $data : [];
    }

    private function saveStaging(string $locale, array $lines): void
    {
        $path = $this->stagingPath($locale);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($lines, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function stagingPath(string $locale): string
    {
        return storage_path("app/i18n/pending/{$locale}.json");
    }

    private function cacheKey(string $locale, string $text): string
    {
        return 'catalog:'.$locale.':'.md5($text);
    }
}
```

- [ ] **Paso 4: Crear `UpdateLocaleRequest`**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', Rule::in(array_keys(config('languages.supported')))],
        ];
    }
}
```

- [ ] **Paso 5: Crear `LocaleController`**

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocaleRequest;
use App\Services\CatalogService;
use App\Services\CatalogTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function __construct(
        private CatalogService $catalog,
        private CatalogTranslationService $translator,
    ) {}

    public function prepare(UpdateLocaleRequest $request): JsonResponse
    {
        $locale = $request->validated('locale');
        $missing = $this->catalog->missing($locale);

        return response()->json([
            'ready' => $missing === [],
            'missing' => count($missing),
            'keys' => array_slice($missing, 0, 20),
        ]);
    }

    public function translate(UpdateLocaleRequest $request): JsonResponse
    {
        $locale = $request->validated('locale');
        $keys = $request->validate([
            'keys' => ['required', 'array', 'max:20'],
            'keys.*' => ['string'],
        ])['keys'];

        $source = $this->catalog->source();
        $keys = array_values(array_filter($keys, fn ($key) => isset($source[$key])));

        $result = $this->translator->translateKeys($locale, $keys);
        $remaining = $this->catalog->missing($locale);

        return response()->json([
            'remaining' => count($remaining),
            'keys' => array_slice($remaining, 0, 20),
            'failed' => $result['failed'],
        ]);
    }

    public function update(UpdateLocaleRequest $request): RedirectResponse
    {
        $locale = $request->validated('locale');

        if (! $this->catalog->isComplete($locale)) {
            return back()->withErrors(['locale' => __('ui.language_prepare_failed')]);
        }

        if ($locale !== 'es' && ! in_array($locale, config('languages.curated'), true)) {
            $this->translator->finalize($locale);
        }

        $request->user()->update(['locale' => $locale]);

        return back()->with('success', __('ui.language_updated'));
    }
}
```

- [ ] **Paso 6: Rutas**

En `routes/web.php` dentro del grupo `auth`:

```php
use App\Http\Controllers\LocaleController;

Route::post('/settings/locale/prepare', [LocaleController::class, 'prepare'])
    ->middleware('throttle:30,1')
    ->name('settings.locale.prepare');
Route::post('/settings/locale/translate', [LocaleController::class, 'translate'])
    ->middleware('throttle:30,1')
    ->name('settings.locale.translate');
Route::patch('/settings/locale', [LocaleController::class, 'update'])
    ->name('settings.locale');
```

- [ ] **Paso 7: Pruebas de catálogo**

Crear `tests/Feature/CatalogTest.php`:

```php
<?php

use App\Services\CatalogService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

it('tiene todas las claves en los idiomas revisados', function () {
    $catalog = app(CatalogService::class);
    $keys = $catalog->keys();

    foreach (config('languages.curated') as $locale) {
        $missing = array_diff($keys, array_keys($catalog->all($locale)));

        expect($missing)->toBe([], "Faltan claves en {$locale}");
    }
});

it('prepara un idioma automático por lotes', function () {
    $catalog = app(CatalogService::class);

    Http::fake([
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => ['translatedText' => 'Traducido'],
        ]),
    ]);

    $missing = $catalog->missing('af');
    $batch = array_slice($missing, 0, 2);

    app(\App\Services\CatalogTranslationService::class)->translateKeys('af', $batch);

    expect(count($catalog->missing('af')))->toBeLessThan(count($missing));
});

it('no activa un idioma incompleto', function () {
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user)
        ->patch(route('settings.locale'), ['locale' => 'af'])
        ->assertSessionHasErrors('locale');

    expect($user->fresh()->locale)->toBe('es');
});
```

- [ ] **Paso 8: Pruebas de endpoints**

Crear `tests/Feature/LocaleSettingsTest.php`:

```php
<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('aplica un idioma revisado', function () {
    $this->patch(route('settings.locale'), ['locale' => 'en'])
        ->assertRedirect();

    expect($this->user->fresh()->locale)->toBe('en');
});

it('rechaza un idioma no soportado', function () {
    $this->patch(route('settings.locale'), ['locale' => 'xx'])
        ->assertSessionHasErrors('locale');
});

it('prepara un idioma automático', function () {
    $this->post(route('settings.locale.prepare'), ['locale' => 'af'])
        ->assertOk()
        ->assertJsonPath('ready', false);
});
```

- [ ] **Paso 9: Ejecutar**

```bash
php artisan test tests/Feature/CatalogTest.php tests/Feature/LocaleSettingsTest.php
```

Esperado: todas pasan.

---

## Tarea 4: Infraestructura i18n de Vue

**Archivos:**
- Crear `resources/js/i18n.js`
- Crear `resources/js/Components/LocaleSync.vue`
- Modificar `resources/js/Components/AppShell.vue`
- Modificar `resources/views/app.blade.php`

- [ ] **Paso 1: Crear `resources/js/i18n.js`**

```js
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useI18n() {
    const page = usePage();
    const messages = computed(() => page.props.translations ?? {});

    function t(key, replacements = {}) {
        let text = messages.value[key] ?? key;

        for (const [name, value] of Object.entries(replacements)) {
            text = text.replaceAll(`:${name}`, String(value));
        }

        return text;
    }

    return { t };
}
```

- [ ] **Paso 2: Crear `LocaleSync.vue`**

```vue
<script setup>
import { usePage } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

const page = usePage();

watchEffect(() => {
    document.documentElement.lang = page.props.locale ?? 'es';
    document.documentElement.dir = page.props.direction ?? 'ltr';
});
</script>

<template>
    <span class="hidden" aria-hidden="true" />
</template>
```

- [ ] **Paso 3: Añadir `dir` al HTML**

En `resources/views/app.blade.php`:

```blade
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' || app()->getLocale() === 'he' ? 'rtl' : 'ltr' }}">
```

- [ ] **Paso 4: Usar `LocaleSync` en `AppShell`**

```vue
<script setup>
import LocaleSync from './LocaleSync.vue';
</script>
```

Dentro del `<div>` raíz:

```vue
<LocaleSync />
```

- [ ] **Paso 5: Verificar build**

```bash
npm run build
```

Esperado: build correcto.

---

## Tarea 5: Selector de idioma y Configuraciones

**Archivos:**
- Crear `resources/js/Components/LanguageSelect.vue`
- Modificar `resources/js/Pages/Settings/Index.vue`
- Modificar `app/Http/Controllers/SettingsController.php`
- Probar `resources/js/Components/__tests__/LanguageSelect.test.js`

- [ ] **Paso 1: Crear `LanguageSelect.vue`**

```vue
<script setup>
import { computed, ref } from 'vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    modelValue: { type: String, required: true },
    languages: { type: Array, required: true },
    curated: { type: Array, default: () => [] },
    locale: { type: String, default: 'es' },
});

const emit = defineEmits(['update:modelValue']);
const { t } = useI18n();
const query = ref('');

const names = computed(() => {
    try {
        return new Intl.DisplayNames([props.locale], { type: 'language' });
    } catch {
        return null;
    }
});

function label(code) {
    try {
        return names.value?.of(code) ?? code;
    } catch {
        return code;
    }
}

const filtered = computed(() => {
    const term = query.value.trim().toLowerCase();

    return props.languages
        .map((code) => ({ code, label: label(code), curated: props.curated.includes(code) }))
        .filter((item) => !term || item.label.toLowerCase().includes(term) || item.code.includes(term))
        .sort((a, b) => {
            if (a.curated !== b.curated) return a.curated ? -1 : 1;
            return a.label.localeCompare(b.label, props.locale);
        });
});
</script>

<template>
    <div class="space-y-2">
        <input
            v-model="query"
            type="search"
            class="w-full rounded-control border border-madera/35 bg-crema px-3 py-2"
            :placeholder="t('ui.language_search')"
            :aria-label="t('ui.language_search')"
        >
        <div class="max-h-64 overflow-y-auto rounded-control border border-madera/35 bg-crema">
            <button
                v-for="item in filtered"
                :key="item.code"
                type="button"
                class="flex w-full items-center justify-between gap-2 border-b border-madera/15 px-3 py-2 text-start last:border-b-0 hover:bg-pergamino"
                :class="item.code === modelValue ? 'bg-pergamino font-semibold' : ''"
                @click="emit('update:modelValue', item.code)"
            >
                <span>{{ item.label }}</span>
                <span class="text-xs text-tinta/60">
                    {{ item.curated ? t('ui.language_reviewed') : t('ui.language_automatic') }}
                </span>
            </button>
            <p v-if="filtered.length === 0" class="p-3 text-sm text-tinta/60">
                {{ t('ui.language_no_results') }}
            </p>
        </div>
    </div>
</template>
```

- [ ] **Paso 2: Pasar idiomas desde `SettingsController`**

```php
public function index(Request $request): Response
{
    return Inertia::render('Settings/Index', [
        'user' => $request->user(),
        'shelves' => $request->user()->shelves()->orderBy('position')->get(),
        'locale' => app()->getLocale(),
        'languages' => array_keys(config('languages.supported')),
        'curated' => config('languages.curated'),
    ]);
}
```

- [ ] **Paso 3: Añadir la sección Idioma a `Settings/Index.vue`**

En `<script setup>`:

```js
import axios from 'axios';
import { useI18n } from '@/i18n';
import LanguageSelect from '@/Components/LanguageSelect.vue';

const { t } = useI18n();
const language = ref(props.locale);
const preparing = ref(false);
const progress = ref(0);
const languageError = ref('');
const languageSuccess = ref(false);

async function applyLanguage() {
    if (language.value === props.locale) return;

    preparing.value = true;
    progress.value = 0;
    languageError.value = '';
    languageSuccess.value = false;

    try {
        const prepare = await axios.post('/settings/locale/prepare', { locale: language.value });
        const total = prepare.data.missing;
        let keys = prepare.data.keys;

        while (keys.length > 0) {
            const response = await axios.post('/settings/locale/translate', {
                locale: language.value,
                keys,
            });

            if (response.data.failed > 0) {
                throw new Error('translation-failed');
            }

            keys = response.data.keys;
            progress.value = total === 0 ? 100 : Math.round(((total - response.data.remaining) / total) * 100);
        }

        await router.patch('/settings/locale', { locale: language.value }, {
            preserveScroll: true,
            onSuccess: () => {
                languageSuccess.value = true;
            },
        });
    } catch {
        languageError.value = t('ui.language_prepare_failed');
    } finally {
        preparing.value = false;
    }
}
```

En la plantilla, antes de Estanterías:

```vue
<section class="mb-8 space-y-3">
    <h2 class="text-2xl">{{ t('ui.language_heading') }}</h2>
    <p class="text-sm text-tinta/65">{{ t('ui.language_help') }}</p>
    <LanguageSelect
        v-model="language"
        :languages="languages"
        :curated="curated"
        :locale="locale"
        :disabled="preparing"
    />
    <button
        type="button"
        class="rounded-control bg-oliva px-4 py-2 text-crema hover:bg-oliva-hover disabled:opacity-50"
        :disabled="preparing"
        @click="applyLanguage"
    >
        {{ t('ui.language_apply') }}
    </button>
    <p v-if="preparing" class="text-sm text-tinta/65">
        {{ t('ui.language_preparing', { percent: progress }) }}
    </p>
    <p v-if="languageError" class="text-sm text-terracota">{{ languageError }}</p>
    <p v-if="languageSuccess" class="text-sm text-oliva">{{ t('ui.language_updated') }}</p>
</section>
```

- [ ] **Paso 4: Sustituir los textos existentes de Configuraciones por `t()`**

Usar las claves de `lang/es.json` para todos los textos de `Settings/Index.vue`.

- [ ] **Paso 5: Prueba de componente**

Crear `resources/js/Components/__tests__/LanguageSelect.test.js`:

```js
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import LanguageSelect from '../LanguageSelect.vue';

describe('LanguageSelect', () => {
    it('filtra idiomas y muestra los revisados', async () => {
        const wrapper = mount(LanguageSelect, {
            props: {
                modelValue: 'es',
                languages: ['es', 'en', 'af'],
                curated: ['es', 'en'],
                locale: 'es',
            },
            global: {
                mocks: {
                    $page: { props: { translations: {}, locale: 'es' } },
                },
            },
        });

        expect(wrapper.text()).toContain('Revisado');
        expect(wrapper.text()).toContain('Automático');

        await wrapper.find('input').setValue('ingl');
        expect(wrapper.text()).toContain('English');
    });
});
```

- [ ] **Paso 6: Ejecutar**

```bash
npm run test
```

Esperado: todas las pruebas de frontend pasan.

---

## Tarea 6: Traducir toda la interfaz autenticada

**Archivos:**
- `resources/js/Components/AppShell.vue`
- `resources/js/Components/BookCard.vue`
- `resources/js/Components/BookSpine.vue`
- `resources/js/Components/Shelf.vue`
- `resources/js/Components/EmptyShelf.vue`
- `resources/js/Components/RatingStars.vue`
- `resources/js/Components/BarcodeScanner.vue`
- `resources/js/Pages/Library/Index.vue`
- `resources/js/Pages/Books/Create.vue`
- `resources/js/Pages/Books/Edit.vue`
- `resources/js/Pages/Settings/Index.vue`
- `resources/js/Pages/Profile/Edit.vue`
- `resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.vue`
- `resources/js/Pages/Profile/Partials/UpdatePasswordForm.vue`
- `resources/js/Pages/Profile/Partials/DeleteUserForm.vue`

- [ ] **Paso 1: En cada archivo, importar y usar `useI18n`**

```js
import { useI18n } from '@/i18n';

const { t } = useI18n();
```

- [ ] **Paso 2: Sustituir textos, placeholders, títulos, `aria-label`, `alt` y `window.confirm`**

Correspondencia obligatoria:

| Archivo | Texto actual | Clave |
|---|---|---|
| AppShell | Biblioteca (nav) | `ui.nav_library` |
| AppShell | Añadir | `ui.nav_add` |
| AppShell | Ajustes | `ui.nav_settings` |
| AppShell | Cerrar sesión | `ui.logout` |
| AppShell | Salir | `ui.exit` |
| Library/Index | Mi biblioteca | `ui.library_title` |
| Library/Index | Añadir libro | `ui.add_book` |
| Shelf | N libros | `ui.books_count` con `{ count }` |
| Shelf | 1 libro | `ui.one_book` |
| Shelf | Ver lomos / Ver portadas | `ui.show_spines` / `ui.show_covers` |
| EmptyShelf | Aún no hay libros… | `ui.empty_shelf` |
| BookCard/BookSpine/Create/Edit | Portada de :title | `ui.cover_alt` |
| BookSpine | autor desconocido | `ui.unknown_author_lower` |
| Create | Autor desconocido | `ui.unknown_author` |
| RatingStars | :rating de 5 estrellas | `ui.rating_label` |
| RatingStars | :star de 5 estrellas | `ui.rating_star` |
| Create/Edit | Valoración | `ui.rating_heading` |
| Create | Buscar / Manual | `ui.search_tab` / `ui.manual_tab` |
| Create | Cerrar cámara | `ui.close_camera` |
| Create | Escanear código… | `ui.scan_camera` |
| Create | Buscar ISBN | `ui.search_isbn` |
| Create | Título o autor | `ui.search_placeholder` |
| Create | Buscando… | `ui.searching` |
| Create | Escribe al menos 3 letras… | `ui.search_hint` |
| Create | No encontramos ese ISBN… | `ui.isbn_not_found` |
| Create | No pudimos consultar el catálogo… | `ui.catalog_error` |
| Create | Revisa los datos… | `ui.check_data` |
| Create | No encontramos resultados… | `ui.no_results` |
| Create/Edit | Título *, Autor, ISBN, Editorial, Año, Notas | `ui.title_placeholder`, `ui.author_placeholder`, `ui.isbn`, `ui.publisher_placeholder`, `ui.year_placeholder`, `ui.notes_placeholder` |
| Create/Edit | Guardar, Eliminar, Mover | `ui.save`, `ui.delete`, `ui.move` |
| Edit | Editar libro | `ui.edit_book_title` |
| Edit | Editar :title | `ui.edit_page_title` |
| Edit | ¿Eliminar este libro…? | `ui.delete_book_confirm` |
| Edit | Mover a otra estantería | `ui.move_heading` |
| BarcodeScanner | No se pudo acceder… | `ui.scanner_error` |
| Settings | Configuraciones, Perfil, Nombre, Correo, Portadas, Lomos, Guardar perfil, Contraseña, Contraseña actual, Nueva contraseña, Confirmar contraseña, Cambiar contraseña, Estanterías, Nombre de la nueva estantería, Crear, Renombrar, Subir, Bajar, Eliminar, confirmación | claves `ui.settings_title`, `ui.profile_heading`, `ui.name`, `ui.email`, `ui.covers`, `ui.spines`, `ui.save_profile`, `ui.password_heading`, `ui.current_password`, `ui.new_password`, `ui.confirm_password`, `ui.change_password`, `ui.shelves_heading`, `ui.new_shelf_placeholder`, `ui.create`, `ui.rename`, `ui.up`, `ui.down`, `ui.delete`, `ui.delete_shelf_confirm` |
| Profile/Edit | Profile | `ui.profile_title` |
| UpdateProfileInformationForm | Profile Information, Update your account's…, Name, Email, Save, Saved., unverified, resend, sent | `ui.profile_info_heading`, `ui.profile_info_help`, `ui.name`, `ui.email`, `ui.save`, `ui.profile_saved`, `ui.profile_unverified`, `ui.profile_resend`, `ui.profile_verification_sent` |
| UpdatePasswordForm | Update Password, Ensure…, Current Password, New Password, Confirm Password, Save, Saved. | `ui.profile_password_heading`, `ui.profile_password_help`, `ui.current_password`, `ui.new_password`, `ui.confirm_password`, `ui.save`, `ui.profile_saved` |
| DeleteUserForm | Delete Account, Once…, Are you sure…?, Password, Cancel | `ui.profile_delete_heading` o `ui.profile_delete_button`, `ui.profile_delete_help`, `ui.profile_delete_confirm_title`, `ui.profile_password`, `ui.cancel`, `ui.profile_delete_button` |

- [ ] **Paso 3: Usar `display_name` en estanterías**

En `Shelf.vue`, `Create.vue` y `Edit.vue`:

```vue
{{ shelf.display_name ?? shelf.name }}
```

En `Shelf.vue`, el conteo:

```js
const countLabel = computed(() =>
    props.shelf.books.length === 1
        ? t('ui.one_book')
        : t('ui.books_count', { count: props.shelf.books.length })
);
```

- [ ] **Paso 4: Ejecutar pruebas y build**

```bash
npm run test
npm run build
```

Esperado: todo pasa.

---

## Tarea 7: Traducción de nombres de estanterías

**Archivos:**
- Modificar `app/Http/Controllers/LibraryController.php`
- Modificar `app/Http/Controllers/BookController.php`
- Modificar `app/Services/TranslationService.php` (ya tiene `translateText`)
- Probar `tests/Feature/ShelfTranslationTest.php`

- [ ] **Paso 1: Añadir `display_name` en `LibraryController`**

```php
use App\Services\TranslationService;

public function index(Request $request, TranslationService $translator): Response
{
    $shelves = $request->user()
        ->shelves()
        ->with('books')
        ->get()
        ->each(function ($shelf) use ($translator) {
            $shelf->setAttribute(
                'display_name',
                app()->getLocale() === 'es'
                    ? $shelf->name
                    : ($translator->translateText($shelf->name, app()->getLocale(), 'es') ?? $shelf->name)
            );
        });

    return Inertia::render('Library/Index', [
        'shelves' => $shelves,
    ]);
}
```

- [ ] **Paso 2: Añadir `display_name` en `BookController`**

En `create()` y `edit()`:

```php
private function shelvesFor(Request $request, TranslationService $translator): array
{
    return $request->user()->shelves()
        ->orderBy('position')
        ->get(['id', 'name'])
        ->map(function ($shelf) use ($translator) {
            $shelf->display_name = app()->getLocale() === 'es'
                ? $shelf->name
                : ($translator->translateText($shelf->name, app()->getLocale(), 'es') ?? $shelf->name);

            return $shelf;
        })
        ->all();
}
```

Inyectar `TranslationService` en el constructor junto a `CoverService` y usar `$this->shelvesFor($request, $translator)`.

- [ ] **Paso 3: Prueba**

```php
<?php

use App\Models\Shelf;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('traduce el nombre de la estantería sin cambiar el original', function () {
    $user = User::factory()->create(['locale' => 'en']);
    $shelf = Shelf::factory()->for($user)->create(['name' => 'Pendientes']);

    Http::fake([
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => ['translatedText' => 'Pending'],
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('library.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('shelves.0.display_name', 'Pending'));

    expect($shelf->fresh()->name)->toBe('Pendientes');
});
```

- [ ] **Paso 4: Ejecutar**

```bash
php artisan test tests/Feature/ShelfTranslationTest.php
```

Esperado: pasa.

---

## Tarea 8: RTL básico

**Archivos:**
- Modificar `resources/js/Components/AppShell.vue`
- Modificar `resources/js/Pages/Settings/Index.vue`
- Modificar `resources/js/Pages/Books/Create.vue`
- Modificar `resources/js/Pages/Books/Edit.vue`
- Modificar `resources/js/Components/Shelf.vue`

- [ ] **Paso 1: Sustituir utilidades direccionales**

Cambiar, cuando existan:

- `ml-*` → `ms-*`
- `mr-*` → `me-*`
- `pl-*` → `ps-*`
- `pr-*` → `pe-*`
- `left-*` → `start-*`
- `right-*` → `end-*`
- `text-left` → `text-start`
- `text-right` → `text-end`
- `border-l-*` → `border-s-*`
- `border-r-*` → `border-e-*`

- [ ] **Paso 2: Ajustar la navegación**

En `AppShell.vue`, la barra lateral y la navegación inferior deben usar `border-s-2` y `text-start`. Verificar que los enlaces sigan alineados en RTL.

- [ ] **Paso 3: Verificar visualmente**

```bash
npm run dev
```

Revisar con `locale = ar` (o un HTML con `dir="rtl"`) que:
- La barra lateral queda a la derecha.
- Los formularios se leen de derecha a izquierda.
- Las estanterías no se solapan.
- Los botones siguen siendo usables.

- [ ] **Paso 4: Build**

```bash
npm run build
```

Esperado: build correcto.

---

## Tarea 9: Documentación

**Archivos:**
- `docs/DISENO.md`
- `docs/DESPLIEGUE.md`
- `.env.example`
- `AGENTS.md`

- [ ] **Paso 1: Actualizar `docs/DISENO.md`**

Añadir al final, con fecha 2026-10-26:

- Nueva sección «Idioma» en Configuraciones.
- Componente `LanguageSelect`: buscador, etiquetas «Revisado»/«Automático», estados de progreso y error.
- Soporte RTL: dirección, utilidades lógicas y revisión de layout.
- Cualquier ajuste visual realizado en la tarea 8.
- Registrar el cambio en la tabla de cambios del documento.

- [ ] **Paso 2: Actualizar `docs/DESPLIEGUE.md`**

Documentar:

- `MYMEMORY_EMAIL` para ampliar el límite diario.
- `storage/app/i18n` debe ser escribible.
- Los idiomas automáticos se generan una vez y se guardan en el servidor.
- Si se borra `storage/app/i18n`, se regeneran al elegir el idioma.

- [ ] **Paso 3: Actualizar `.env.example`**

Añadir:

```env
MYMEMORY_EMAIL=
```

- [ ] **Paso 4: Actualizar `AGENTS.md`**

Añadir a convenciones:

```md
- Textos visibles de la app autenticada: siempre con claves de `lang/es.json` y `t('ui.*')`; no escribir cadenas visibles directamente en componentes autenticados.
```

- [ ] **Paso 5: Revisar que no haya textos sueltos**

```bash
Get-ChildItem -Recurse -File resources/js -Include *.vue,*.js | Select-String -Pattern '>[A-ZÁÉÍÓÚÑ¿¡][^<>{]{2,}<'
```

Esperado: solo las pantallas `Auth/*`, `Welcome.vue` y la marca «Biblioteca». Cualquier otro texto visible debe usar `t()`.

---

## Tarea 10: Verificación final

- [ ] **Paso 1: Backend**

```bash
php artisan test
```

Esperado: todas las pruebas pasan.

- [ ] **Paso 2: Formato PHP**

```bash
./vendor/bin/pint
```

Esperado: sin errores.

- [ ] **Paso 3: Frontend**

```bash
npm run test
npm run build
```

Esperado: todas las pruebas pasan y el build termina.

- [ ] **Paso 4: Recorrido manual**

Con una cuenta de prueba:

1. Entrar y comprobar que todo está en español.
2. Configuraciones → Idioma → English → Aplicar.
3. Verificar navegación, biblioteca, formularios, validaciones y nombres de estanterías en inglés.
4. Buscar un libro en inglés y comprobar que el título se muestra en inglés.
5. Cambiar a francés y comprobar que los títulos de búsqueda se traducen al francés.
6. Elegir un idioma automático (por ejemplo, afrikáans) y ver el progreso.
7. Cerrar sesión y comprobar que el login sigue en español.
8. Volver a entrar y comprobar que se conserva el idioma.
9. Revisar 360 px, escritorio y un idioma RTL.
10. Comprobar que una estantería renombrada conserva su nombre original en Configuraciones.

- [ ] **Paso 5: Informar**

Indicar a la usuaria los archivos modificados, las pruebas ejecutadas y los cambios visuales registrados en `docs/DISENO.md`. No hacer commit.
