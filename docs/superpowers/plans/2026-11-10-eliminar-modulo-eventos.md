# Eliminación de módulo de Eventos — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Eliminar completamente el módulo de Eventos de la aplicación Biblioteca: código backend, frontend, rutas, navegación, pruebas, documentación, configuración y migración de base de datos, sin dejar código huérfano ni referencias rotas.

**Architecture:** Se eliminan los archivos del módulo de eventos (controlador, servicio, modelo, migración, componentes Vue, rutas, navegación, tests, specs, docs, claves de idioma). Se crea una migración para dropear la tabla `event_progress`. No se modifica ningún otro módulo de la aplicación.

**Tech Stack:** Laravel 13 (PHP 8.3), Vue 3, Inertia.js, Tailwind CSS 4, Pest/PHPUnit, Vitest.

---

## File Structure

- **Create:** `database/migrations/2026_11_10_000002_drop_event_progress_table.php`
- **Delete:** `app/Http/Controllers/EventController.php`
- **Delete:** `app/Services/EventService.php`
- **Delete:** `app/Models/EventProgress.php`
- **Delete:** `database/migrations/2026_11_05_000001_create_event_progress_table.php`
- **Delete:** `config/events.php`
- **Delete:** `resources/js/Components/EventSticker.vue`
- **Delete:** `resources/js/Components/__tests__/EventSticker.test.js`
- **Modify:** `routes/web.php` (quitar rutas de eventos e importación de EventController)
- **Modify:** `resources/js/Components/AppShell.vue` (quitar enlace de Eventos)
- **Modify:** `resources/js/Components/BottomNav.vue` (quitar entrada de Eventos del menú móvil)
- **Modify:** `lang/es.json` (quitar claves `ui.nav_events` y `ui.event_*`)
- **Modify:** `lang/en.json` (quitar claves `ui.nav_events` y `ui.event_*`)
- **Delete:** `tests/Feature/EventPageTest.php`
- **Delete:** `tests/Feature/EventAlbumTest.php`
- **Delete:** `tests/Feature/EventProgressTest.php`
- **Delete:** `tests/Feature/EventShopTest.php`
- **Delete:** `tests/Feature/EventRotationTest.php`
- **Delete:** `docs/superpowers/specs/2026-11-05-eventos-design.md`
- **Delete:** `docs/superpowers/plans/2026-11-05-eventos.md`
- **Modify:** `docs/DISENO.md` (quitar sección de Eventos y registro de cambios relacionados)

---

## Task 1: Crear migración para dropear tabla event_progress

**Files:**
- Create: `database/migrations/2026_11_10_000002_drop_event_progress_table.php`

- [ ] **Step 1: Escribir la migración**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('event_progress');
    }

    public function down(): void
    {
        Schema::create('event_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('event_key');
            $table->integer('completed_tasks')->default(0);
            $table->timestamps();
        });
    }
};
```

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint database/migrations/2026_11-10_000002_drop_event_progress_table.php`  
Expected: sin cambios de formato pendientes.

---

## Task 2: Eliminar controlador EventController

**Files:**
- Delete: `app/Http/Controllers/EventController.php`

- [ ] **Step 1: Eliminar el archivo**

Run: `Remove-Item app/Http/Controllers/EventController.php` (o `rm app/Http/Controllers/EventController.php`)

- [ ] **Step 2: Verificar que el archivo ya no existe**

Run: `Test-Path app/Http/Controllers/EventController.php`  
Expected: `False` (el archivo no existe).

---

## Task 3: Eliminar servicio EventService

**Files:**
- Delete: `app/Services/EventService.php`

- [ ] **Step 1: Eliminar el archivo**

Run: `Remove-Item app/Services/EventService.php`

- [ ] **Step 2: Verificar que el archivo ya no existe**

Run: `Test-Path app/Services/EventService.php`  
Expected: `False`.

---

## Task 4: Eliminar modelo EventProgress

**Files:**
- Delete: `app/Models/EventProgress.php`

- [ ] **Step 1: Eliminar el archivo**

Run: `Remove-Item app/Models/EventProgress.php`

- [ ] **Step 2: Verificar que el archivo ya no existe**

Run: `Test-Path app/Models/EventProgress.php`  
Expected: `False`.

---

## Task 5: Eliminar migración original de event_progress

**Files:**
- Delete: `database/migrations/2026_11_05_000001_create_event_progress_table.php`

- [ ] **Step 1: Eliminar el archivo**

