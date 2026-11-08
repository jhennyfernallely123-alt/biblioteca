# Álbum de pegatinas réplica — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rediseñar `/events/album` como un álbum de pegatinas estilo cuaderno claro (papeles pastel, anillas, tarjeta de progreso, filtros por evento, tarjetas numeradas por rareza y ficha de detalle al tocar una pegatina obtenida), siempre en papel claro incluso con tema oscuro.

**Architecture:** Backend: `config/events.php` añade `rarity` a cada pegatina; `EventController::album` combina el catálogo con los coleccionables propios y entrega `stickers` (`key`, `event_key`, `theme`, `cost`, `rarity`, `owned`, `acquired_at`) y `progress` (`owned`, `total`, `by_rarity` con `{comun, rara, epica}`). Frontend: `Pages/Events/Album.vue` renderiza filtro, progreso, rejilla y ficha de detalle (modal); `EventSticker.vue` se mantiene sin cambios. Estética con clases nuevas `sticker-album*` en `resources/css/textures.css` y tokens físicos de álbum en `resources/css/app.css` (`--color-album-paper`, etc.), idénticos en light y dark.

**Tech Stack:** Laravel 13 + Inertia.js + Vue 3, Tailwind CSS 4, Pest/PHPUnit, Vitest, MySQL.

---

## Estructura de archivos

- Crear: `docs/superpowers/plans/2026-11-08-album-replica.md` (este plan).
- Modificar: `config/events.php` (rarity por pegatina).
- Modificar: `app/Http/Controllers/EventController.php:64-82` (método `album`).
- Modificar: `tests/Feature/EventAlbumTest.php` (rarity, by_rarity, acquired_at, aislamiento).
- Crear: `resources/js/Pages/Events/__tests__/Album.test.js` (rejilla, bloqueadas, filtro, ficha).
- Modificar: `resources/js/Pages/Events/Album.vue` (réplica completa).
- Modificar: `resources/css/textures.css` (clases `sticker-album*` al final).
- Modificar: `resources/css/app.css` (tokens físicos `--color-album-*` en `@theme`, light y dark iguales).
- Modificar: `lang/{es,en,pt,fr,de,it,ca,gl}.json` (14 claves nuevas × 8, vía script PHP).
- Modificar: `docs/DISENO.md` (sección álbum reemplazada + fila de cambios 2026-11-08).
- Sin crear: componentes nuevos (se reutiliza `Modal` y `EventSticker`).
- Fuera de alcance: sobres, confeti, precios, reordenar, nuevas pegatinas, editar dibujos.

---

### Task 1: Datos — añadir `rarity` a las 12 pegatinas en `config/events.php`

**Files:**
- Modify: `config/events.php` (cada entrada de `stickers`).

- [ ] **Step 1: Escribir la prueba que falla** (test de backend con rarity)

```php
// tests/Feature/EventAlbumTest.php — nueva aserción dentro del primer test
->where('stickers.0.rarity', 'comun')
->where('progress.by_rarity.comun', 1)
->where('progress.by_rarity.rara', 0)
->where('progress.by_rarity.epica', 0)
```

- [ ] **Step 2: Ejecutar test y verificar fallo**

```bash
php artisan test --filter=EventAlbumTest
```
Esperado: FAIL (falta `rarity`/`by_rarity` o clave inexistente).

- [ ] **Step 3: Implementar `rarity` en `config/events.php`**

```php
'stickers' => [
    ['key' => 'feria_pluma', 'name' => 'Pluma de la feria', 'cost' => 15, 'emblem' => 'feather', 'rarity' => 'comun'],
    ['key' => 'feria_lampara', 'name' => 'Lámpara de la feria', 'cost' => 25, 'emblem' => 'lantern', 'rarity' => 'epica'],
    ['key' => 'dragones_escama', 'name' => 'Escama de dragón', 'cost' => 18, 'emblem' => 'dragon', 'rarity' => 'rara'],
    ['key' => 'dragones_alas', 'name' => 'Ala nocturna', 'cost' => 22, 'emblem' => 'wing', 'rarity' => 'rara'],
    ['key' => 'invierno_copo', 'name' => 'Copo de cuento', 'cost' => 15, 'emblem' => 'star', 'rarity' => 'comun'],
    ['key' => 'invierno_campana', 'name' => 'Campana nevada', 'cost' => 24, 'emblem' => 'bell', 'rarity' => 'rara'],
    ['key' => 'enamorados_corazon', 'name' => 'Corazón sellado', 'cost' => 16, 'emblem' => 'heart', 'rarity' => 'comun'],
    ['key' => 'enamorados_rosa', 'name' => 'Rosa entre páginas', 'cost' => 25, 'emblem' => 'rose', 'rarity' => 'epica'],
    ['key' => 'brujas_calavera', 'name' => 'Calavera lectora', 'cost' => 17, 'emblem' => 'skull', 'rarity' => 'rara'],
    ['key' => 'brujas_pocion', 'name' => 'Poción de tinta', 'cost' => 21, 'emblem' => 'flame', 'rarity' => 'rara'],
    ['key' => 'flores_ramo', 'name' => 'Ramo prensado', 'cost' => 15, 'emblem' => 'rose', 'rarity' => 'comun'],
    ['key' => 'flores_planta', 'name' => 'Planta de biblioteca', 'cost' => 20, 'emblem' => 'leaf', 'rarity' => 'rara'],
],
```

