# Diseño: eventos rotativos (fase 2)

**Fecha:** 2026-11-05 · **Estado:** aprobado por la usuaria
**Relacionado:** `2026-11-03-cartas-bombo-design.md` (fase 1)

## Objetivo

Eventos rotativos de 1–4 días que dan tareas diarias, recompensan con tinta y
permiten **gastarla** en coleccionables: pegatinas y cartas de evento. Los
minijuegos quedan para la fase 3.

## Decisiones de la usuaria

- Coleccionables: **pegatinas + cartas de evento**.
- Temática: **mezcla** de temáticas del mes y eventos propios.
- Precios **caros/valiosos**: pegatinas 15–25 tinta, cartas de evento 50–80.
- UI: **sección propia «Eventos»** en el menú (entre Cartas y Decoraciones).

## Rotación determinista

- `config/events.php`: catálogo encadenado de 6 eventos (duración 2–4 días,
  ciclo de 16 días) con: clave, nombre, temática (`halloween`, `christmas`,
  `valentine`, `flowers`, `dragon`, `book`), tareas del día y tienda.
- Época fija `2026-11-05` (config `events.epoch`); zona horaria
  `CARDS_TIMEZONE` (la misma de las cartas). Sin cron: cada petición calcula
  `dayNumber = días desde época` y recorre el ciclo (`dayNumber % 16`) para
  saber el evento activo y el día dentro del evento (1..duración).

| Evento | Tema | Días |
|---|---|---|
| Feria del libro antiguo | book | 3 |
| Noche de dragones | dragon | 2 |
| Cuentos de invierno | christmas | 4 |
| Cartas de enamorados | valentine | 2 |
| La noche de las brujas | halloween | 3 |
| Feria de las flores | flowers | 2 |

## Tareas diarias

Tres tareas por evento, del catálogo: `draw` (tirar la balota), `read`
(registrar una sesión de lectura), `add_book` (añadir un libro) y `album`
(abrir el álbum). Cada tarea completada otorga **2–3 de tinta** (según
evento) y se registra **una vez por día y evento** (filas únicas).

Hooks en el código existente (fallos silenciosos, nunca rompen la acción):
- `CardDeckService::draw` → `draw`.
- `ReadingController@storeSession` → `read`.
- `BookController@store` → `add_book`.
- `CardController::album` → `album`.

## Tienda y colección

- `POST /events/buy` (`throttle:30,1`): compra un coleccionable de la tienda
  del evento activo con tinta. Validaciones: tinta suficiente (422),
  coleccionable ya obtenido (409). Compra atómica en transacción.
- Cartas de evento: 50–80 tinta. Pegatinas: 15–25 tinta.
- Los coleccionables se guardan en `user_collectibles`
  (único `user_id + collectible_key`) con `kind` (`sticker`/`card`),
  `event_key`, `source` (`shop`) y fecha.

## Modelo de datos

| Tabla | Columnas clave |
|---|---|
| `event_progress` | `user_id` FK, `event_key`, `day_date`, `task_key`, `ink_earned`; único (`user_id`,`event_key`,`day_date`,`task_key`) |
| `user_collectibles` | `user_id` FK, `collectible_key`, `kind`, `event_key`, `source`, `acquired_at`; único (`user_id`,`collectible_key`) |

## Página «Eventos» (`Events/Index`)

- Cabecera: nombre del evento, **Día X de Y**, chip de temática.
- **Tareas del día**: lista con estado (hecha/pendiente) y recompensa.
- **Tienda**: rejilla de pegatinas (SVG temático) y cartas de evento con
  coste; botón comprar (o «En tu colección»).
- **Mi colección**: rejilla de coleccionables obtenidos.
- Accesibilidad: checklist con `aria-current`, botones con `aria-label`,
  aviso de resultado con `role="status"`.

## Navegación

- Nueva entrada **Eventos** (`ui.nav_events`) entre Cartas y Decoraciones;
  en móvil entra al menú desplegable. `AppShell` actualiza enlaces.

## Contenido

- 6 eventos × 2 pegatinas + 1 carta de evento (12 pegatinas, 6 cartas).
- Pegatinas: SVG de línea reutilizando la paleta (libro, dragón, copo,
  corazón, calavera, flor, pluma, lámpara). Cartas de evento: marco
  temático + inicial y nombre (texto corto, sin carta larga).
- Nombres de eventos y coleccionables viven en `config/events.php`
  (español, como los personajes); las etiquetas de interfaz van al
  catálogo (`ui.events_*`) en los 8 idiomas.

## Pruebas

- `EventRotationTest`: rotación determinista (época, salto de evento,
  ciclo completo).
- `EventProgressTest`: hooks completan una vez al día, tinta otorgada,
  sin duplicados.
- `EventShopTest`: compra con tinta, tinta insuficiente 422, duplicado 409.
- `EventPageTest`: página con props del evento; AppShell con enlace nuevo.

## Fuera de alcance

- Minijuegos (fase 3). Traducciones de las cartas largas. Notificaciones.
