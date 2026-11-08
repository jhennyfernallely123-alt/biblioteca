# Diseño: álbum de pegatinas ilustradas

**Fecha:** 2026-11-05 · **Estado:** aprobado por la usuaria
**Relacionado:** `2026-11-05-eventos-design.md` (fase 2), `2026-11-03-cartas-bombo-design.md`

## Objetivo

Dar a las pegatinas de evento una **página propia de álbum** (`/events/album`) y
rediseñar las **12 pegatinas** como ilustraciones SVG originales con aspecto de
pegatina troquelada (borde blanco, sombra suave y dibujo a mano), siguiendo la
referencia visual aportada por la usuaria.

## Decisiones de la usuaria

- Álbum en **página propia** `/events/album`, como el álbum de cartas.
- **Rediseñar las 12** pegatinas del catálogo como ilustraciones.
- Las no obtenidas se ven como **silueta gris con «?»**, sin desvelar el dibujo.
- Las pegatinas son **solo de colección**; no se colocan en las estanterías.

## Contenido (12 pegatinas)

| Evento | Pegatina | Dibujo |
|---|---|---|
| Feria del libro | `feria_pluma` | Pluma y tintero |
| Feria del libro | `feria_lampara` | Lámpara de latón |
| Noche de dragones | `dragones_escama` | Huevo de dragón con escamas |
| Noche de dragones | `dragones_alas` | Dragón con alas desplegadas |
| Cuentos de invierno | `invierno_copo` | Bola de nieve con copo |
| Cuentos de invierno | `invierno_campana` | Campana con lazo |
| Cartas de enamorados | `enamorados_corazon` | Corazón sellado |
| Cartas de enamorados | `enamorados_rosa` | Rosa entre páginas |
| La noche de las brujas | `brujas_calavera` | Calavera con vela |
| La noche de las brujas | `brujas_pocion` | Poción de tinta |
| Feria de las flores | `flores_ramo` | Ramo prensado |
| Feria de las flores | `flores_planta` | Planta en maceta |

## Componente `EventSticker`

- Props: `itemKey` (clave del catálogo), `size` (px, por defecto 96), `locked`.
- Cada clave mapea a un `<g>` SVG dentro de un `viewBox` común `0 0 120 120`.
- **Troquel:** silueta blanca orgánica detrás del dibujo (variantes de blob) y
  sombra suave con `filter: drop-shadow`, para el aspecto de pegatina real.
- **Bloqueada:** silueta gris con «?», sin renderizar la ilustración.
- Estilo de dibujo: trazo redondeado, ligeramente irregular, paleta de tokens
  (`crema`, `pergamino`, `madera`, `dorado`, `bosque`, `oliva`, `salvia`,
  `terracota`, `danger-strong`, `orquidea`, `magic`, `gold`, `tinta`, `moon`).
  Sin colores nuevos.
- Si la clave no existe, degrada a un emblema de estrella (sin romper).

## Álbum (`Events/Album`)

- Cabecera: título «Álbum de pegatinas», progreso «X de 12» y frasco de tinta
  (`card-ink`).
- Cuerpo: panel tipo página de álbum (`.event-album`, fondo `pergamino` con
  textura sutil de papel), rejilla de casillas troqueladas.
- Obtenida: sticker a color + nombre en `tinta`.
- Bloqueada: silueta gris con «?» y texto «Aún no la tienes» en `mist`.
- Responsive: 2 columnas a 360 px, 3 en tablet, 4 en escritorio.
- Accesibilidad: cada casilla con `aria-label` (nombre o «bloqueada»), contador
  con `role="progressbar"`.

## Datos y rutas

- `EventService::stickerCatalog()`: recorre `config('events.catalog')` y devuelve
  las 12 pegatinas en orden con `key`, `name`, `emblem`, `cost`, `event_key`,
  `event_name`, `theme`.
- `EventService::ownedCollectibleKeys(User)`: claves obtenidas por la usuaria.
- `EventController::album(Request, EventService)`: props `stickers` (con `owned`),
  `progress` (`owned`/`total`) e `ink`.
- Ruta `GET /events/album` → `events.album` dentro del grupo `auth` + `nickname`.
- La tienda y el cobro no cambian: las pegatinas siguen comprándose en la tienda
  del evento activo con tinta.

## Acceso

- Botón **«Ver el álbum de pegatinas»** en la página Eventos, bajo la cabecera o
  junto a la sección de colección (clave `ui.events_open_album`).
- La tienda y «Mi colección» de Eventos pasan a usar `EventSticker` por `itemKey`.

## Textos nuevos (8 idiomas)

`ui.events_stickers_album`, `ui.events_stickers_progress`,
`ui.events_open_album`, `ui.events_locked`.

## Pruebas

- `EventAlbumTest`: la página muestra 12 pegatinas, marca las obtenidas, cuenta
  el progreso y no mezcla coleccionables de otras usuarias.
- `EventSticker.test.js`: renderiza las 12 claves, la versión bloqueada no
  muestra la ilustración y una clave desconocida no rompe.
- Verificación final: `php artisan test`, `npm run test`, `pint --test`,
  `npm run build`.

## Fuera de alcance

- Pegatinas colocables en estanterías.
- Nuevos coleccionables fuera de las 12 actuales.
- Imágenes raster externas.
