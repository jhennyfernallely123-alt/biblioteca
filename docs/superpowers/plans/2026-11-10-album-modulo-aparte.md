# Álbum de colección — módulo aparte — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear un módulo de álbum independiente en `/album` que muestre las 20 cartas y los 20 stickers de máquina del usuario autenticado, con diseño estilo álbum digital (barra de progreso, filtros por categoría, tarjetas troqueladas y detalle en modal), sin depender del módulo Máquina expendedora.

**Architecture:** Backend `AlbumController` consulta `user_cards` y `user_collectibles` (kind=sticker, event_key=maquina) y devuelve una lista unificada con progreso. Frontend Inertia página `Album/Index` con componentes `AlbumSlot`, `AlbumProgress`, `AlbumFilter`, `AlbumDetailModal` y reuso de `LibrarySticker`. Navegación propia en `AppShell` y `BottomNav`.

**Tech Stack:** Laravel 13 (PHP 8.3), Inertia.js, Vue 3, Tailwind CSS 4, Pest/PHPUnit, Vitest.

---

## File Structure

- Create: `app/Http/Controllers/AlbumController.php`
- Create: `routes/` modification: `routes/web.php` (add `/album` route)
- Create: `resources/js/Pages/Album/Index.vue`
- Create: `resources/js/Components/AlbumSlot.vue`
- Create: `resources/js/Components/AlbumProgress.vue`
- Create: `resources/js/Components/AlbumFilter.vue`
- Create: `resources/js/Components/AlbumDetailModal.vue`
- Create: `tests/Feature/AlbumTest.php`
- Create: `resources/js/Components/__tests__/AlbumSlot.test.js`
- Modify: `resources/js/Components/AppShell.vue` (add album link)
- Modify: `resources/js/Components/BottomNav.vue` (add album menu item)
- Modify: `lang/es.json`, `lang/en.json` (add `ui.nav_album`)
- Modify: `docs/DISENO.md` (album section + changelog)

---

## Task 1: Escribir la prueba de backend del álbum (falla)

**Files:**
- Create: `tests/Feature/AlbumTest.php`