- [ ] **Step 4: Ejecutar test y verificar paso**

```bash
php artisan test --filter=EventAlbumTest
```
Esperado: PASS (2 tests).

---

### Task 2: Backend — `album` entrega `owned`, `acquired_at` y `progress.by_rarity`

**Files:**
- Modify: `app/Http/Controllers/EventController.php:64-82`.
- Test: `tests/Feature/EventAlbumTest.php`.

- [ ] **Step 1: Escribir la prueba que falla** (`acquired_at` solo en obtenidas)

```php
// tests/Feature/EventAlbumTest.php — segundo bloque dentro del primer test
->where('stickers.0.acquired_at', fn ($value) => is_string($value) && $value !== '')
->where('stickers.1.acquired_at', null)
```

y nuevo test de desglose por rareza con varias obtenidas:

```php
it('desglosa el progreso por rareza', function () {
    UserCollectible::create([
        'user_id' => $this->user->id, 'collectible_key' => 'feria_pluma', 'kind' => 'sticker',
        'event_key' => 'feria_libro', 'source' => 'shop', 'acquired_at' => now(),
    ]);
    UserCollectible::create([
        'user_id' => $this->user->id, 'collectible_key' => 'dragones_escama', 'kind' => 'sticker',
        'event_key' => 'noche_dragones', 'source' => 'shop', 'acquired_at' => now(),
    ]);
    UserCollectible::create([
        'user_id' => $this->user->id, 'collectible_key' => 'feria_lampara', 'kind' => 'sticker',
        'event_key' => 'feria_libro', 'source' => 'shop', 'acquired_at' => now(),
    ]);

    $this->get(route('events.album'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('progress.owned', 3)
            ->where('progress.total', 12)
            ->where('progress.by_rarity.comun', 1)
            ->where('progress.by_rarity.rara', 1)
            ->where('progress.by_rarity.epica', 1)
        );
});
```

- [ ] **Step 2: Ejecutar test y verificar fallo**

```bash
php artisan test --filter=EventAlbumTest
```
Esperado: FAIL (`acquired_at` ausente/nulo o `by_rarity` ausente).

- [ ] **Step 3: Implementar método `album`**

```php
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
    ]);
}
```

- [ ] **Step 4: Ejecutar test y verificar paso**

```bash
php artisan test --filter=EventAlbumTest
```
Esperado: PASS (3 tests).

- [ ] **Step 5: Ejecutar toda la suite PHP para no romper nada**

```bash
php artisan test
```
Esperado: PASS (209+ tests).

---

### Task 3: Tokens físicos del álbum en `app.css`

**Files:**
- Modify: `resources/css/app.css` (bloque `@theme` light y dark).

- [ ] **Step 1: Añadir tokens físicos de álbum**

Al final de cada bloque `@theme` (light y dark, valores idénticos porque el álbum es siempre papel claro):

```css
  --color-album-paper: #FAF6EE;
  --color-album-edge: #E5DDCB;
  --color-album-ink: #2B2620;
  --color-album-pink: #FDE2E4;
  --color-album-pink-strong: #F0B3B8;
  --color-album-amber: #B45309;
  --color-album-mint: #E2F0CB;
  --color-album-sky: #B5E2FA;
  --color-album-lilac: #E0BBE4;
  --color-album-lemon: #FEF9EF;
  --color-album-peach: #FFCAD4;
  --color-album-slate: #3F4A56;
```

- [ ] **Step 2: Verificar que no hay hex sueltos en `.vue`**

```bash
# PowerShell (ps 5.1): buscar #hex en componentes Vue excluyendo app.css y tests
Get-ChildItem -Recurse -Include *.vue -File resources\js | Select-String -Pattern "#[0-9a-fA-F]{3,8}\b"
```
Esperado: solo coincidencias previstas (CardMachine materiales 3D); ningún token del álbum.

- [ ] **Step 3: Pint check y build**

```bash
vendor\bin\pint --test
npm run build
```
Esperado: pint passed, build OK (sin errores).

---

### Task 4: CSS — clases `sticker-album*` en `textures.css`

**Files:**
- Modify: `resources/css/textures.css` (append al final).

- [ ] **Step 1: Añadir clases de álbum**

