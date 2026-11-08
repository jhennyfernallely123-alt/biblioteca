# Álbum de pegatinas ilustradas — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **NOTA del proyecto (AGENTS.md):** la usuaria hace los commits. Ninguna tarea ejecuta `git`.

**Goal:** Álbum propio de pegatinas (`/events/album`) con las 12 pegatinas rediseñadas como ilustraciones SVG troqueladas y las no obtenidas en silueta.

**Architecture:** `EventSticker` pasa de emblema genérico a mapa de 12 ilustraciones SVG con silueta blanca y sombra. `EventService` expone el catálogo completo de pegatinas y las claves obtenidas. `EventController::album` renderiza `Events/Album`. La página Eventos enlaza al álbum y usa el componente por clave.

**Tech Stack:** Laravel 13, Inertia + Vue 3, Tailwind 4, Pest, Vitest.

**Spec:** `docs/superpowers/specs/2026-11-05-album-stickers-design.md`

---

## Task 1: Datos del catálogo (TDD)

**Files:**
- Modify: `app/Services/EventService.php`
- Test: `tests/Feature/EventAlbumTest.php` (se completa en Task 3)

- [ ] **Step 1: Métodos de catálogo**

Añadir a `EventService`:

```php
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
```

- [ ] **Step 2: Verde**

```bash
cmd.exe /C 'php artisan test --filter=EventProgressTest'
cmd.exe /C 'vendor\bin\pint app\Services\EventService.php'
```

## Task 2: Componente `EventSticker` ilustrado

**Files:**
- Modify: `resources/js/Components/EventSticker.vue`
- Modify: `resources/css/textures.css`
- Create: `resources/js/Components/__tests__/EventSticker.test.js`
- Modify: `resources/js/Pages/Events/Index.vue`

- [ ] **Step 1: Reescribir `EventSticker.vue`**

Props `itemKey` (String), `size` (Number, 96), `locked` (Boolean). Mapa `artworks`
con las 12 claves y su `<g>` SVG. Blob troquelado blanco detrás y sombra. Clave
desconocida degrada a estrella. Bloqueada: silueta gris con «?».

- [ ] **Step 2: Clases CSS**

Añadir a `textures.css`: `.event-sticker` (contenedor, sin borde discontinuo),
`.event-sticker__cut` (blanco), `.event-sticker__art`, `.event-sticker--locked`.

- [ ] **Step 3: Usar por clave en Eventos**

En `Pages/Events/Index.vue`, la tienda y la colección pasan `<EventSticker :item-key="item.key" />`;
las cartas siguen con `card-monogram`.

- [ ] **Step 4: Test de componente**

Comprobar que se renderizan las 12 claves, que `locked` no incluye ilustración y
que una clave desconocida no rompe.

## Task 3: Página del álbum (TDD)

**Files:**
- Modify: `app/Http/Controllers/EventController.php`
- Modify: `routes/web.php`
- Create: `resources/js/Pages/Events/Album.vue`
- Create: `tests/Feature/EventAlbumTest.php`
- Modify: `resources/js/Pages/Events/Index.vue` (botón al álbum)

- [ ] **Step 1: Test en rojo** (`EventAlbumTest`)

Fechar el test en `2026-11-05`, crear usuaria y una pegatina obtenida. Verificar
`Events/Album`, 12 stickers, `progress.owned` 1, `progress.total` 12, `owned`
correcto y que no aparecen coleccionables de otra usuaria.

- [ ] **Step 2: Controlador y ruta**

`EventController::album` devuelve `stickers` (catálogo + `owned`), `progress` e
`ink`. Ruta `GET /events/album` → `events.album` antes de `/events/buy`.

- [ ] **Step 3: Vista `Events/Album.vue`**

Cabecera con título, progreso y tinta; panel `.event-album` con rejilla de
casillas; obtenidas con `EventSticker` y nombre; bloqueadas con silueta y
«Aún no la tienes». `aria-label` por casilla y `progressbar` en el contador.

- [ ] **Step 4: Acceso desde Eventos**

Botón «Ver el álbum de pegatinas» (`ui.events_open_album`) que enlaza a
`/events/album`.

## Task 4: Textos 8 idiomas

**Files:**
- Modify: `lang/{es,en,pt,fr,de,it,ca,gl}.json`

- [ ] **Step 1: Claves**

`ui.events_stickers_album`, `ui.events_stickers_progress`, `ui.events_open_album`,
`ui.events_locked`. Añadir con script PHP preservando UTF-8.

## Task 5: Documentación y verificación

**Files:**
- Modify: `docs/DISENO.md`

- [ ] **Step 1: DISENO.md**

Sección «Álbum de pegatinas» (página, casillas, troquel, silueta, responsive),
fila fechada 2026-11-05 y `**Última actualización:**`.

- [ ] **Step 2: Suite completa**

```bash
cmd.exe /C 'php artisan test'
cmd.exe /C 'npm run test'
cmd.exe /C 'vendor\bin\pint --test'
cmd.exe /C 'npm run build'
```

- [ ] **Step 3: Revisión manual**

Abrir `/events` y `/events/album` en 360 px y escritorio; comprobar la compra de
una pegatina, la aparición en el álbum y la silueta de las no obtenidas. Informar
archivos; la usuaria hace el commit.