- [ ] **Step 1: Escribir la prueba de listado del álbum**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCard;
use App\Models\UserCollectible;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlbumTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_solo_objetos_del_usuario_autenticado(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $card = UserCard::factory()->create([
            'user_id' => $user->id,
            'card_key' => 'violet',
            'copies' => 1,
        ]);
        $sticker = UserCollectible::factory()->create([
            'user_id' => $user->id,
            'collectible_key' => 'maquina_libro',
            'kind' => 'sticker',
            'event_key' => 'maquina',
            'source' => 'machine',
        ]);
        UserCard::factory()->create([
            'user_id' => $other->id,
            'card_key' => 'xaden',
            'copies' => 1,
        ]);
        UserCollectible::factory()->create([
            'user_id' => $other->id,
            'collectible_key' => 'maquina_pluma',
            'kind' => 'sticker',
            'event_key' => 'maquina',
            'source' => 'machine',
        ]);

        $response = $this->actingAs($user)->get('/album');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Album/Index'));

        $data = $response->inertia()->data('props');
        $keys = collect($data['items'])->pluck('key');

        $this->assertTrue($keys->contains('violet'));
        $this->assertTrue($keys->contains('maquina_libro'));
        $this->assertFalse($keys->contains('xaden'));
        $this->assertFalse($keys->contains('maquina_pluma'));
    }

    public function test_progreso_coincide_con_cartas_y_stickers_obtenidos(): void
    {
        $user = User::factory()->create();

        UserCard::factory()->count(3)->create(['user_id' => $user->id]);
        UserCollectible::factory()->count(2)->create([
            'user_id' => $user->id,
            'kind' => 'sticker',
            'event_key' => 'maquina',
            'source' => 'machine',
        ]);

        $response = $this->actingAs($user)->get('/album');

        $data = $response->inertia()->data('props');

        $this->assertSame(5, $data['progress']['unlocked']);
        $this->assertSame(40, $data['progress']['total']);
    }
}
```

- [ ] **Step 2: Ejecutar la prueba y verificar que falla**

Run: `php artisan test --filter=AlbumTest`  
Expected: FAIL (404 / class `AlbumController` not found / route not defined).

---

## Task 2: Crear el controlador `AlbumController`

**Files:**
- Create: `app/Http/Controllers/AlbumController.php`

- [ ] **Step 1: Escribir el controlador con listado unificado de cartas y stickers**

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CardCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AlbumController extends Controller
{
    public function index(Request $request, CardCatalogService $catalog): Response
    {
        $user = $request->user();

        $cards = $this->cardItems($user, $catalog);
        $stickers = $this->stickerItems($user, $catalog);

        $items = $cards->concat($stickers)->values();

        $progress = [
            'unlocked' => $items->where('unlocked', true)->count(),
            'total' => $items->count(),
            'byRarity' => [
                'comun' => $items->where('unlocked', true)->where('rarity', 'comun')->count(),
                'rara' => $items->where('unlocked', true)->where('rarity', 'rara')->count(),
                'epica' => $items->where('unlocked', true)->where('rarity', 'epica')->count(),
            ],
        ];

        return Inertia::render('Album/Index', [
            'items' => $items,
            'progress' => $progress,
        ]);
    }

    private function cardItems(User $user, CardCatalogService $catalog): Collection
    {
        $pool = $catalog->poolFor($user);
        $owned = $user->userCards()->whereIn('card_key', $pool)->get()->keyBy('card_key');

        return collect($pool)->map(function (string $key) use ($catalog, $owned) {
            $card = $owned->get($key);
            $metadata = $catalog->metadata($key);

            return [
                'type' => 'card',
                'key' => $key,
                'name' => $metadata['character'] ?? $key,
                'category' => $metadata['familyLabel'] ?? $metadata['family'] ?? 'Cartas',
                'rarity' => 'comun',
                'unlocked' => $card !== null,
                'copies' => (int) ($card->copies ?? 0),
                'acquired_at' => $card?->created_at?->toISOString(),
                'image' => null,
            ];
        })->values();
    }

    private function stickerItems(User $user, CardCatalogService $catalog): Collection
    {
        $catalogStickers = $catalog->machineStickers();
        $owned = $user->collectibles()
            ->where('kind', 'sticker')
            ->where('event_key', 'maquina')
            ->get()
            ->keyBy('collectible_key');

        return collect($catalogStickers)->map(function (array $sticker) use ($owned) {
            $record = $owned->get($sticker['key']);

            return [
                'type' => 'sticker',
                'key' => $sticker['key'],
                'name' => __($sticker['name']),
                'category' => 'Stickers de la máquina',
                'rarity' => 'comun',
                'unlocked' => $record !== null,
                'copies' => 1,
                'acquired_at' => $record?->acquired_at?->toISOString(),
                'image' => null,
            ];
        })->values();
    }
}
```

- [ ] **Step 2: Ejecutar la prueba de backend y verificar que pasa**

Run: `php artisan test --filter=AlbumTest`  
Expected: PASS (2 tests).

---

## Task 3: Añadir la ruta `/album`

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Añadir la ruta del álbum dentro del grupo autenticado**

```php
    Route::get('/album', [AlbumController::class, 'index'])->name('album.index');
```

Insert after the `/cards/{card}` route (line 65) and before `/events`.

- [ ] **Step 2: Importar el controlador en `routes/web.php`**

Add to the import block:
```php
use App\Http\Controllers\AlbumController;
```

- [ ] **Step 3: Verificar que la ruta responde**

Run: `php artisan test --filter=AlbumTest`  
Expected: PASS (2 tests). Route now resolves to `Album/Index` (page not yet created, so Inertia will error on missing page; we fix that in Task 4). For now, the route registration itself is validated by the test reaching the controller. If Inertia complains about missing page, proceed to Task 4 immediately.

---

## Task 4: Crear componentes Vue del álbum