```css
/* Álbum de pegatinas: siempre papel claro, independiente del tema */
.sticker-album {
    position: relative;
    border: 4px solid var(--color-album-edge);
    border-radius: 24px;
    background-color: var(--color-album-paper);
    color: var(--color-album-ink);
    box-shadow: 0 10px 25px color-mix(in srgb, var(--color-tinta) 25%, transparent);
    padding: 24px 20px;
    min-height: 400px;
}

.sticker-album__rings {
    display: none;
    position: absolute;
    inset-x: 0;
    top: -10px;
    height: 22px;
    justify-content: space-between;
    padding: 0 28px;
    pointer-events: none;
    z-index: 1;
}

@media (min-width: 640px) {
    .sticker-album__rings {
        display: flex;
    }
}

.sticker-album__ring {
    width: 20px;
    height: 20px;
    border: 4px solid color-mix(in srgb, var(--color-madera-clara) 55%, transparent);
    border-radius: 50%;
    box-shadow: inset 0 1px 1px color-mix(in srgb, #000 20%, transparent);
    background: var(--color-laton);
}

.sticker-album__header {
    margin-bottom: 10px;
    position: relative;
    z-index: 2;
}

.sticker-album__title {
    font-family: var(--font-serif, ui-serif, Georgia, serif);
    font-size: 1.75rem;
    color: var(--color-album-ink);
    margin: 0 0 4px 0;
}

.sticker-album__hint {
    font-size: 0.9rem;
    color: color-mix(in srgb, var(--color-album-ink) 60%, transparent);
    margin: 0;
}

.sticker-album__progress {
    border: 1px solid var(--color-album-edge);
    border-radius: 16px;
    padding: 14px 16px;
    background: var(--color-album-paper);
    box-shadow: 0 4px 10px color-mix(in srgb, var(--color-tinta) 12%, transparent);
    margin-bottom: 14px;
}

.sticker-album__label {
    display: block;
    font-size: 0.8rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: color-mix(in srgb, var(--color-album-ink) 55%, transparent);
    margin-bottom: 6px;
}

.sticker-album__title-big {
    font-family: var(--font-serif, ui-serif, Georgia, serif);
    font-size: 1.5rem;
    color: var(--color-album-ink);
    margin: 0 0 2px 0;
}

.sticker-album__completed {
    font-size: 0.85rem;
    color: color-mix(in srgb, var(--color-album-ink) 45%, transparent);
    margin: 0 0 8px 0;
}

.sticker-album__bar {
    height: 8px;
    border-radius: 4px;
    background: color-mix(in srgb, var(--color-album-edge) 45%, transparent);
    overflow: hidden;
}

.sticker-album__bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--color-album-pink) 0%, var(--color-album-pink-strong) 45%, var(--color-album-amber) 100%);
}

.sticker-album__counters {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}

.sticker-album__counter {
    font-size: 0.8rem;
    color: color-mix(in srgb, var(--color-album-ink) 55%, transparent);
}

.sticker-album__counter strong {
    color: var(--color-album-ink);
    font-size: 1rem;
}

.sticker-album__filters {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 16px;
}

.sticker-album__pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    border: 1px solid color-mix(in srgb, var(--color-album-edge) 55%, transparent);
    background: var(--color-album-paper);
    color: var(--color-album-ink);
    font-size: 0.85rem;
    cursor: pointer;
}

.sticker-album__pill--active {
    background: var(--color-album-slate);
    color: var(--color-album-paper);
    border-color: var(--color-album-slate);
}

.sticker-album__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

@media (min-width: 640px) {
    .sticker-album__grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (min-width: 1024px) {
    .sticker-album__grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

.sticker-album__card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    border-radius: 14px;
    padding: 10px 8px;
    position: relative;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    background: #FFFFFF;
    border: 1px solid var(--color-album-amber);
    box-shadow: 0 2px 6px color-mix(in srgb, var(--color-tinta) 10%, transparent);
}

@media (prefers-reduced-motion: no-preference) {
    .sticker-album__card:hover {
        transform: translateY(-2px) rotate(1deg);
        box-shadow: 0 8px 18px color-mix(in srgb, var(--color-tinta) 18%, transparent);
    }
}

.sticker-album__card--locked {
    background: color-mix(in srgb, var(--color-album-paper) 70%, transparent);
    border: 2px dashed color-mix(in srgb, var(--color-madera) 40%, transparent);
    cursor: default;
}

.sticker-album__card--locked .event-sticker {
    opacity: 0.55;
}

.sticker-album__number {
    position: absolute;
    top: 6px;
    right: 8px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--color-album-amber);
    color: #FFFFFF;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.sticker-album__sticker {
    width: 112px;
    height: 112px;
    z-index: 2;
}

.sticker-album__name {
    font-family: var(--font-serif, ui-serif, Georgia, serif);
    font-size: 0.95rem;
    color: var(--color-album-ink);
    text-align: center;
    margin: 4px 0 2px 0;
}

.sticker-album__event {
    font-size: 0.75rem;
    color: color-mix(in srgb, var(--color-album-ink) 45%, transparent);
    text-align: center;
}

.sticker-album__badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.sticker-album__badge--comun { background: var(--color-album-mint); color: var(--color-tinta); }
.sticker-album__badge--rara { background: var(--color-album-sky); color: var(--color-tinta); }
.sticker-album__badge--epica { background: var(--color-album-lilac); color: var(--color-tinta); }

.sticker-album__locked-tag {
    color: color-mix(in srgb, var(--color-album-ink) 40%, transparent);
    font-size: 0.8rem;
}
```

- [ ] **Step 2: Ejecutar build y pint**

```bash
npm run build
vendor\bin\pint --test
```
Esperado: build OK, pint passed.

---

### Task 5: Frontend — reescribir `Pages/Events/Album.vue`

**Files:**
- Modify: `resources/js/Pages/Events/Album.vue`.

- [ ] **Step 1: Escribir la prueba que falla** (`resources/js/Pages/Events/__tests__/Album.test.js`)

