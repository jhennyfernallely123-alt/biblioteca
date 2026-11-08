# Diseño: álbum de pegatinas réplica de la referencia

**Fecha:** 2026-11-08 · **Estado:** aprobado por la usuaria
**Relacionado:** `2026-11-08-tema-semantico-design.md`,
`2026-11-05-album-stickers-design.md`, `docs/DISENO.md`

## Objetivo

Rediseñar la página `/events/album` para que se vea como el álbum digital de la
referencia `album_digital_de_stickers.html`: cuaderno claro con anillas, tarjeta
de progreso, filtros por evento, tarjetas de pegatina con número y rareza, y ficha
de detalle al tocar una pegatina obtenida.

## Decisiones de la usuaria

- Réplica visual de la **sección del álbum** de la referencia.
- Sin sobre de pegatinas ni confeti: las pegatinas se siguen comprando en Eventos.
- El álbum es **siempre papel claro**, también con el tema oscuro.
- Las pegatinas no obtenidas no desvelan el dibujo: candado, «???» y borde
  discontinuo.

## Estructura de la página

1. **Cabecera**
   - Título «Álbum de pegatinas» y botón «Volver a Eventos» (reutiliza
     `ui.nav_events`).
   - Sin frasco de tinta: la economía se gestiona en Eventos.

2. **Tarjeta de progreso**
   - Tarjeta blanca redondeada con sombra suave.
   - Etiqueta «Progreso de la colección», título «Tu álbum de estampas» y contador
     grande «X / 12» con la palabra «Completado».
   - Barra degradada rosa → rosa fuerte → ámbar; `role="progressbar"` con
     `aria-valuemin/max/now`.
   - Tira de tres contadores: comunes, raras y épicas obtenidas.

3. **Filtros**
   - Píldoras: «Todas» + los 6 eventos del catálogo.
   - Filtrado en el cliente por `event_key`; estado activo en slate oscuro.
   - Se conserva `aria-pressed` y navegación con teclado.

4. **Cuaderno**
   - Panel crema (`#FAF6EE`), borde grueso (`#E5DDCB`) y radio grande.
   - Ocho anillas metálicas sobre el borde superior, solo desde 640 px.
   - Encabezado de página con título del álbum y pista «Toca una pegatina para ver
     sus detalles».

5. **Rejilla de pegatinas**
   - 2 columnas a 360 px, 3 en tablet, 4 en escritorio.
   - Tarjeta obtenida: fondo blanco, borde ámbar, sombra, esquina doblada sutil,
     número `#01`, insignia de rareza con color, dibujo de 112 px, nombre y evento.
   - Tarjeta bloqueada: fondo crema translúcido, borde discontinuo, icono de
     candado, «Bloqueado», nombre «???», sin clic.
   - Hover: elevación, escala y giro leve de 1°; respeta
     `prefers-reduced-motion`.

6. **Ficha de detalle** (solo obtenidas)
   - Modal centrado con el dibujo grande, píldora del evento, nombre, descripción
     corta y caja con rareza, número y fecha de obtención.
   - Botón «Entendido» y cierre con Escape, clic en el velo y foco atrapado
     (reutiliza `Modal`).
   - Fecha formateada en el navegador con `Intl.DateTimeFormat` según el idioma.

## Datos

- `config/events.php`: cada pegatina recibe `rarity` explícita.
  - Comunes (4): `feria_pluma`, `invierno_copo`, `enamorados_corazon`,
    `flores_ramo`.
  - Raras (6): `dragones_escama`, `dragones_alas`, `invierno_campana`,
    `brujas_calavera`, `brujas_pocion`, `flores_planta`.
  - Épicas (2): `feria_lampara`, `enamorados_rosa`.
- `EventService::stickerCatalog()` añade `rarity` al array devuelto.
- `EventController::album` combina el catálogo con `user_collectibles` y devuelve:
  - `stickers`: `key`, `event_key`, `theme`, `cost`, `rarity`, `owned`,
    `acquired_at` (ISO 8601 o `null`).
  - `progress`: `owned`, `total` y `by_rarity` con `{comun, rara, epica}`.
- Las descripciones y etiquetas salen del catálogo de idiomas; no se guardan en
  base de datos.
- Sin cambios en la tienda, precios ni economía.

## Componentes

- `Pages/Events/Album.vue`: estado de filtro, cálculo de progreso y apertura de la
  ficha. Sin lógica de red.
- `EventSticker.vue`: se mantiene tal cual (dibujo, troquel y bloqueada).
- Clases nuevas en `textures.css`: `sticker-album*` para cuaderno, anillas,
  tarjetas, insignias y ficha. Los tokens pastel del álbum se definen una sola vez
  y no dependen del tema (papel claro siempre).

## Textos nuevos (8 idiomas)

`ui.sticker_progress_label`, `ui.sticker_progress_title`, `ui.sticker_completed`,
`ui.sticker_all`, `ui.sticker_locked`, `ui.sticker_number`, `ui.sticker_rarity`,
`ui.sticker_acquired`, `ui.sticker_got_it`, `ui.sticker_hint`,
`ui.sticker_rarity_comun`, `ui.sticker_rarity_rara`, `ui.sticker_rarity_epica` y
`ui.sticker_desc_<clave>` para las 12 pegatinas. Se reutilizan
`ui.events_stickers_album` y `ui.events_stickers_progress` donde encajen.

## Pruebas

- `EventAlbumTest`: 12 pegatinas con rareza, progreso y desglose correctos,
  `acquired_at` presente solo en las obtenidas y aislamiento entre usuarias.
- `Album.test.js`: rejilla de 12, bloqueadas con «???» y sin dibujo, filtro por
  evento, apertura y cierre de la ficha con rareza, número y fecha.
- `EventSticker.test.js` se mantiene verde.
- Verificación completa: `php artisan test`, `npm run test`,
  `vendor\bin\pint --test`, `npm run build`, y revisión visual a 360 px y
  escritorio en ambos temas.

## Documentación

- `docs/DISENO.md`: sustituye la sección actual del álbum por la réplica
  (cuaderno, anillas, progreso, filtros, tarjetas, ficha) y añade fila de cambios
  fechada 2026-11-08.

## Fuera de alcance

- Sobres, confeti y nuevas fuentes de pegatinas.
- Cambiar precios o la economía de tinta.
- Reordenar pegatinas o añadir nuevas.
- Editar el dibujo de las 12 pegatinas.