**Files:**
- Create: `resources/js/Components/AlbumSlot.vue`
- Create: `resources/js/Components/AlbumProgress.vue`
- Create: `resources/js/Components/AlbumFilter.vue`
- Create: `resources/js/Components/AlbumDetailModal.vue`

- [ ] **Step 1: Escribir `AlbumSlot.vue`**

```vue
<script setup>
import { computed } from 'vue';
import { useI18n } from '@/i18n';
import LibrarySticker from './LibrarySticker.vue';
import CardEmblem from './CardEmblem.vue';

const props = defineProps({
    item: { type: Object, required: true },
});

const { t } = useI18n();

const rarityColor = computed(() => {
    switch (props.item.rarity) {
        case 'epica': return 'text-amber-500';
        case 'rara': return 'text-blue-500';
        default: return 'text-ink-muted';
    }
});

const isLocked = computed(() => !props.item.unlocked);
</script>

<template>
    <div
        class="flex flex-col items-center gap-2 cursor-pointer group"
        :class="{
            'collected bg-surface/60 border border-edge/50 shadow-sm hover:shadow-md': !isLocked,
            'bg-surface-raised/40 border-dashed border-edge/40 opacity-60 hover:opacity-100': isLocked,
        }"
        :aria-label="isLocked ? t('ui.album_locked') : t('ui.album_item', { name: props.item.name })"
        role="button"
        tabindex="0"
    >
        <!-- Número de la pieza -->
        <div class="text-[10px] font-bold text-ink-muted uppercase tracking-wider">
            #{{ props.item.key }}
        </div>

        <!-- Visual del sticker o carta -->
        <div class="w-24 h-24 flex items-center justify-center my-2 relative rounded-xl border border-edge/30 bg-surface p-1">
            <div v-if="props.item.type === 'sticker'" class="w-full h-full flex items-center justify-center">
                <LibrarySticker
                    v-if="!isLocked"
                    :sticker="props.item.key"
                    :size="80"
                    :locked="false"
                />
                <div v-else class="w-20 h-20 flex items-center justify-center rounded-full bg-surface-raised border border-edge text-ink-muted">
                    <span class="text-xs">?</span>
                </div>
            </div>
            <div v-else class="w-full h-full flex items-center justify-center">
                <CardEmblem v-if="!isLocked" :name="props.item.image ?? 'book'" :size="40" />
                <div v-else class="w-16 h-16 rounded-full bg-surface-raised border border-edge text-ink-muted flex items-center justify-center">
                    <span class="text-xs">?</span>
                </div>
            </div>
        </div>

        <!-- Etiqueta de rareza -->
        <span
            v-if="!isLocked"
            class="text-[10px] font-bold px-2 py-0.5 rounded-full"
            :class="rarityColor"
        >
            {{ t(`ui.album_rarity_${props.item.rarity}`) }}
        </span>
        <span v-else class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-surface text-ink-muted">
            {{ t('ui.album_locked') }}
        </span>

        <!-- Nombre -->
        <h4
            class="font-serif font-bold text-sm text-center leading-tight"
            :class="{ 'text-ink': !isLocked, 'text-ink-muted': isLocked }"
        >
            {{ isLocked ? t('ui.album_unknown') : props.item.name }}
        </h4>
    </div>
</template>
```

- [ ] **Step 2: Escribir `AlbumProgress.vue`**

```vue
<script setup>
defineProps({
    progress: { type: Object, required: true },
});
</script>

<template>
    <div class="mb-4 flex flex-col gap-1">
        <div class="flex justify-between items-end mb-1">
            <span class="text-xs uppercase tracking-wider font-bold text-ink-muted">{{ t('ui.album_progress') }}</span>
            <span class="font-serif font-bold text-xl text-accent">
                {{ progress.unlocked }} / {{ progress.total }}
            </span>
        </div>
        <div class="w-full h-3 rounded-full bg-edge overflow-hidden border border-edge/40">
            <div
                class="h-full bg-gradient-to-r from-accent via-tertiary to-danger rounded-full transition-all duration-700"
                :style="{ width: `${progress.total ? (progress.unlocked / progress.total) * 100 : 0}%` }"
            ></div>
        </div>
        <div class="flex justify-between text-[10px] font-bold text-ink-muted mt-1">
            <span>{{ t('ui.album_rarity_comun') }}: {{ progress.byRarity.comun }}</span>
            <span>{{ t('ui.album_rarity_rara') }}: {{ progress.byRarity.rara }}</span>
            <span>{{ t('ui.album_rarity_epica') }}: {{ progress.byRarity.epica }}</span>
        </div>
    </div>
</template>
```