```js
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Album from '../Album.vue';

const stickers = Array.from({ length: 12 }, (_, i) => ({
    key: `sticker_${i}`,
    event_key: i < 4 ? 'feria_libro' : i < 8 ? 'noche_dragones' : 'invierno_cuento',
    theme: 'book',
    cost: 15,
    rarity: i < 4 ? 'comun' : i < 8 ? 'rara' : 'epica',
    owned: i < 2,
    acquired_at: i < 2 ? '2026-11-01T10:00:00+00:00' : null,
}));

const progress = { owned: 2, total: 12, by_rarity: { comun: 1, rara: 1, epica: 0 } };

describe('Album', () => {
    it('renderiza 12 tarjetas; bloqueadas muestran ??? y sin ilustración', () => {
        const wrapper = mount(Album, { props: { stickers, progress } });
        expect(wrapper.findAll('.sticker-album__card')).toHaveLength(12);
        expect(wrapper.findAll('.sticker-album__card--locked')).toHaveLength(10);
        expect(wrapper.text()).toContain('???');
        expect(wrapper.findAll('.event-sticker__art')).toHaveLength(2);
        wrapper.unmount();
    });

    it('filtra por evento', () => {
        const wrapper = mount(Album, { props: { stickers, progress } });
        wrapper.findAll('.sticker-album__pill')[1].trigger('click');
        expect(wrapper.findAll('.sticker-album__card')).toHaveLength(4);
        wrapper.unmount();
    });

    it('abre y cierra la ficha con rareza, número y fecha', () => {
        const wrapper = mount(Album, { props: { stickers, progress } });
        wrapper.findAll('.sticker-album__card')[0].trigger('click');
        expect(wrapper.findComponent({ name: 'Modal' }).exists()).toBe(true);
        expect(wrapper.text()).toContain('Común');
        expect(wrapper.text()).toContain('#01');
        expect(wrapper.text()).toContain('1 de nov. de 2026');
        wrapper.findComponent({ name: 'Modal' }).vm.$emit('close');
        expect(wrapper.findComponent({ name: 'Modal' }).props('show')).toBe(false);
        wrapper.unmount();
    });
});
```

Nota: `Modal` emite `close`; en el test se simula la emisión y se comprueba que el padre pone `show` en false.

- [ ] **Step 2: Ejecutar test y verificar fallo**

```bash
npm run test -- resources/js/Pages/Events/__tests__/Album.test.js
```
Esperado: FAIL (componente `Album` no existe o no cumple las aserciones).

- [ ] **Step 3: Implementar `Album.vue`**