Run: `Remove-Item database/migrations/2026_11_05_000001_create_event_progress_table.php`

- [ ] **Step 2: Verificar que el archivo ya no existe**

Run: `Test-Path database/migrations/2026_11_05_000001_create_event_progress_table.php`  
Expected: `False`.

---

## Task 6: Eliminar config/events.php

**Files:**
- Delete: `config/events.php`

- [ ] **Step 1: Eliminar el archivo**

Run: `Remove-Item config/events.php`

- [ ] **Step 2: Verificar que el archivo ya no existe**

Run: `Test-Path config/events.php`  
Expected: `False`.

---

## Task 7: Eliminar componente EventSticker.vue

**Files:**
- Delete: `resources/js/Components/EventSticker.vue`

- [ ] **Step 1: Eliminar el archivo**

Run: `Remove-Item resources/js/Components/EventSticker.vue`

- [ ] **Step 2: Verificar que el archivo ya no existe**

Run: `Test-Path resources/js/Components/EventSticker.vue`  
Expected: `False`.

---

## Task 8: Eliminar prueba EventSticker.test.js

**Files:**
- Delete: `resources/js/Components/__tests__/EventSticker.test.js`

- [ ] **Step 1: Eliminar el archivo**

Run: `Remove-Item resources/js/Components/__tests__/EventSticker.test.js`

- [ ] **Step 2: Verificar que el archivo ya no existe**

Run: `Test-Path resources/js/Components/__tests__/EventSticker.test.js`  
Expected: `False`.

---

## Task 9: Eliminar rutas de eventos en routes/web.php

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Quitar la importación de EventController**

Sustituir:

```php
use App\Http\Controllers\EventController;
```

por nada (eliminar la línea completa).

- [ ] **Step 2: Quitar las 3 rutas de eventos**

Sustituir:

```php
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/album', [EventController::class, 'album'])->name('events.album');
    Route::post('/events/buy', [EventController::class, 'buy'])
        ->middleware('throttle:30,1')
        ->name('events.buy');
```

por nada (eliminar las tres líneas/bloque).

- [ ] **Step 3: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint routes/web.php`  
Expected: sin cambios de formato pendientes.

---

## Task 10: Quitar entrada de Eventos en AppShell.vue

**Files:**
- Modify: `resources/js/Components/AppShell.vue`

- [ ] **Step 1: Quitar el enlace de Eventos del array de links**

Sustituir:

```js
    { label: t('ui.nav_events'), href: '/events' },
```

por nada (eliminar la línea completa, incluyendo la coma si es el último elemento o dejar la coma del elemento anterior según corresponda).

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/js/Components/AppShell.vue`  
Expected: sin cambios de formato pendientes.

---

## Task 11: Quitar entrada de Eventos en BottomNav.vue

**Files:**
- Modify: `resources/js/Components/BottomNav.vue`

- [ ] **Step 1: Quitar la entrada de Eventos del menú móvil**

Sustituir:

```js
    { label: t('ui.nav_events'), href: '/events', iconPath: 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z' },
```

por nada (eliminar la línea completa).

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/js/Components/BottomNav.vue`  
Expected: sin cambios de formato pendientes.

---

## Task 12: Quitar claves de idioma en lang/es.json

**Files:**
- Modify: `lang/es.json`

- [ ] **Step 1: Quitar la clave `ui.nav_events`**

Buscar y eliminar:

```json
    "ui.nav_events": "Eventos",
```

- [ ] **Step 2: Quitar las claves `ui.event_*`**

Buscar y eliminar todas las claves que empiecen con `ui.event_` en `lang/es.json`.

- [ ] **Step 3: Ejecutar la prueba de catálogo de idiomas**

Run: `php artisan test --filter=CatalogTest`  
Expected: PASS (los catálogos siguen planos y con las mismas claves en ambos idiomas).

---

## Task 13: Quitar claves de idioma en lang/en.json

**Files:**
- Modify: `lang/en.json`

- [ ] **Step 1: Quitar la clave `ui.nav_events`**

Buscar y eliminar:

```json
    "ui.nav_events": "Events",
