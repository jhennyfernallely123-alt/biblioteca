# Eliminación del módulo de Eventos

**Fecha:** 2026-11-10  
**Estado:** Pendiente de revisión de la usuaria  
**Alcance:** Eliminación completa del módulo de Eventos del proyecto Biblioteca.

## 1. Objetivo

Eliminar por completo el módulo de Eventos de la aplicación Biblioteca, incluyendo código backend, frontend, rutas, navegación, pruebas, documentación, configuración y base de datos. No debe quedar código huérfano ni referencias rotas.

## 2. Decisiones de diseño

| Decisión | Elección |
|---|---|
| Alcance | Eliminación completa (Enfoque A) |
| Tabla `event_progress` | Dropear mediante migración |
| Archivos PHP | Eliminar controlador, servicio y modelo de eventos |
| Componentes Vue | Eliminar `EventSticker`, `Events/Index`, `Events/Album` |
| Rutas | Eliminar `/events` y `/events/album` de `routes/web.php` |
| Navegación | Quitar entradas de `AppShell.vue` y `BottomNav.vue` |
| Idiomas | Quitar claves `ui.nav_events`, `ui.event_*` de `lang/es.json` y `lang/en.json` |
| Pruebas backend | Eliminar `EventPageTest`, `EventAlbumTest`, `EventProgressTest`, `EventShopTest`, `EventRotationTest` |
| Pruebas frontend | Eliminar `EventSticker.test.js` |
| Documentación | Eliminar specs y planes de eventos; quitar sección de `docs/DISENO.md` |
| Configuración | Eliminar `config/events.php` |

## 3. Archivos a eliminar

### Backend (PHP)
- `app/Http/Controllers/EventController.php`
- `app/Services/EventService.php`
- `app/Models/EventProgress.php`
- `database/migrations/2026_11_05_000001_create_event_progress_table.php`
- `config/events.php`

### Frontend (Vue)
- `resources/js/Components/EventSticker.vue`
- `resources/js/Components/__tests__/EventSticker.test.js`
- `resources/js/Pages/Events/Index.vue` (si existe)
- `resources/js/Pages/Events/Album.vue` (si existe)

### Rutas
- `routes/web.php` — eliminar las 3 rutas de eventos y la importación de `EventController`

### Navegación
- `resources/js/Components/AppShell.vue` — quitar enlace de Eventos
- `resources/js/Components/BottomNav.vue` — quitar entrada de Eventos del menú móvil

### Pruebas
- `tests/Feature/EventPageTest.php`
- `tests/Feature/EventAlbumTest.php`
- `tests/Feature/EventProgressTest.php`
- `tests/Feature/EventShopTest.php`
- `tests/Feature/EventRotationTest.php`

### Documentación
- `docs/superpowers/specs/2026-11-05-eventos-design.md`
- `docs/superpowers/plans/2026-11-05-eventos.md`
- `docs/DISENO.md` — quitar sección de Eventos y registro de cambios relacionados

### Idiomas
- `lang/es.json` — quitar claves `ui.nav_events` y `ui.event_*`
- `lang/en.json` — quitar claves `ui.nav_events` y `ui.event_*`

## 4. Nueva migración

Crear una migración que dropee la tabla `event_progress`:

```php
Schema::dropIfExists('event_progress');
```

## 5. Verificación

- `php artisan test` — todas las pruebas deben pasar (excepto las eliminadas)
- `npm run test -- --run` — todas las pruebas frontend deben pasar
- `npm run build` — build correcto
- `php vendor\bin\pint` — sin cambios de formato pendientes
- `git diff --stat` — revisar archivos modificados/eliminados

## 6. Fuera de alcance

- No se elimina el módulo de Cartas, Estanterías, Libros, Decoraciones ni Configuraciones.
- No se modifica la lógica de negocio de otros módulos.
- No se hace commit; queda para la usuaria.