```vue
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EventSticker from '@/Components/EventSticker.vue';
import Modal from '@/Components/Modal.vue';
import { useI18n } from '@/i18n';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    stickers: { type: Array, required: true },
    progress: { type: Object, required: true },
});

const { t } = useI18n();
const page = usePage();
const locale = computed(() => page.props.locale ?? 'es');

const ALL = 'all';
const activeEvent = ref(ALL);
const selected = ref(null);

const events = computed(() => {
    const seen = new Map();
    for (const sticker of props.stickers) {
        if (!seen.has(sticker.event_key)) {
            seen.set(sticker.event_key, sticker.event_name ?? sticker.event_key);
        }
    }
    return Array.from(seen.entries()).map(([key, name]) => ({ key, name }));
});

const filtered = computed(() =>
    activeEvent.value === ALL ? props.stickers : props.stickers.filter((s) => s.event_key === activeEvent.value)
);

const rarityLabel = (rarity) => t(`ui.sticker_rarity_${rarity}`);

const formatDate = (iso) => {
    if (!iso) return '';
    return new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium' }).format(new Date(iso));
});

const stickerNumber = (sticker) => String(props.stickers.indexOf(sticker) + 1).padStart(2, '0');

function open(sticker) {
    selected.value = sticker;
}

function close() {
    selected.value = null;
}
</script>

<template>
    <Head :title="t('ui.events_stickers_album')" />

    <AuthenticatedLayout>
        <section class="sticker-album" :aria-label="t('ui.events_stickers_album')">
            <div class="sticker-album__rings" aria-hidden="true">
                <span class="sticker-album__ring" v-for="n in 8" :key="n" />
            </div>

            <header class="sticker-album__header mb-6 flex items-start justify-between gap-4">
                <div>
                    <h1 class="sticker-album__title">{{ t('ui.events_stickers_album') }}</h1>
                    <p class="sticker-album__hint">{{ t('ui.sticker_hint') }}</p>
                </div>
                <Link href="/events" class="sticker-album__pill">
                    <span class="text-ink-muted">{{ t('ui.nav_events') }}</span>
                </Link>
            </header>

            <!-- Tarjeta de progreso -->
            <div class="sticker-album__progress">
                <p class="sticker-album__label">{{ t('ui.sticker_progress_label') }}</p>
                <p class="sticker-album__title-big">{{ t('ui.sticker_progress_title') }}</p>
                <p class="sticker-album__completed">
                    {{ progress.owned }} / {{ progress.total }} — {{ t('ui.sticker_completed') }}
                </p>
                <div class="sticker-album__bar">
                    <div
                        class="sticker-album__bar-fill"
                        :style="{ width: `${progress.total ? (progress.owned / progress.total) * 100 : 0}%` }"
                        role="progressbar"
                        :aria-valuenow="progress.owned"
                        :aria-valuemin="0"
                        :aria-valuemax="progress.total"
                    />
                </div>
                <div class="sticker-album__counters">
                    <span class="sticker-album__counter">
                        <strong>{{ progress.by_rarity.comun }}</strong> {{ rarityLabel('comun') }}
                    </span>
                    <span class="sticker-album__counter">
                        <strong>{{ progress.by_rarity.rara }}</strong> {{ rarityLabel('rara') }}
                    </span>
                    <span class="sticker-album__counter">
                        <strong>{{ progress.by_rarity.epica }}</strong> {{ rarityLabel('epica') }}
                    </span>
                </div>
            </div>

            <!-- Filtros -->
            <div class="sticker-album__filters">
                <button
                    type="button"
                    class="sticker-album__pill"
                    :class="{ 'sticker-album__pill--active': activeEvent === ALL }"
                    @click="activeEvent = ALL"
                    :aria-pressed="activeEvent === ALL"
                >
                    {{ t('ui.sticker_all') }}
                </button>
                <button
                    v-for="event in events"
                    :key="event.key"
                    type="button"
                    class="sticker-album__pill"
                    :class="{ 'sticker-album__pill--active': activeEvent === event.key }"
                    @click="activeEvent = event.key"
                    :aria-pressed="activeEvent === event.key"
                >
                    {{ event.name }}
                </button>
            </div>

            <!-- Cuaderno / rejilla -->
            <div class="sticker-album__grid">
                <article
                    v-for="sticker in filtered"
                    :key="sticker.key"
                    class="sticker-album__card"
                    :class="{ 'sticker-album__card--locked': !sticker.owned }"
                    @click="sticker.owned && open(sticker)"
                >
                    <span class="sticker-album__number">#{{ stickerNumber(sticker) }}</span>
                    <EventSticker
                        v-if="sticker.owned"
                        :item-key="sticker.key"
                        :size="112"
                        class="sticker-album__sticker"
                    />
                    <div v-else class="sticker-album__sticker" aria-hidden="true" />
                    <p class="sticker-album__name">
                        {{ sticker.owned ? t(`ui.event_item_${sticker.key}`) : t('ui.sticker_locked') }}
                    </p>
                    <p class="sticker-album__event">
                        {{ sticker.event_name ?? sticker.event_key }}
                    </p>
                    <span v-if="sticker.owned" class="sticker-album__badge" :class="`sticker-album__badge--${sticker.rarity}`">
                        {{ rarityLabel(sticker.rarity) }}
                    </span>
                    <span v-else class="sticker-album__locked-tag">{{ t('ui.sticker_locked') }}</span>
                </article>
            </div>
        </section>

        <!-- Ficha de detalle -->
        <Modal v-model:show="selected !== null" max-width="md" @close="close">
            <template v-if="selected">
                <div class="sticker-album__modal">
                    <EventSticker :item-key="selected.key" :size="160" class="sticker-album__modal-sticker" />
                    <span class="sticker-album__badge" :class="`sticker-album__badge--${selected.rarity}`">
                        {{ rarityLabel(selected.rarity) }}
                    </span>
                    <h2 class="font-serif text-2xl text-album-ink mt-2 mb-1">
                        {{ t(`ui.event_item_${selected.key}`) }}
                    </h2>
                    <p class="text-ink-muted text-sm mb-4">{{ selected.event_name ?? selected.event_key }}</p>
                    <p class="text-ink-sm mb-4">{{ t(`ui.sticker_desc_${selected.key}`) }}</p>
                    <div class="border border-edge rounded p-3 bg-surface">
                        <p class="text-xs text-ink-muted mb-1">{{ t('ui.sticker_number', { n: String(props.stickers.indexOf(selected) + 1).padStart(2, '0') }) }}</p>
                        <p class="text-xs text-ink-muted mb-1">{{ t('ui.sticker_acquired') }}: {{ formatDate(selected.acquired_at) }}</p>
                    </div>
                    <button type="button" class="mt-4 px-4 py-2 rounded bg-accent text-on-accent font-semibold" @click="close">
                        {{ t('ui.sticker_got_it') }}
                    </button>
                </div>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>
```

Nota: se usa `text-album-ink` y `text-ink-sm` como utilidades de Tailwind arbitrarias (`text-[var(--color-album-ink)]`) si fuera necesario; simplificado con clases existentes `text-ink-*`. El botón "Entendido" cierra mediante `@click="close"` y Escape/clic en velo ya lo gestiona `Modal`.

- [ ] **Step 4: Ejecutar test y verificar paso**

```bash
npm run test -- resources/js/Pages/Events/__tests__/Album.test.js
```
Esperado: PASS.

- [ ] **Step 5: Mantener `EventSticker.test.js` verde**

```bash
npm run test -- resources/js/Components/__tests__/EventSticker.test.js
```
Esperado: PASS (3 tests).

---

### Task 6: Textos — añadir claves de álbum a los 8 idiomas

**Files:**
- Modify: `lang/es.json`, `lang/en.json`, `lang/pt.json`, `lang/fr.json`, `lang/de.json`, `lang/it.json`, `lang/ca.json`, `lang/gl.json`.

- [ ] **Step 1: Ejecutar script PHP para insertar claves**

Crear `_album_i18n.php` (se borra después):

