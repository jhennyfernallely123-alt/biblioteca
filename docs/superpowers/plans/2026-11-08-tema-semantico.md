# Tema claro/oscuro con tokens semánticos — Plan de implementación

> **Para quien ejecute este plan:** ejecutar las tareas en orden. Cada tarea termina con
> pruebas o verificación concreta. **No ejecutar `git add`, `git commit`, `git push` ni
> ninguna operación que modifique el historial: los commits los hace la usuaria**
> (regla 1 de `AGENTS.md`).

**Objetivo:** sustituir la paleta única de noche por un sistema de roles semánticos con
**tema claro pastel por defecto** y **tema oscuro** (la paleta actual), con selector en
Ajustes persistido en la base de datos.

**Arquitectura:** los componentes dejan de nombrar colores concretos (`night`, `gold`,
`moon`…) y usan roles (`page`, `surface`, `ink`, `accent`, `secondary`, `tertiary`…).
Los valores viven en `@theme` dentro de `resources/css/app.css`; `:root[data-theme='dark']`
sobrescribe solo las variables de color. Los tokens físicos (madera, papel, lomo, latón)
no cambian. El tema viaja en `users.theme` y se aplica con `data-theme` en `<html>`.

**Stack:** Laravel 13, Inertia, Vue 3, Tailwind CSS 4, Pest, Vitest.

**Specs aprobadas:**
`docs/superpowers/specs/2026-11-08-tema-semantico-design.md` y
`docs/superpowers/specs/2026-11-08-album-replica-design.md` (fase 2).

**Fuera de alcance:** tipografías, estructura de páginas, lógica de eventos, economía,
ilustraciones. La réplica del álbum se planifica aparte al terminar esta fase.

---

## Tarea 1 — Tokens semánticos y overrides en `app.css`

**Archivos:**
- Modificar: `resources/css/app.css`
- Reescribir: `resources/js/Components/__tests__/palette.test.js`

**Paso 1.1 — Reescribir la prueba de paleta (rojo).**

`palette.test.js` actual afirma los hex de la paleta nocturna; se sustituye por la prueba
del sistema nuevo. Contenido completo:

```js
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';

const css = readFileSync(
    fileURLToPath(new URL('../../../css/app.css', import.meta.url)),
    'utf8',
);

const block = (selector) => {
    const start = css.indexOf(selector);

    return css.slice(start, css.indexOf('}', start));
};

const token = (source, name) =>
    source.match(new RegExp(`--color-${name}:\\s*(#[0-9A-Fa-f]{6})`))?.[1];

const light = block('@theme');
const dark = block(":root[data-theme='dark']");

const expectedLight = {
    page: '#FDF7F0',
    surface: '#FFFFFF',
    'surface-raised': '#FFF8F1',
    'surface-sunken': '#F6EDE3',
    ink: '#3B3630',
    'ink-muted': '#7A7068',
    'ink-soft': '#9A9088',
    heading: '#3B3630',
    'on-accent': '#FFFFFF',
    edge: '#E8D5C4',
    'edge-strong': '#D6BEA8',
    accent: '#DB2777',
    'accent-strong': '#BE185D',
    'accent-soft': '#FDE2E4',
    secondary: '#0F766E',
    'secondary-strong': '#115E59',
    tertiary: '#7C3AED',
    'tertiary-strong': '#6D28D9',
    danger: '#E11D48',
    'danger-strong': '#BE123C',
    success: '#047857',
    warning: '#B45309',
    ring: '#7C3AED',
    scrim: '#1C1A24',
};

const expectedDark = {
    page: '#12101F',
    surface: '#211D3A',
    'surface-raised': '#1A1730',
    'surface-sunken': '#191632',
    ink: '#F1ECFF',
    'ink-muted': '#B7AED6',
    'ink-soft': '#8C82B8',
    heading: '#E8C887',
    'on-accent': '#FFFFFF',
    edge: '#3A3363',
    'edge-strong': '#4A4278',
    accent: '#E8C887',
    'accent-strong': '#D4B271',
    'accent-soft': '#3A3350',
    secondary: '#74D8C9',
    'secondary-strong': '#5FC7B8',
    tertiary: '#7C5CE0',
    'tertiary-strong': '#6A4AC9',
    danger: '#F2A0B0',
    'danger-strong': '#A8455C',
    success: '#8FE3B8',
    warning: '#E8C887',
    ring: '#74D8C9',
    scrim: '#0A0814',
};

const physical = {
    bosque: '#2F4A3C',
    salvia: '#7A9A7E',
    oliva: '#556B4F',
    'oliva-hover': '#465A41',
    madera: '#6B4F3A',
    'madera-clara': '#8B6B4F',
    pergamino: '#F3E9D2',
    crema: '#FAF4E6',
    tinta: '#2B2620',
    terracota: '#A9714B',
    dorado: '#C2A878',
    malva: '#9E61AB',
    laton: '#E8C887',
};

describe('paleta de la aplicación', () => {
    it('define el tema claro por defecto', () => {
        for (const [name, value] of Object.entries(expectedLight)) {
            expect(token(light, name), name).toBe(value);
        }
    });

    it('sobrescribe la paleta en el tema oscuro', () => {
        for (const [name, value] of Object.entries(expectedDark)) {
            expect(token(dark, name), name).toBe(value);
        }
    });

    it('conserva los colores físicos del mueble', () => {
        for (const [name, value] of Object.entries(physical)) {
            expect(token(light, name), name).toBe(value);
        }
    });

    it('deriva las sombras del token scrim', () => {
        expect(css).toMatch(/--shadow-soft:[^;]*var\(--color-scrim\)/);
        expect(css).toMatch(/--shadow-card:[^;]*var\(--color-scrim\)/);
        expect(css).toMatch(/--shadow-cover:[^;]*var\(--color-scrim\)/);
        expect(css).toMatch(/--shadow-modal:[^;]*var\(--color-scrim\)/);
    });
});
```

**Paso 1.2 — Ejecutar la prueba y confirmar que falla.**

```powershell
npm run test -- --run resources/js/Components/__tests__/palette.test.js
```

Esperado: falla porque `--color-page` y el bloque `[data-theme='dark']` aún no existen.

**Paso 1.3 — Añadir los tokens nuevos manteniendo los heredados.**

En `@theme` de `resources/css/app.css`, **insertar antes de los tokens físicos**
(no borrar todavía `night`…`orquidea`, los usa la migración de la tarea 2):

```css
    /* Roles semánticos — tema claro por defecto */
    --color-page: #FDF7F0;
    --color-surface: #FFFFFF;
    --color-surface-raised: #FFF8F1;
    --color-surface-sunken: #F6EDE3;
    --color-ink: #3B3630;
    --color-ink-muted: #7A7068;
    --color-ink-soft: #9A9088;
    --color-heading: #3B3630;
    --color-on-accent: #FFFFFF;
    --color-edge: #E8D5C4;
    --color-edge-strong: #D6BEA8;
    --color-accent: #DB2777;
    --color-accent-strong: #BE185D;
    --color-accent-soft: #FDE2E4;
    --color-secondary: #0F766E;
    --color-secondary-strong: #115E59;
    --color-tertiary: #7C3AED;
    --color-tertiary-strong: #6D28D9;
    --color-danger: #E11D48;
    --color-danger-strong: #BE123C;
    --color-success: #047857;
    --color-warning: #B45309;
    --color-ring: #7C3AED;
    --color-scrim: #1C1A24;
```

Sustituir las sombras existentes (líneas con `--shadow-*`) por:

```css
    --shadow-soft: 0 1px 2px color-mix(in srgb, var(--color-scrim) 12%, transparent);
    --shadow-card: 0 2px 6px color-mix(in srgb, var(--color-scrim) 16%, transparent);
    --shadow-cover: 0 8px 20px color-mix(in srgb, var(--color-scrim) 22%, transparent);
    --shadow-modal: 0 20px 50px color-mix(in srgb, var(--color-scrim) 35%, transparent);
```

Después del cierre `}` de `@theme`, añadir:

```css
:root[data-theme='light'] {
    color-scheme: light;
}