- [ ] **Step 3: Escribir `AlbumFilter.vue`**

```vue
<script setup>
defineProps({
    active: { type: String, required: true },
});

const emit = defineEmits(['update:active']);
</script>

<template>
    <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-edge/40">
        <button
            v-for="category in ['all', 'card', 'sticker']"
            :key="category"
            :class="[
                'px-3 py-1.5 rounded-xl text-sm font-bold transition-all',
                active === category
                    ? 'bg-accent text-white shadow-sm'
                    : 'bg-surface text-ink-muted hover:bg-surface-raised',
            ]"
            @click="emit('update:active', category)"
        >
            {{ t(`ui.album_filter_${category}`) }}
        </button>
    </div>
</template>
```

- [ ] **Step 4: Escribir `AlbumDetailModal.vue`**

```vue
<script setup>
defineProps({
    show: { type: Boolean, default: false },
    item: { type: Object, default: null },
});

const emit = defineEmits(['close']);
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 bg-ink/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="emit('close')"
    >
        <div class="bg-surface rounded-modal border border-edge shadow-2xl max-w-md w-full p-6 transform animate-popIn">
            <button
                type="button"
                class="absolute top-4 right-4 text-ink-muted hover:text-ink w-10 h-10 rounded-control flex items-center justify-center"
                @click="emit('close')"
            >
                <span class="text-tinta">✕</span>
            </button>

            <div class="text-center pt-2">
                <div class="w-32 h-32 mx-auto mb-4 flex items-center justify-center rounded-xl border border-edge bg-surface/80 p-2">
                    <component
                        v-if="item"
                        :is="item.type === 'sticker' ? 'LibrarySticker' : 'CardEmblem'"
                        :key="item.key"
                        :sticker="item.key"
                        :name="item.image ?? 'book'"
                        :size="112"
                    />
                </div>

                <span
                    v-if="item"
                    class="inline-block px-3 py-1 text-[10px] font-bold rounded-full mb-2"
                    :class="{
                        'bg-amber-100 text-amber-700': item.rarity === 'epica',
                        'bg-blue-100 text-blue-700': item.rarity === 'rara',
                        'bg-surface text-ink-muted': item.rarity === 'comun',
                    }"
                >
                    {{ t(`ui.album_rarity_${item.rarity}`) }}
                </span>

                <h3 v-if="item" class="font-serif font-bold text-xl text-tinta mb-1">
                    {{ item.name }}
                </h3>
                <p v-if="item" class="text-sm text-ink-muted mb-4 px-2">
                    {{ t('ui.album_category', { category: item.category }) }}
                </p>

                <div v-if="item" class="bg-pergamino rounded-card p-3 flex justify-around text-xs font-bold text-tinta mb-4 border border-edge">
                    <div>
                        <span class="block text-ink-muted text-[10px] uppercase">{{ t('ui.album_number') }}</span>
                        <span class="text-tinta">#{{ item.key }}</span>
                    </div>
                    <div v-if="item.acquired_at" class="border-l border-edge/40 pl-3">
                        <span class="block text-ink-muted text-[10px] uppercase">{{ t('ui.album_acquired') }}</span>
                        <span class="text-tinta">{{ item.acquired_at }}</span>
                    </div>
                </div>

                <button type="button" v-if="item" class="w-full py-3 bg-accent text-white font-bold rounded-control hover:bg-accent/90 transition" @click="emit('close')">
                    {{ t('ui.album_understood') }}
                </button>
            </div>
        </div>
    </div>
</template>

<style>
.animate-popIn {
    animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}
@keyframes popIn {
    0% { transform: scale(0.5); opacity: 0; }
    80% { transform: scale(1.08); }
    100% { transform: scale(1); opacity: 1; }
}
</style>
```