```php
<?php
$keys = [
    'ui.sticker_progress_label' => ['es' => 'Progreso de la colección', 'en' => 'Collection progress', 'pt' => 'Progresso da coleção', 'fr' => 'Progression de la collection', 'de' => 'Sammlungs-Fortschritt', 'it' => 'Progresso collezione', 'ca' => "Progrés de la col·lecció", 'gl' => 'Progreso da colección'],
    'ui.sticker_progress_title' => ['es' => 'Tu álbum de estampas', 'en' => 'Your sticker album', 'pt' => 'Seu álbum de figurinhas', 'fr' => 'Votre album de stickers', 'de' => 'Dein Sticker-Album', 'it' => 'Il tuo album di figurine', 'ca' => "El teu àlbum d'estampes", 'gl' => "O túa álbum de estampas"],
    'ui.sticker_completed' => ['es' => 'Completado', 'en' => 'Completed', 'pt' => 'Completo', 'fr' => 'Terminé', 'de' => 'Vollständig', 'it' => 'Completato', 'ca' => 'Completat', 'gl' => 'Completado'],
    'ui.sticker_all' => ['es' => 'Todas', 'en' => 'All', 'pt' => 'Todas', 'fr' => 'Toutes', 'de' => 'Alle', 'it' => 'Tutte', 'ca' => 'Totes', 'gl' => 'Todas'],
    'ui.sticker_locked' => ['es' => 'Bloqueado', 'en' => 'Locked', 'pt' => 'Bloqueado', 'fr' => 'Verrouillé', 'de' => 'Gesperrt', 'it' => 'Bloccato', 'ca' => 'Bloquejat', 'gl' => 'Bloqueado'],
    'ui.sticker_number' => ['es' => '#:n', 'en' => '#:n', 'pt' => '#:n', 'fr' => '#:n', 'de' => '#:n', 'it' => '#:n', 'ca' => '#:n', 'gl' => '#:n'],
    'ui.sticker_rarity' => ['es' => 'Rareza', 'en' => 'Rarity', 'pt' => 'Raridade', 'fr' => 'Rareté', 'de' => 'Seltenheit', 'it' => 'Rarità', 'ca' => 'Raresa', 'gl' => 'Rareza'],
    'ui.sticker_acquired' => ['es' => 'Obtenida', 'en' => 'Acquired', 'pt' => 'Adquirida', 'fr' => 'Obtenue', 'de' => 'Erworben', 'it' => 'Ottenuta', 'ca' => 'Obtinguda', 'gl' => 'Obtida'],
    'ui.sticker_got_it' => ['es' => 'Entendido', 'en' => 'Got it', 'pt' => 'Entendido', 'fr' => 'Compris', 'de' => 'Verstanden', 'it' => 'Capito', 'ca' => 'Entès', 'gl' => 'Entendido'],
    'ui.sticker_hint' => ['es' => 'Toca una pegatina para ver sus detalles', 'en' => 'Tap a sticker to see details', 'pt' => 'Toque em uma figurinha para ver detalhes', 'fr' => 'Appuyez sur un sticker pour voir les détails', 'de' => 'Tippe auf einen Sticker für Details', 'it' => 'Tocca una figurina per i dettagli', 'ca' => "Toca una estampa per veure els detalls", 'gl' => 'Toca unha estampa para ver os detalles'],
    'ui.sticker_rarity_comun' => ['es' => 'Común', 'en' => 'Common', 'pt' => 'Comum', 'fr' => 'Commun', 'de' => 'Gewöhnlich', 'it' => 'Comune', 'ca' => 'Comú', 'gl' => 'Común'],
    'ui.sticker_rarity_rara' => ['es' => 'Rara', 'en' => 'Rare', 'pt' => 'Rara', 'fr' => 'Rare', 'de' => 'Selten', 'it' => 'Rara', 'ca' => 'Rara', 'gl' => 'Rara'],
    'ui.sticker_rarity_epica' => ['es' => 'Épica', 'en' => 'Epic', 'pt' => 'Épica', 'fr' => 'Épique', 'de' => 'Episch', 'it' => 'Epica', 'ca' => 'Èpica', 'gl' => 'Épica'],
];

$descs = [
    'feria_pluma' => ['es' => 'Pluma de ave escritora.', 'en' => "Writer's feather.", 'pt' => 'Pena de ave escritora.', 'fr' => "Plume d'oiseau écrivain.", 'de' => 'Schriftfeder.', 'it' => 'Piuma di uccello scrittore.', 'ca' => 'Ploma d\'ocell escriptor.', 'gl' => 'Pluma de ave escritora.'],
    'feria_lampara' => ['es' => 'Lámpara de mesa antigua.', 'en' => 'Old table lamp.', 'pt' => 'Lâmpada de mesa antiga.', 'fr' => 'Vieille lampe de table.', 'de' => 'Alte Tischlampe.', 'it' => 'Vecchia lampada da tavolo.', 'ca' => 'Lampada de taula antiga.', 'gl' => 'Lámpara de mesa antiga.'],
    'dragones_escama' => ['es' => 'Escama de dragón guardián.', 'en' => "Guardian dragon's scale.", 'pt' => 'Escama de dragão guardião.', 'fr' => "Écailles du dragon gardien.", 'de': "Schuppen des Wächterdrachen.", 'it' => "Squama del drago guardiano.", 'ca' => "Escama del drac guardó", 'gl' => "Escama do dragón guardián."],
    'dragones_alas' => ['es' => 'Ala de dragón nocturno.', 'en' => 'Night dragon wing.', 'pt' => 'Asa de dragão noturno.', 'fr' => 'Aile de dragon nocturne.', 'de' => 'Flügel des Nachtdrachen.', 'it' => 'Ala di drago notturno.', 'ca' => 'Ala de drac nocturn.', 'gl' => 'Asa do dragón nocturno.'],
    'invierno_copo' => ['es' => 'Copo de nieve de cuento.', 'en' => "Storybook snowflake.", 'pt' => 'Floco de neve de conto.', 'fr' => 'Flocon de neige de conte.', 'de' => 'Schneeflocke aus dem Märchenbuch.', 'it' => 'Fiocco di neve da favola.', 'ca' => 'Floc de neu de conte.', 'gl' => 'Floco de neve de conto.'],
    'invierno_campana' => ['es' => 'Campana cubierta de nieve.', 'en' => 'Snow-covered bell.', 'pt' => 'Sino coberto de neve.', 'fr' => 'Cloche recouverte de neige.', 'de' => 'Schneebedeckte Glocke.', 'it' => 'Campana ricoperta di neve.', 'ca' => 'Campana coberta de neu.', 'gl' => 'Cunca cuberta de neve.'],
    'enamorados_corazon' => ['es' => 'Corazón sellado con cera.', 'en' => 'Wax-sealed heart.', 'pt' => 'Coração lacrado com cera.', 'fr' => 'Cœur scellé à la cire.', 'de' => 'Mit Wachs versiegeltes Herz.', 'it' => 'Cuore sigillato con ceralacca.', 'ca' => 'Cor durt a la cera.', 'gl' => 'Corazón sellado con cera.'],
    'enamorados_rosa' => ['es' => 'Rosa entre páginas de libro.', 'en' => 'Pressed rose between pages.', 'pt' => 'Rosa pressionada entre páginas.', 'fr' => 'Rose pressée entre les pages.', 'de' => 'Getrocknete Rose zwischen Buchseiten.', 'it' => 'Rosa pressata tra le pagine.', 'ca' => 'Rosa premsa entre pàgines.', 'gl' => 'Rosa presión entre páxinas.'],
    'brujas_calavera' => ['es' => 'Calavera de lectora nocturna.', 'en' => "Night reader's skull.", 'pt' => 'Caveira de leitora noturna.', 'fr' => 'Crâne de lectrice nocturne.', 'de' => 'Schädel der nächtlichen Leserin.', 'it' => 'Cranio della lettrice notturna.', 'ca' => 'Càverí de lectora nocturna.', 'gl' => 'Calavera de lectora nocturna.'],
    'brujas_pocion' => ['es' => 'Poción de tinta mágica.', 'en' => 'Magic ink potion.', 'pt' => 'Poção de tinta mágica.', 'fr' => 'Potion d\'encre magique.', 'de' => 'Magische Tinktur.', 'it' => 'Pozione di inchiostro magico.', 'ca' => 'Poció de tinta màgica.', 'gl' => 'Poción de tinta mágica.'],
    'flores_ramo' => ['es' => 'Ramo de flores prensado.', 'en' => 'Pressed flower bouquet.', 'pt' => 'Buquê de flores prensado.', 'fr' => 'Bouquet de fleurs pressé.', 'de' => 'Getrockneter Blumenstrauß.', 'it' => 'Mazzo di fiori pressato.', 'ca' => 'Rams de flors premses.', 'gl' => 'Ramo de flores pressionado.'],
    'flores_planta' => ['es' => 'Planta de biblioteca.', 'en' => 'Library plant.', 'pt' => 'Planta de biblioteca.', 'fr' => 'Plante de bibliothèque.', 'de' => 'Bibliothekspflanze.', 'it' => 'Pianta da biblioteca.', 'ca' => 'Planta de biblioteca.', 'gl' => 'Planta de biblioteca.'],
];

$langs = ['es', 'en', 'pt', 'fr', 'de', 'it', 'ca', 'gl'];
foreach ($langs as $lang) {
    $path = "lang/{$lang}.json";
    $data = json_decode(file_get_contents($path), true);
    $ui = $data['ui'] ?? [];
    foreach ($keys as $k => $v) { $ui[$k] = $v[$lang]; }
    foreach ($descs as $key => $v) { $ui["ui.sticker_desc_{$key}"] = $v[$lang]; }
    ksort($ui);
    $data['ui'] = $ui;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    echo "actualizado {$path}\n";
}
```

