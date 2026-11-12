# Álbum de colección — módulo aparte (rediseño estilo álbum digital)

**Fecha:** 2026-11-10  
**Estado:** Diseño aprobado por la usuaria, pendiente de revisión del spec  
**Alcance:** nuevo módulo de álbum independiente que muestra las cartas y los stickers de máquina del usuario, con diseño estilo álbum digital de stickers (referencia visual: `lbum_digital_de_stickers.html`).

## 1. Objetivo

Reemplazar el álbum actual de `/cards/album` por una página propia de álbum (`/album`) que no dependa del módulo Máquina expendedora. El álbum muestra todas las cartas desbloqueadas y los stickers de máquina obtenidos por la usuaria, con diseño de álbum de estampas: barra de progreso, filtros por categoría, tarjetas tipo sticker con borde troquelado y detalle en modal.

## 2. Decisiones de diseño

| Decisión | Elección |
|---|---|
| Ruta | `/album` (título de página "Tu álbum") |
| Navegación | Entrada propia en barra lateral y menú móvil, separada de Máquina expendedora y Eventos |
| Contenido | 20 cartas (de `config/cards.php`) + 20 stickers de máquina (de `stickerArt.js`) |
| Estado de origen | Cartas desde `user_cards`; stickers desde `user_collectibles` (`kind='sticker'`, `event_key='maquina'`, `source='machine'`) |
| Aislamiento | Solo objetos del usuario autenticado |
| Estilo visual | Referencia HTML de álbum digital (anillas, progreso, tarjetas troqueladas, detalle modal), adaptado a tokens existentes |
| Colores | Solo tokens existentes (`--color-crema`, `--color-tinta`, `--color-dorado`, `--color-page`, `--color-surface`, `--color-edge`, `--color-ink`, `--color-ink-muted`, `--color-danger`, `--color-danger-strong`, etc.) |
| Sobre sorpresa | Botón opcional en header para abrir sobre y desbloquear uno al azar no obtenido (fase 1, puede activarse/desactivarse) |

## 3. Arquitectura

### Backend
- Controlador `AlbumController` con método `index`:
  - Consulta cartas propias: `UserCard` del usuario autenticado, join con metadata de carta (clave, personaje, familia, emblema, moneda).
  - Consulta stickers de máquina propios: `UserCollectible` donde `user_id`, `kind='sticker'`, `event_key='maquina'`, `source='machine'`.
  - Une ambos en una lista normalizada con campos: `type` (`card` / `sticker`), `key`, `name`, `category`, `rarity`, `unlocked` (bool), `copies` (solo cartas), `acquired_at`, `image` (SVG o emblema), `color`.
  - Calcula progreso: total objetos (20 cartas + 20 stickers = 40), obtenidos, desglose por rareza.
- No crea nuevas tablas; reusa `user_cards` y `user_collectibles`.
- Filtra siempre por `user_id` del autenticado (política de autorización).

### Frontend
- Página Inertia `resources/js/Pages/Album/Index.vue`.
- Componentes nuevos:
  - `resources/js/Components/AlbumSlot.vue`: tarjeta tipo sticker para un objeto (carta o sticker), con borde troquelado, nombre, categoría, rareza, estado bloqueado/bloqueado.
  - `resources/js/Components/AlbumCard.vue`: variante para cartas (muestra emblema, monograma, personaje, familia).
  - `resources/js/Components/AlbumProgress.vue`: barra de progreso con desglose Común/Rara/Épica.
  - `resources/js/Components/AlbumFilter.vue`: filtros Todas / Cartas / Stickers.
  - `resources/js/Components/AlbumDetailModal.vue`: detalle en modal con ilustración grande y datos.
- `LibrarySticker.vue` se reutiliza para los stickers (ya tiene silueta propia y arte).
- No se usa `v-html` con SVG de cartas; las cartas usan `CardEmblem` + datos de texto.

## 4. Diseño visual

- Contenedor tipo libreta: fondo `page`/`surface`, borde `edge`, radio grande.
- Barra de progreso superior con contador "N de M" y barra de color `accent`.
- Filtros por categoría: Todas, Cartas, Stickers (activable/desactivable).
- Rejilla responsiva: 2 columnas a 360 px, 3 en tablet, 4-5 en escritorio.
- Tarjeta desbloqueada: borde blanco troquelado, ilustración, nombre, categoría, etiqueta de rareza.
- Tarjeta bloqueada: silueta en `surface-raised`, candado, nombre "???", sin clic.
- Efecto hover: `translateY(-6px) scale(1.03) rotate(-1deg)`.
- Modal de detalle: fondo `surface`, ilustración grande, nombre, categoría, rareza con color, número, fecha de obtención, botón "Entendido".

## 5. Accesibilidad

- Tarjetas con `role="button"`, `tabindex="0"`, `aria-label` con nombre del objeto.
- Filtros con `aria-pressed`.
- Progreso con `role="progressbar"`, `aria-valuenow/min/max`.
- Cierre de modal con Escape.

## 6. Documentación

- Actualizar `docs/DISENO.md`:
  - Nuevo apartado "Álbum de colección (`Album/Index`)".
  - Registro de cambios con fecha 2026-11-10.
- No se añaden colores nuevos.

## 7. Pruebas

- `tests/Feature/AlbumTest.php`:
  - muestra solo objetos del usuario autenticado.
  - progreso coincide con cartas + stickers de máquina obtenidos.
  - filtro por categoría funciona.
  - detalle de objeto no obtenido no se expone (no hay dato sensible).
  - no mezcla datos de otras cuentas.
- `resources/js/Components/__tests__/AlbumSlot.test.js` (o similar):
  - renderiza carta y sticker.
  - estado bloqueado muestra candado y "???".
  - detalle modal solo para obtenidos.
- Ejecutar `npm run test -- --run`, `npm run build`, `php artisan test`.

## 8. Fuera de alcance (fase 1)

- Sobre sorpresa con animación y confetti (se deja como botón desactivado o para fase 2).
- Integración con eventos de stickers de evento (esos se quedan en `/events/album`).
- Cambios en la máquina expendedora (`Cards/Index`).