- [ ] **Step 5: Ejecutar lint (pint) sobre los componentes nuevos**

Run: `php vendor\bin\pint resources/js/Components/AlbumSlot.vue resources/js/Components/AlbumProgress.vue resources/js/Components/AlbumFilter.vue resources/js/Components/AlbumDetailModal.vue`  
Expected: no cambios de formato pendientes (o solo estilo).

---

## Task 5: Crear la página Inertia `Album/Index.vue`

**Files:**
- Create: `resources/js/Pages/Album/Index.vue`

- [ ] **Step 1: Escribir la página del álbum**

```vue
<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import { useI18n } from '@/i18n';
import AlbumSlot from '@/Components/AlbumSlot.vue';
import AlbumProgress from '@/Components/AlbumProgress.vue';
import AlbumFilter from '@/Components/AlbumFilter.vue';
import AlbumDetailModal from '@/Components/AlbumDetailModal.vue';

const props = defineProps({
    items: { type: Array, required: true },
    progress: { type: Object, required: true },
});

const { t } = useI18n();

const activeFilter = ref('all');
const modalItem = ref(null);

const filteredItems = computed(() => {
    if (activeFilter.value === 'all') return props.items;
    return props.items.filter((item) => item.type === activeFilter.value);
});

function openDetail(item) {
    modalItem.value = item;
}
</script>

<template>
    <Head :title="t('ui.album_title')" />

    <AuthenticatedLayout>
        <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-serif text-3xl text-accent">{{ t('ui.album_title') }}</h1>
                <p class="text-sm text-ink-muted">{{ t('ui.album_subtitle') }}</p>
            </div>
        </header>

        <AlbumProgress :progress="progress" />

        <AlbumFilter v-model:active="activeFilter" />

        <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <AlbumSlot
                v-for="item in filteredItems"
                :key="`${item.type}-${item.key}`"
                :item="item"
                @click="openDetail(item)"
            />
        </div>

        <AlbumDetailModal :show="modalItem !== null" :item="modalItem" @close="modalItem = null" />

        <div v-if="props.items.length === 0" class="mt-12 text-center text-ink-muted">
            <p>{{ t('ui.album_empty') }}</p>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 2: Ejecutar los tests de frontend para verificar que no hay errores de compilación**

Run: `npm run test -- --run`  
Expected: PASS (la página se compila; los componentes nuevos no tienen tests aún, pero no rompen nada).

---

## Task 6: Añadir entradas de navegación (AppShell y BottomNav)

**Files:**
- Modify: `resources/js/Components/AppShell.vue`
- Modify: `resources/js/Components/BottomNav.vue`

- [ ] **Step 1: Añadir el álbum a los enlaces de `AppShell.vue`**

```js
    { label: t('ui.nav_decorations'), href: '/decorations' },
    { label: t('ui.nav_album'), href: '/album' },
    { label: t('ui.nav_settings'), href: '/settings' },