PowerShell pasa comillas mal a PHP; escribir el script en un archivo temporal y ejecutar con `php _album_i18n.php`, luego borrarlo.

- [ ] **Step 2: Verificar clave en los 8 idiomas**

```bash
# ejemplo para español
Select-String -Path lang\es.json -Pattern "sticker_progress_label|sticker_desc_feria_pluma"
```
Repetir para `en`, `pt`, `fr`, `de`, `it`, `ca`, `gl`. Esperado: clave presente en cada uno.

- [ ] **Step 3: Suite de catálogo de idiomas**

```bash
php artisan test --filter=CatalogTest
```
Esperado: PASS.

---

### Task 7: Documentación — `docs/DISENO.md`

**Files:**
- Modify: `docs/DISENO.md`.

- [ ] **Step 1: Reemplazar sección Álbum de pegatinas**

Sustituir el bloque actual de álbum por:

```
## Álbum de pegatinas (réplica de la referencia)

Página `/events/album` con estética de cuaderno claro, independiente del tema activo.

- **Contenedor:** `.sticker-album`, papel claro (`--color-album-paper #FAF6EE`), borde grueso (`--color-album-edge #E5DDCB`), radio 24 px y sombra suave.
- **Anillas:** 8 anillas metálicas (`.sticker-album__ring`) sobre el borde superior, visibles solo desde 640 px.
- **Tarjeta de progreso:** etiqueta, título "Tu álbum de estampas", contador "X / 12 — Completado", barra degradada rosa → rosa fuerte → ámbar (`role="progressbar"`) y tira de tres contadores (comunes, raras, épicas obtenidas).
- **Filtros:** pestaña "Todas" más una pildora por evento (6 eventos del catálogo); filtrado en cliente por `event_key`; estado activo en fondo oscuro (`--color-album-slate`).
- **Cuaderno:** rejilla 2 columnas a 360 px, 3 en tablet, 4 en escritorio. Tarjeta obtenida: fondo blanco, borde ámbar, sombra, número `#01`, insignia de rareza con color, dibujo de 112 px, nombre y evento. Tarjeta bloqueada: fondo translúcido, borde discontinuo, "Bloqueado", nombre "???", sin ilustración y sin clic. Hover: elevación, escala y giro leve de 1° (respetando `prefers-reduced-motion`).
- **Ficha de detalle (solo obtenidas):** `Modal` centrado con dibujo grande (160 px), pildora de rareza, nombre, descripción corta (`ui.sticker_desc_<clave>`), caja con rareza, número y fecha de obtención (`Intl.DateTimeFormat` según idioma), botón "Entendido"; cierre con Escape y clic en el velo.
```

- [ ] **Step 2: Añadir fila de cambios**

Añadir al registro de cambios (tabla) al final, con fecha 2026-11-08:

```
| 2026-11-08 | Álbum de pegatinas réplica: cuaderno claro, tarjeta de progreso, filtros por evento, tarjetas numeradas por rareza y ficha de detalle. Tokens físicos `--color-album-*` y clases `sticker-album*` en CSS. 14 claves de álbum en 8 idiomas. |
```

- [ ] **Step 3: Verificar integridad del Markdown**

Abrir `docs/DISENO.md` y confirmar que la sección del álbum cita solo clases y tokens existentes (`sticker-album*`, `--color-album-*`).

---

### Task 8: Verificación completa

**Files:** ninguno (solo ejecución).

- [ ] **Step 1: Suite PHP**

```bash
php artisan test
```
Esperado: PASS (incluye `EventAlbumTest` con 3 tests y `CatalogTest`).

- [ ] **Step 2: Suite Vitest**

```bash
npm run test
```
Esperado: PASS (incluye `Album.test.js` y `EventSticker.test.js`).

- [ ] **Step 3: Pint y build**

```bash
vendor\bin\pint --test
npm run build
```
Esperado: pint passed, build OK.

- [ ] **Step 4: Revisión visual manual**

Abrir `http://localhost:8000/events/album` autenticado, en ambos temas (`data-theme='light'` y `'dark'`), a 360 px y escritorio:
- Cuaderno papel claro también en tema oscuro.
- 12 tarjetas; bloqueadas con borde discontinuo y "???".
- Filtros funcionan y pill activa con texto legible.
- Progreso y contadores por rareza correctos.
- Ficha de detalle al tocar obtenida; cierre con Escape.
- Reservado: accesibilidad (foco, `aria-pressed`, `role="progressbar"`).