```

- [ ] **Step 2: Quitar las claves `ui.event_*`**

Buscar y eliminar todas las claves que empiecen con `ui.event_` en `lang/en.json`.

- [ ] **Step 3: Ejecutar la prueba de catálogo de idiomas**

Run: `php artisan test --filter=CatalogTest`  
Expected: PASS.

---

## Task 14: Eliminar pruebas de eventos (backend)

**Files:**
- Delete: `tests/Feature/EventPageTest.php`
- Delete: `tests/Feature/EventAlbumTest.php`
- Delete: `tests/Feature/EventProgressTest.php`
- Delete: `tests/Feature/EventShopTest.php`
- Delete: `tests/Feature/EventRotationTest.php`

- [ ] **Step 1: Eliminar los 5 archivos de prueba**

Run: `Remove-Item tests/Feature/EventPageTest.php tests/Feature/EventAlbumTest.php tests/Feature/EventProgressTest.php tests/Feature/EventShopTest.php tests/Feature/EventRotationTest.php`

- [ ] **Step 2: Verificar que los archivos ya no existen**

Run: `Test-Path tests/Feature/EventPageTest.php` (y los otros 4)  
Expected: `False` para cada uno.

---

## Task 15: Eliminar prueba de EventSticker (frontend)

**Files:**
- Delete: `resources/js/Components/__tests__/EventSticker.test.js`

- [ ] **Step 1: Eliminar el archivo**

Run: `Remove-Item resources/js/Components/__tests__/EventSticker.test.js`

- [ ] **Step 2: Verificar que el archivo ya no existe**

Run: `Test-Path resources/js/Components/__tests__/EventSticker.test.js`  
Expected: `False`.

---

## Task 16: Eliminar specs y plans de eventos

**Files:**
- Delete: `docs/superpowers/specs/2026-11-05-eventos-design.md`
- Delete: `docs/superpowers/plans/2026-11-05-eventos.md`

- [ ] **Step 1: Eliminar ambos archivos**

Run: `Remove-Item docs/superpowers/specs/2026-11-05-eventos-design.md docs/superpowers/plans/2026-11-05-eventos.md`

- [ ] **Step 2: Verificar que los archivos ya no existen**

Run: `Test-Path docs/superpowers/specs/2026-11-05-eventos-design.md` (y el otro)  
Expected: `False` para cada uno.

---

## Task 17: Actualizar docs/DISENO.md

**Files:**
- Modify: `docs/DISENO.md`

- [ ] **Step 1: Quitar la sección de Eventos**

Buscar y eliminar la sección `### Eventos (\`Events/Index\`)` y todo su contenido hasta la siguiente sección.

- [ ] **Step 2: Quitar la fila del registro de cambios relacionada con eventos**

Buscar y eliminar cualquier fila del registro de cambios que mencione "Eventos" o "eventos".

- [ ] **Step 3: Ejecutar `git diff --check` para docs**

Run: `git diff --check docs/DISENO.md`  
Expected: solo avisos CRLF (aceptables).

---

## Task 16: Verificación final

**Files:** ninguno nuevo.

- [ ] **Step 1: Ejecutar todos los tests de backend**

Run: `php artisan test`  
Expected: PASS (ninguna prueba de eventos falla porque ya no existen).

- [ ] **Step 2: Ejecutar todos los tests de frontend**

Run: `npm run test -- --run`  
Expected: PASS (ninguna prueba de EventSticker falla porque ya no existe).

- [ ] **Step 3: Compilar producción**

Run: `npm run build`  
Expected: build correcto.

- [ ] **Step 4: Ejecutar Pint en todos los archivos PHP y Vue modificados**

Run: `php vendor\bin\pint routes/web.php resources/js/Components/AppShell.vue resources/js/Components/BottomNav.vue`  
Expected: sin cambios de formato pendientes.

- [ ] **Step 5: Revisar el diff final**

Run: `git status --short` y `git diff --stat`  
No commit. Informar a la usuaria de los archivos modificados y eliminados.

---

## Self-Review

- **Spec coverage:** Task 1-5 eliminan la base de datos y modelos (spec §3). Task 6-9 eliminan archivos PHP (spec §3). Task 7-8 eliminan componentes frontend (spec §3). Task 9-11 eliminan rutas y navegación (spec §3). Task 12-13 eliminan idiomas (spec §3). Task 14-15 eliminan pruebas (spec §3). Task 16 elimina docs (spec §3). Task 17 verifica (spec §5).

- **Placeholder scan:** no hay TBD ni TODO; cada paso tiene código o instrucciones exactas.

- **Type consistency:** no hay referencias a tipos o métodos eliminados en otros archivos que queden pendientes (se eliminaron todos los archivos que referenciaban a EventController, EventService, EventProgress).

- **Fuera de alcance respetado:** no se toca el módulo de Cartas, Estanterías, Libros, Decoraciones ni Configuraciones. No se hace commit.