```

- [ ] **Step 2: Añadir el álbum al menú móvil de `BottomNav.vue`**

```js
    { label: t('ui.nav_decorations'), href: '/decorations', iconPath: 'M12 3l1.9 5.8L20 10l-6.1 1.2L12 17l-1.9-5.8L4 10l6.1-1.2z' },
    { label: t('ui.nav_album'), href: '/album', iconPath: 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5' },
    { label: t('ui.nav_settings'), href: '/settings', iconPath: 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z' },
```

- [ ] **Step 3: Ejecutar prueba de navegación (AppShell) si existe**

Run: `php artisan test --filter=AlbumTest && npm run test -- --run resources/js/Components/__tests__/AppShell.test.js`  
Expected: PASS.

---

## Task 7: Añadir claves de idioma

**Files:**
- Modify: `lang/es.json`
- Modify: `lang/en.json`

- [ ] **Step 1: Añadir claves en `lang/es.json`**

```json
  "ui": {
    "nav_album": "Álbum",
    "album_title": "Tu álbum",
    "album_subtitle": "Tus cartas y stickers coleccionados",
    "album_progress": "Progreso de la colección",
    "album_filter_all": "Todos",
    "album_filter_card": "Cartas",
    "album_filter_sticker": "Stickers",
    "album_rarity_comun": "Común",
    "album_rarity_rara": "Rara",
    "album_rarity_epica": "Épica",
    "album_locked": "Bloqueado",
    "album_unknown": "???",
    "album_category": "{category}",
    "album_number": "Número",
    "album_acquired": "Obtenido",
    "album_understood": "Entendido",
    "album_empty": "Aún no has coleccionado nada."
  }
```

- [ ] **Step 2: Añadir claves en `lang/en.json`**

```json
  "ui": {
    "nav_album": "Album",
    "album_title": "Your album",
    "album_subtitle": "Your collected cards and stickers",
    "album_progress": "Collection progress",
    "album_filter_all": "All",
    "album_filter_card": "Cards",
    "album_filter_sticker": "Stickers",
    "album_rarity_comun": "Common",
    "album_rarity_rara": "Rare",
    "album_rarity_epica": "Epic",
    "album_locked": "Locked",
    "album_unknown": "???",
    "album_category": "{category}",
    "album_number": "Number",
    "album_acquired": "Acquired",
    "album_understood": "Understood",
    "album_empty": "You haven't collected anything yet."
  }
```

- [ ] **Step 4: Ejecutar la prueba de catálogo de idiomas**

Run: `php artisan test --filter=CatalogTest`  
Expected: PASS (los catálogos siguen planos y con las mismas claves en ambos idiomas).

---

## Task 8: Escribir la prueba del componente `AlbumSlot`

**Files:**
- Create: `resources/js/Components/__tests__/AlbumSlot.test.js`

- [ ] **Step 1: Escribir pruebas del componente `AlbumSlot`**

```js
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import AlbumSlot from '../AlbumSlot.vue';

describe('AlbumSlot', () => {
    it('renderiza una carta desbloqueada con nombre y emblema', () => {
        const wrapper = mount(AlbumSlot, {
            props: {
                item: {
                    type: 'card',
                    key: 'violet',
                    name: 'Violet Sorrengail',
                    category: 'Empíreo',
                    rarity: 'comun',
                    unlocked: true,
                },
            },
        });

        expect(wrapper.text()).toContain('Violet Sorrengail');
        expect(wrapper.find('.library-sticker__art').exists()).toBe(false);
    });

    it('renderiza un sticker desbloqueado con LibrarySticker', () => {
        const wrapper = mount(AlbumSlot, {
            props: {
                item: {
                    type: 'sticker',
                    key: 'maquina_libro',
                    name: 'Libro',
                    category: 'Stickers de la máquina',
                    rarity: 'comun',
                    unlocked: true,
                },
            },
        });

        expect(wrapper.text()).toContain('Libro');
        expect(wrapper.findComponent({ name: 'LibrarySticker' }).exists()).toBe(true);
    });

    it('muestra estado bloqueado con candado y ???', () => {
        const wrapper = mount(AlbumSlot, {
            props: {
                item: {
                    type: 'card',
                    key: 'xaden',
                    name: 'Xaden Riorson',
                    category: 'Empíreo',
                    rarity: 'comun',
                    unlocked: false,
                },
            },
        });

        expect(wrapper.text()).toContain('???');
        expect(wrapper.attributes('aria-label')).toContain('Bloqueado');
    });
});
```

- [ ] **Step 2: Ejecutar la prueba de `AlbumSlot`**

Run: `npm run test -- --run resources/js/Components/__tests__/AlbumSlot.test.js`  
Expected: PASS (3 tests).

---

## Task 8: Actualizar `docs/DISENO.md`

**Files:**
- Modify: `docs/DISENO.md`

- [ ] **Step 1: Añadir el apartado del álbum y la fila del registro de cambios**

Añadir antes de `### Eventos (`Events/Index`)`:

```markdown
### Álbum de colección (`Album/Index`)

- **Navegación:** entrada propia **Álbum** (`ui.nav_album`) en la barra lateral y en el menú móvil, separada de Máquina expendedora y Eventos; se puede abrir sin pasar por la máquina.
- **Página (`Album/Index`):** muestra las cartas desbloqueadas y los stickers de máquina obtenidos en una misma rejilla tipo álbum de estampas. Cabecera con título `ui.album_title` y ayuda `ui.album_subtitle`.
- **Barra de progreso (`AlbumProgress`):** contador "N de M" con barra `accent` y desglose por rareza (`ui.album_rarity_*`). Total 40 piezas (20 cartas + 20 stickers).
- **Filtros (`AlbumFilter`):** pestañas Todas / Cartas / Stickers (`ui.album_filter_*`).
- **Tarjetas (`AlbumSlot`):** bordes troquelados blancos (`library-sticker__cut`), sombra suave, efecto peel-off al hover; bloqueadas muestran candado y `???` sin clic.
- **Detalle en modal (`AlbumDetailModal`):** ilustración grande, nombre, categoría, rareza con color, número y fecha de obtención; cierre con Escape y clic en el velo.
- **Estados:** progreso y filtro funcionan solo con objetos del usuario autenticado; nunca mezcla datos de otras cuentas.
- **Clases nuevas en `textures.css`:** ninguna nueva obligatoria (reusa `library-sticker`, `card-slot`, tokens existentes). No se añaden colores nuevos.
```

Añadir al final del registro de cambios:

```markdown
| 2026-11-10 | Nuevo módulo **Álbum de colección** en `/album`, independiente de la Máquina expendedora, con entrada propia en navegación. Muestra cartas y stickers de máquina en una rejilla tipo álbum digital con barra de progreso, filtros por categoría, tarjetas troqueladas y detalle en modal. Reusa `LibrarySticker` y `CardEmblem`; sin colores nuevos. | Agente |
```

- [ ] **Step 2: Ejecutar `git diff --check` para docs**

Run: `git diff --check docs/DISENO.md`  
Expected: solo avisos CRLF (aceptables).

---

## Task 9: Verificación final

**Files:** ninguno nuevo.

- [ ] **Step 1: Ejecutar todos los tests de backend**

Run: `php artisan test`  
Expected: PASS (incluye `AlbumTest` y los existentes).

- [ ] **Step 2: Ejecutar todos los tests de frontend**

Run: `npm run test -- --run`  
Expected: PASS.

- [ ] **Step 3: Compilar producción**

Run: `npm run build`  
Expected: build correcto.

- [ ] **Step 4: Ejecutar Pint en los archivos PHP y Vue nuevos**

Run: `php vendor\bin\pint app/Http/Controllers/AlbumController.php`  
Expected: sin cambios de formato pendientes.

- [ ] **Step 5: Revisar el diff final**

Run: `git status --short` y `git diff --stat`  
No commit. Informar a la usuaria de los archivos modificados.

---

## Self-Review

- **Spec coverage:** Task 1-3 implementan el backend del álbum (spec §3, §6). Task 4-5 implementan el frontend y componentes (spec §4, §5). Task 6-7 añaden navegación e idiomas. Task 8 documenta (spec §6). Task 9 verifica (spec §7).
- **Placeholder scan:** no hay TBD ni TODO; cada paso tiene código o instrucciones exactas.
- **Type consistency:** `AlbumController` devuelve `items` con claves `type`, `key`, `name`, `category`, `rarity`, `unlocked`, `copies`, `acquired_at`; `AlbumSlot` consume exactamente esas claves. `AlbumDetailModal` usa `item.type`, `item.key`, `item.name`, `item.rarity`, `item.acquired_at`. Consistencia verificada.
- **Fuera de alcance respetado:** no se toca la máquina expendedora ni `/events/album`.