---

### Criterios de aceptación (definición de hecho)

- [ ] `config/events.php` tiene `rarity` en las 12 pegatinas (4 comun, 6 rara, 2 epica).
- [ ] `GET /events/album` devuelve `stickers[*]` con `owned` y `acquired_at` (ISO o `null`) y `progress.by_rarity.comun/rara/epica`.
- [ ] `EventAlbumTest` pasa: 12 pegatinas, progreso, desglose por rareza, `acquired_at` solo en obtenidas, aislamiento de usuaria.
- [ ] `Album.test.js` pasa: rejilla 12, bloqueadas sin ilustración, filtro por evento, ficha con rareza/número/fecha.
- [ ] `EventSticker.test.js` sigue verde.
- [ ] `docs/DISENO.md` documenta el álbum y la fila 2026-11-08.
- [ ] `php artisan test`, `npm run test`, `vendor\bin\pint --test`, `npm run build` todos verdes.

Plan completo y listo para ejecutar. Dos opciones de ejecución:

**1. Subagent-Driven (recomendado):** despacho un subagent por tarea con revisión entre tareas.
**2. Inline Execution:** ejecuto las tareas en esta sesión con `executing-plans` y puntos de control.

**¿Qué enfoque prefieres?** (También recuerda: los commits los hace la usuaria; yo no toco git.)