:root[data-theme='dark'] {
    color-scheme: dark;
    --color-page: #12101F;
    --color-surface: #211D3A;
    --color-surface-raised: #1A1730;
    --color-surface-sunken: #191632;
    --color-ink: #F1ECFF;
    --color-ink-muted: #B7AED6;
    --color-ink-soft: #8C82B8;
    --color-heading: #E8C887;
    --color-on-accent: #FFFFFF;
    --color-edge: #3A3363;
    --color-edge-strong: #4A4278;
    --color-accent: #E8C887;
    --color-accent-strong: #D4B271;
    --color-accent-soft: #3A3350;
    --color-secondary: #74D8C9;
    --color-secondary-strong: #5FC7B8;
    --color-tertiary: #7C5CE0;
    --color-tertiary-strong: #6A4AC9;
    --color-danger: #F2A0B0;
    --color-danger-strong: #A8455C;
    --color-success: #8FE3B8;
    --color-warning: #E8C887;
    --color-ring: #74D8C9;
    --color-scrim: #0A0814;
}
```

**Paso 1.4 — Actualizar la capa base al tema claro.**

En la capa `@layer base`, cambiar:

```css
body {
    @apply bg-page text-ink font-sans antialiased;
}

h1, h2, h3 {
    @apply font-serif text-heading;
}
```

y el foco visible a `ring-ring ring-offset-page` (mantener el resto de reglas).

**Paso 1.5 — Verificar.**

```powershell
npm run test -- --run resources/js/Components/__tests__/palette.test.js
npm run build
```

Esperado: prueba en verde y build correcto.

---

## Tarea 2 — Migración mecánica de clases y variables

**Archivos:** todos los `.vue`, `.js` y `.css` de `resources/js` y `resources/css`,
salvo `resources/js/app.js` y `resources/js/Pages/Welcome.vue` (Breeze, sin tokens del
tema).

**Paso 2.1 — Pre-pasos manuales (el token viejo estaba sobrecargado).**

Aplicar estos reemplazos exactos antes del script:

| Archivo | Antiguo | Nuevo |
|---|---|---|
| `Components/Modal.vue` | `bg-night/80` | `bg-scrim/80` |
| `Components/MagicWelcome.vue` | `bg-night/80` | `bg-scrim/80` |
| `Components/ReadingLightbox.vue` | `bg-night/70` | `bg-scrim/70` |
| `Components/BookCard.vue` | `bg-night/85`, `bg-night/70` | `bg-scrim/85`, `bg-scrim/70` |
| `Components/BookSpine.vue` | `bg-night/85`, `bg-night/70` | `bg-scrim/85`, `bg-scrim/70` |
| `Components/BarcodeScanner.vue` | `bg-night` | `bg-scrim` |
| `Components/DecorationCatalog.vue` | `bg-night/60` | `bg-scrim/60` |
| `Components/CardAlbum.vue` | `bg-night` | `bg-edge` |
| `Pages/Events/Album.vue` | `bg-night/15` | `bg-edge` |
| `Components/PrimaryButton.vue` | `text-night` | `text-on-accent` |
| `Components/Shelf.vue` | `text-night` | `text-on-accent` |
| `css/textures.css` | `linear-gradient(170deg, var(--color-panel), var(--color-night))` | `linear-gradient(170deg, var(--color-scrim), color-mix(in srgb, var(--color-scrim) 75%, #000))` |
| `css/textures.css` | `linear-gradient(170deg, var(--color-panel-raised), var(--color-night-raised))` | `linear-gradient(170deg, color-mix(in srgb, var(--color-scrim) 80%, var(--color-surface)), var(--color-scrim))` |
| `css/textures.css` | `color-mix(in srgb, var(--color-night) 70%, transparent)` | `color-mix(in srgb, var(--color-scrim) 70%, transparent)` |

Comprobar con búsqueda que no queden `bg-night` genéricos en componentes Vue:

```powershell
Get-ChildItem -Path resources/js -Recurse -Include *.vue | Select-String -Pattern "bg-night"
```

Esperado: solo `Pages/Welcome.vue` (excluido).

**Paso 2.2 — Script de migración.**

Crear `_migrate_tokens.php` en la raíz:

```php
<?php

$root = dirname(__DIR__);
$legacy = [
    'night-raised' => 'surface-raised',
    'panel-raised' => 'surface-raised',
    'magic-strong' => 'secondary-strong',
    'arcane-strong' => 'tertiary-strong',
    'night' => 'page',
    'panel' => 'surface',
    'line' => 'edge',
    'moon' => 'ink',
    'mist' => 'ink-muted',
    'gold' => 'accent',
    'magic' => 'secondary',
    'arcane' => 'tertiary',
    'orquidea' => 'tertiary',
];

$skip = [
    'resources/js/Pages/Welcome.vue',
    'resources/js/app.js',
];

$prefixes = ['ring-offset', 'ring', 'bg', 'text', 'border', 'from', 'via', 'to', 'fill', 'stroke', 'divide', 'outline', 'decoration', 'placeholder', 'file', 'shadow'];
$prefix = implode('|', array_map(fn ($p) => preg_quote($p, '/'), $prefixes));

$changed = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root.'/resources', FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (! $file->isFile()) {
        continue;
    }

    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
    $extension = $file->getExtension();

    if (! in_array($extension, ['vue', 'js', 'css'], true)) {
        continue;
    }

    if (str_contains($relative, '__tests__') || in_array($relative, $skip, true)) {
        continue;
    }

    $content = file_get_contents($file->getPathname());
    $original = $content;

    foreach ($legacy as $old => $new) {
        $content = str_replace("var(--color-{$old})", "var(--color-{$new})", $content);

        $pattern = '/(?<![\w-])((?:(?:[a-z0-9-]+):)*)('.$prefix.')-'.preg_quote($old, '/').'(?![\w-])/';
        $content = preg_replace($pattern, '$1$2-'.$new, $content);
    }

    if ($content !== $original) {
        file_put_contents($file->getPathname(), $content);
        $changed++;
    }
}

echo "Archivos modificados: {$changed}".PHP_EOL;
```

**Paso 2.3 — Ejecutar y borrar el script.**

```powershell
php _migrate_tokens.php
Remove-Item _migrate_tokens.php
```

**Paso 2.4 — Comprobaciones.**

```powershell
Get-ChildItem -Path resources/js,resources/css -Recurse -Include *.vue,*.css,*.js | Select-String -Pattern "-(night|night-raised|panel|panel-raised|line|moon|mist|gold|magic|magic-strong|arcane|arcane-strong|orquidea)\b"
```

Revisar que las únicas coincidencias sean nombres propios no-token (por ejemplo
`arcane-sky`, `season-*`). No debe quedar ninguna clase `bg-*`/`text-*`/`border-*` con
tokens heredados.

```powershell
npm run test
npm run build
```

Esperado: 156 pruebas en verde y build correcto. Si una prueba falla por clases, es un
uso real que el script no cubrió: corregirlo con el token semántico correcto.

---

## Tarea 3 — Revisión de texturas y excepciones visuales

**Archivos:**
- Revisar: `resources/css/textures.css`
- Revisar: `resources/js/Components/CardMachine.vue`

**Paso 3.1 — Revisar degradados y velos.**

Comprobar a mano estos bloques de `textures.css` y corregir si el resultado en tema
claro es ilegible:

- `.arcane-sky` (fondo de acceso): ahora `page`; los puntos usan `ink` y `accent`.
  Debe verse como cielo pastel con motas; si las motas quedan demasiado oscuras,
  bajar la opacidad del gradiente (mantener el token).
- `.card-slot` y `.card-back`: velos `scrim`; deben seguir leyéndose sobre el mueble.
- `.card-machine`, `.card-album`: conservan madera y tinta (físicos).
- `.event-item` / `.event-task`: el velo `var(--color-page)` ahora es claro;
  comprobar contraste del texto (`ink`) sobre esas superficies.
- `.auth-book-frame`, `.library-emblem`: usan `tinta`/`pergamino`/`dorado` físicos;
  sin cambios.

**Paso 3.2 — Máquina de cartas.**

`CardMachine.vue` conserva sus materiales 3D (`0x…` en JS) y usa `text-ink-muted` /
`text-accent` tras la migración. Su escenario debe seguir oscuro; si el fondo del panel
quedó claro y el texto no se lee, envolver el panel con `surface-sunken` y `ink`
(sin hex nuevos).

**Paso 3.3 — Pruebas y build.**

```powershell
npm run test
npm run build
```

---

## Tarea 4 — Retirar tokens heredados y añadir guardián

**Archivos:**
- Modificar: `resources/css/app.css`
- Crear: `resources/js/Components/__tests__/theme.test.js`

**Paso 4.1 — Borrar tokens heredados.**

En `@theme` eliminar las líneas de:
`night`, `night-raised`, `panel`, `panel-raised`, `line`, `moon`, `mist`, `gold`,
`magic`, `magic-strong`, `arcane`, `arcane-strong`, `orquidea`.
Mantener `danger`, `danger-strong`, `success` (ya son semánticos) y todos los físicos.

**Paso 4.2 — Crear el guardián.**

Contenido completo de `theme.test.js`:

```js
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { extname, join, sep } from 'node:path';
import { describe, expect, it } from 'vitest';

const roots = ['resources/css', 'resources/js'];
const skipped = new Set([
    join('resources', 'js', 'Pages', 'Welcome.vue'),
    join('resources', 'js', 'app.js'),
]);

const legacy = '(?:night|night-raised|panel|panel-raised|line|moon|mist|gold|magic|magic-strong|arcane|arcane-strong|orquidea)';
const legacyClass = new RegExp(
    `(?:^|[\\s"'!])(?:(?:[a-z0-9-]+):)*(?:ring-offset|ring|bg|text|border|from|via|to|fill|stroke|divide|outline|decoration|placeholder|file|shadow)-${legacy}(?![\\w-])`,
);
const legacyVar = new RegExp(`--color-${legacy}(?![\\w-])`);
const hex = /#[0-9A-Fa-f]{3,8}\b/;

function walk(directory) {
    return readdirSync(directory).flatMap((name) => {
        const path = join(directory, name);

        if (statSync(path).isDirectory()) {
            return path.split(sep).includes('__tests__') ? [] : walk(path);
        }

        return [path];
    }).filter((path) => ['.vue', '.js', '.css'].includes(extname(path)));
}

const files = roots.flatMap(walk).filter((path) => !skipped.has(path));

describe('sistema de temas', () => {
    it('no conserva tokens de la paleta anterior', () => {
        const offenders = files.filter((path) => {
            const content = readFileSync(path, 'utf8');

            return legacyClass.test(content) || legacyVar.test(content);
        });

        expect(offenders).toEqual([]);
    });

    it('no usa hex sueltos en componentes Vue', () => {
        const offenders = files
            .filter((path) => path.endsWith('.vue') && !path.endsWith('CardMachine.vue'))
            .filter((path) => hex.test(readFileSync(path, 'utf8')));

        expect(offenders).toEqual([]);
    });

    it('declara el override del tema oscuro', () => {
        const css = readFileSync('resources/css/app.css', 'utf8');

        expect(css).toContain(":root[data-theme='dark']");
        expect(css).toContain('color-scheme: dark');
    });
});
```

Nota: `CardMachine.vue` se excluye **solo** de la regla de hex (materiales 3D);
sí se revisa que no conserve tokens heredados.

**Paso 4.3 — Verificar.**

```powershell
npm run test -- --run resources/js/Components/__tests__/theme.test.js
npm run build
```

Esperado: verde. Si aparecen archivos infractores, migrarlos con el token correcto
(no añadirlos a la lista de exclusión).

---

## Tarea 5 — Persistencia del tema

**Archivos:**
- Crear: `database/migrations/2026_11_08_000001_add_theme_to_users_table.php`
- Modificar: `app/Models/User.php`
- Modificar: `app/Http/Controllers/SettingsController.php`
- Modificar: `routes/web.php`
- Modificar: `tests/Feature/SettingsTest.php`

**Paso 5.1 — Migración.**

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
            $table->string('theme')->default('light');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
```

**Paso 5.2 — Modelo.**

En `User.php`: añadir `'theme'` al atributo `#[Fillable([...])]` y
`'theme' => 'light'` a `$attributes`.

**Paso 5.3 — Controlador.**

En `SettingsController` añadir el método (usar el `Request` y `RedirectResponse`
ya importados en el archivo):

```php
public function updateTheme(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'theme' => ['required', 'in:light,dark'],
    ]);

    $request->user()->update($validated);

    return back();
}
```

**Paso 5.4 — Ruta.**

Dentro del grupo autenticado, junto a las demás de ajustes:

```php
Route::patch('/settings/theme', [SettingsController::class, 'updateTheme'])
    ->name('settings.theme');
```

**Paso 5.5 — Pruebas (rojo → verde).**

Añadir a `tests/Feature/SettingsTest.php`:

```php
it('guarda el tema elegido', function () {
    $this->patch(route('settings.theme'), ['theme' => 'dark'])
        ->assertRedirect();

    expect($this->user->fresh()->theme)->toBe('dark');
});

it('rechaza un tema no válido', function () {
    $this->from(route('settings.index'))
        ->patch(route('settings.theme'), ['theme' => 'neon'])
        ->assertRedirect(route('settings.index'))
        ->assertSessionHasErrors('theme');

    expect($this->user->fresh()->theme)->toBe('light');
});
```

**Paso 5.6 — Migrar y verificar.**

```powershell
php artisan migrate
php artisan test --filter=SettingsTest
```

Esperado: verde y columna `theme` presente.

---

## Tarea 6 — `ThemeSync`, `data-theme`, selector e i18n

**Archivos:**
- Crear: `resources/js/Components/ThemeSync.vue`
- Crear: `resources/js/Components/__tests__/ThemeSync.test.js`
- Modificar: `resources/js/Components/AppShell.vue`
- Modificar: `resources/js/Layouts/GuestLayout.vue`
- Modificar: `resources/views/app.blade.php`
- Modificar: `resources/js/Pages/Settings/Index.vue`
- Modificar: `lang/{es,en,pt,fr,de,it,ca,gl}.json`

**Paso 6.1 — Componente de sincronización.**

`ThemeSync.vue`:

```vue
<script setup>
import { usePage } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

const page = usePage();

watchEffect(() => {
    const theme = page.props.auth?.user?.theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.theme = theme;
});
</script>

<template>
    <span hidden aria-hidden="true" />
</template>
```

**Paso 6.2 — Montarlo en ambos layouts.**

- `AppShell.vue`: importar `ThemeSync` y colocar `<ThemeSync />` como primer hijo del
  template.
- `GuestLayout.vue`: importar y colocar `<ThemeSync />` como primer hijo del `div`
  raíz.

**Paso 6.3 — Blade.**

En `resources/views/app.blade.php`, añadir el atributo a `<html>`:

```blade
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ in_array(app()->getLocale(), config('languages.rtl'), true) ? 'rtl' : 'ltr' }}"
    data-theme="{{ auth()->user()?->theme ?? 'light' }}"
>
```

**Paso 6.4 — Estado y selector en Ajustes.**

En el `<script setup>` de `Settings/Index.vue`:

```js
const theme = ref(props.user.theme ?? 'light');
const themeError = ref('');

const themes = [
    { value: 'light', label: 'ui.theme_light', help: 'ui.theme_light_help' },
    { value: 'dark', label: 'ui.theme_dark', help: 'ui.theme_dark_help' },
];

function applyTheme(value) {
    if (value === theme.value) {
        return;
    }

    const previous = theme.value;
    theme.value = value;
    themeError.value = '';
    document.documentElement.dataset.theme = value;

    router.patch('/settings/theme', { theme: value }, {
        preserveScroll: true,
        onError: () => {
            theme.value = previous;
            document.documentElement.dataset.theme = previous;
            themeError.value = t('ui.theme_error');
        },
    });
}
```

En el template, antes de la sección de contraseña:

```vue
<section class="mb-8 space-y-3">
    <h2 class="text-2xl">{{ t('ui.appearance_heading') }}</h2>
    <p class="text-sm text-ink-muted">{{ t('ui.appearance_help') }}</p>

    <div class="grid gap-3 sm:grid-cols-2">
        <button
            v-for="option in themes"
            :key="option.value"
            type="button"
            class="rounded-card border p-4 text-left transition"
            :class="theme === option.value
                ? 'border-accent bg-accent-soft ring-2 ring-accent/30'
                : 'border-edge bg-surface hover:border-edge-strong'"
            :aria-pressed="theme === option.value"
            @click="applyTheme(option.value)"
        >
            <span class="font-serif text-lg text-heading">{{ t(option.label) }}</span>
            <span class="mt-1 block text-xs text-ink-muted">{{ t(option.help) }}</span>
        </button>
    </div>

    <p v-if="themeError" class="text-sm text-danger">{{ themeError }}</p>
</section>
```

**Paso 6.5 — Traducciones.**

Crear un script temporal `_add_theme_keys.php` en la raíz y ejecutarlo:

```php
<?php

$root = __DIR__.'/lang';
$keys = [
    'en' => [
        'ui.appearance_heading' => 'Appearance',
        'ui.appearance_help' => 'Choose the app theme. It is saved to your account.',
        'ui.theme_light' => 'Light',
        'ui.theme_dark' => 'Dark',
        'ui.theme_light_help' => 'Cream paper, white cards and rosy accents.',
        'ui.theme_dark_help' => 'Arcane night, violet panels and gold.',
        'ui.theme_error' => 'The theme could not be saved.',
    ],
    'es' => [
        'ui.appearance_heading' => 'Apariencia',
        'ui.appearance_help' => 'Elige el tema de la aplicación. Se guarda en tu cuenta.',
        'ui.theme_light' => 'Claro',
        'ui.theme_dark' => 'Oscuro',
        'ui.theme_light_help' => 'Papel crema, tarjetas blancas y acentos rosas.',
        'ui.theme_dark_help' => 'Noche arcana, paneles violetas y dorados.',
        'ui.theme_error' => 'No se pudo guardar el tema.',
    ],
    'pt' => [
        'ui.appearance_heading' => 'Aparência',
        'ui.appearance_help' => 'Escolha o tema do aplicativo. Ele fica salvo na sua conta.',
        'ui.theme_light' => 'Claro',
        'ui.theme_dark' => 'Escuro',
        'ui.theme_light_help' => 'Papel creme, cartões brancos e detalhes rosas.',
        'ui.theme_dark_help' => 'Noite arcana, painéis violeta e dourado.',
        'ui.theme_error' => 'Não foi possível salvar o tema.',
    ],
    'fr' => [
        'ui.appearance_heading' => 'Apparence',
        'ui.appearance_help' => 'Choisissez le thème de l’application. Il est enregistré dans votre compte.',
        'ui.theme_light' => 'Clair',
        'ui.theme_dark' => 'Sombre',
        'ui.theme_light_help' => 'Papier crème, cartes blanches et accents rosés.',
        'ui.theme_dark_help' => 'Nuit arcane, panneaux violets et dorures.',
        'ui.theme_error' => 'Le thème n’a pas pu être enregistré.',
    ],
    'de' => [
        'ui.appearance_heading' => 'Darstellung',
        'ui.appearance_help' => 'Wähle das Design der App. Es wird in deinem Konto gespeichert.',
        'ui.theme_light' => 'Hell',
        'ui.theme_dark' => 'Dunkel',
        'ui.theme_light_help' => 'Cremefarbenes Papier, weiße Karten und rosafarbene Akzente.',
        'ui.theme_dark_help' => 'Arkane Nacht, violette Paneele und Gold.',
        'ui.theme_error' => 'Das Design konnte nicht gespeichert werden.',
    ],
    'it' => [
        'ui.appearance_heading' => 'Aspetto',
        'ui.appearance_help' => 'Scegli il tema dell’app. Viene salvato nel tuo account.',
        'ui.theme_light' => 'Chiaro',
        'ui.theme_dark' => 'Scuro',
        'ui.theme_light_help' => 'Carta crema, schede bianche e accenti rosa.',
        'ui.theme_dark_help' => 'Notte arcana, pannelli viola e oro.',
        'ui.theme_error' => 'Non è stato possibile salvare il tema.',
    ],
    'ca' => [
        'ui.appearance_heading' => 'Aparença',
        'ui.appearance_help' => 'Tria el tema de l’aplicació. Es desa al teu compte.',
        'ui.theme_light' => 'Clar',
        'ui.theme_dark' => 'Fosc',
        'ui.theme_light_help' => 'Paper crema, targetes blanques i accents rosats.',
        'ui.theme_dark_help' => 'Nit arcana, panells violeta i daurats.',
        'ui.theme_error' => 'No s’ha pogut desar el tema.',
    ],
    'gl' => [
        'ui.appearance_heading' => 'Aparencia',
        'ui.appearance_help' => 'Escolle o tema da aplicación. Gárdase na túa conta.',
        'ui.theme_light' => 'Claro',
        'ui.theme_dark' => 'Escuro',
        'ui.theme_light_help' => 'Papel crema, tarxetas brancas e detalles rosas.',
        'ui.theme_dark_help' => 'Noite arcana, paneis violeta e dourados.',
        'ui.theme_error' => 'Non se puido gardar o tema.',
    ],
];

foreach ($keys as $locale => $entries) {
    $path = $root.'/'.$locale.'.json';
    $data = json_decode(file_get_contents($path), true);
    $data = array_merge($data, $entries);
    ksort($data);
    file_put_contents(
        $path,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
    );
}
```

```powershell
php _add_theme_keys.php
Remove-Item _add_theme_keys.php
```

**Paso 6.6 — Prueba de `ThemeSync`.**

`ThemeSync.test.js`:

```js
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { nextTick, reactive } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ page: null }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mocks.page,
}));

import ThemeSync from '../ThemeSync.vue';

enableAutoUnmount(afterEach);

describe('ThemeSync', () => {
    beforeEach(() => {
        mocks.page = reactive({
            props: { auth: { user: { theme: 'dark' } } },
        });
    });

    afterEach(() => {
        delete document.documentElement.dataset.theme;
    });

    it('aplica el tema guardado en la cuenta', async () => {
        mount(ThemeSync);
        await nextTick();

        expect(document.documentElement.dataset.theme).toBe('dark');
    });

    it('reacciona al cambio de tema', async () => {
        mount(ThemeSync);
        await nextTick();

        mocks.page.props.auth.user.theme = 'light';
        await nextTick();

        expect(document.documentElement.dataset.theme).toBe('light');
    });
});
```

**Paso 6.7 — Verificar.**

```powershell
npm run test
php artisan test
npm run build
```

Esperado: todo verde.

---

## Tarea 7 — Documentación visual y verificación final

**Archivos:**
- Modificar: `docs/DISENO.md`

**Paso 7.1 — Reescribir la sección 2 de `DISENO.md`.**

- Cambiar `**Última actualización:** 2026-11-05` por `2026-11-08`.
- Sustituir «Paleta de la noche (interfaz)» por dos tablas: **«Tema claro (por defecto)»**
  y **«Tema oscuro»**, con los valores de la tabla de la spec.
- Sustituir «Colores semánticos» por la lista real de tokens
  (`page`, `surface`, `surface-raised`, `surface-sunken`, `ink`, `ink-muted`, `ink-soft`,
  `heading`, `on-accent`, `edge`, `edge-strong`, `accent`, `accent-strong`, `accent-soft`,
  `secondary`, `secondary-strong`, `tertiary`, `tertiary-strong`, `danger`,
  `danger-strong`, `success`, `warning`, `ring`, `scrim`).
- Actualizar «Reglas de contraste»: `ink` sobre `page`/`surface` AA; `on-accent` sobre
  `accent`/`tertiary`/`secondary` AA; el mueble conserva sus combinaciones.
- Añadir una nota: el tema se elige en **Ajustes → Apariencia**, se guarda en la cuenta
  y el álbum de eventos usa siempre papel claro.

**Paso 7.2 — Registro de cambios.**

Añadir al final de la tabla de cambios:

```md
| 2026-11-08 | Tema claro pastel por defecto y tema oscuro conmutables desde **Ajustes → Apariencia** (persistido en la cuenta). La interfaz pasa a tokens semánticos (`page`, `surface`, `ink`, `edge`, `accent`, `secondary`, `tertiary`, `danger`, `success`, `scrim`); se retiran `night`, `panel`, `line`, `moon`, `mist`, `gold`, `magic`, `arcane` y `orquidea`. Sin cambios de tipografía. El mueble y las portadas conservan sus colores físicos. | Agente |
```

**Paso 7.3 — Verificación completa.**

```powershell
php artisan test
npm run test
vendor\bin\pint --test
npm run build
```

**Paso 7.4 — Revisión manual en navegador.**

- Claro y oscuro en escritorio y a 360 px: Inicio, Estanterías, Ajustes, Eventos,
  Álbum, Acceso y Registro.
- Cambiar tema en Ajustes y recargar: se mantiene.
- Abrir modal, lector, escáner y catálogo de decoraciones en ambos temas.
- Comprobar foco visible con teclado y contraste de textos secundarios.

---

## Criterios de aceptación

1. `npm run test`, `php artisan test`, `vendor\bin\pint --test` y `npm run build` pasan.
2. No queda ningún uso de tokens heredados en `resources/` (lo garantiza `theme.test.js`).
3. El tema claro es el valor por defecto para usuarias nuevas y visitantes.
4. El tema se guarda en `users.theme` y sobrevive a la recarga y a otra sesión.
5. Los tokens físicos del mueble no cambiaron de valor.
6. `docs/DISENO.md` refleja la paleta nueva y tiene la fila del 2026-11-08.
