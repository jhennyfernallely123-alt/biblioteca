# Diseño: tema claro/oscuro con tokens semánticos

**Fecha:** 2026-11-08 · **Estado:** aprobado por la usuaria
**Relacionado:** `2026-11-08-album-replica-design.md`, `docs/DISENO.md`

## Objetivo

Convertir la paleta pastel de la referencia `album_digital_de_stickers.html` en el
**tema claro por defecto** de toda la aplicación, conservar la paleta actual como
**tema oscuro** y permitir cambiar entre ambos desde Ajustes, con la preferencia
guardada en la base de datos.

La implementación pasa por **tokens semánticos**: los componentes dejan de nombrar
colores concretos (`night`, `panel`, `gold`…) y usan roles (`page`, `surface`,
`ink`, `accent`…). Así cada tema solo redefine los valores.

## Decisiones de la usuaria

- Tema **claro pastel por defecto**; el tema actual pasa a **modo oscuro**.
- **Tipografías intactas**: Cormorant Garamond para títulos e Inter para texto.
- Selector en **Ajustes**, con la preferencia guardada en la **base de datos**.
- Enfoque de **tokens semánticos** con migración completa, no un simple remapeo.
- El **álbum de pegatinas** será siempre papel claro, en ambos temas.

## Tokens semánticos

Todos en `resources/css/app.css` (`@theme`). Los valores claros son el valor por
defecto; el tema oscuro los sobrescribe con `:root[data-theme='dark']`.

| Token | Claro | Oscuro | Uso |
|---|---|---|---|
| `page` | `#FDF7F0` | `#12101F` | Fondo de página |
| `surface` | `#FFFFFF` | `#211D3A` | Tarjetas, paneles, inputs |
| `surface-raised` | `#FFF8F1` | `#1A1730` | Navegación, menús, hover suave |
| `surface-sunken` | `#F6EDE3` | `#191632` | Zonas hundidas, pistas, previsualizaciones |
| `ink` | `#3B3630` | `#F1ECFF` | Texto principal |
| `ink-muted` | `#7A7068` | `#B7AED6` | Texto secundario |
| `ink-soft` | `#9A9088` | `#8C82B8` | Texto terciario, ayudas |
| `heading` | `#3B3630` | `#E8C887` | Títulos `h1`–`h3` |
| `on-accent` | `#FFFFFF` | `#FFFFFF` | Texto sobre fondos saturados (botones) |
| `edge` | `#E8D5C4` | `#3A3363` | Bordes y separadores |
| `edge-strong` | `#D6BEA8` | `#4A4278` | Bordes destacados |
| `accent` | `#DB2777` | `#E8C887` | Acento principal (rosa/dorado) |
| `accent-strong` | `#BE185D` | `#D4B271` | Hover del acento |
| `accent-soft` | `#FDE2E4` | `#3A3350` | Fondos de insignias |
| `secondary` | `#0F766E` | `#74D8C9` | Acento secundario (AA sobre blanco) |
| `secondary-strong` | `#115E59` | `#5FC7B8` | Hover secundario |
| `tertiary` | `#7C3AED` | `#7C5CE0` | Acento terciario |
| `tertiary-strong` | `#6D28D9` | `#6A4AC9` | Hover terciario |
| `danger` | `#E11D48` | `#F2A0B0` | Error |
| `danger-strong` | `#BE123C` | `#A8455C` | Error fuerte |
| `success` | `#047857` | `#8FE3B8` | Éxito (AA sobre blanco) |
| `warning` | `#B45309` | `#E8C887` | Aviso (AA sobre blanco) |
| `ring` | `#7C3AED` | `#74D8C9` | Anillo de foco |
| `scrim` | `#1C1A24` | `#0A0814` | Velos de modales (siempre oscuro) |

### Tokens físicos que no cambian

`bosque`, `oliva`, `salvia`, `madera`, `madera-clara`, `pergamino`, `crema`,
`tinta`, `terracota` y `dorado` representan objetos físicos (lomos, madera, papel,
latón) y conservan sus valores actuales en ambos temas.

### Sombras

`--shadow-soft`, `--shadow-card`, `--shadow-cover` y `--shadow-modal` se redefinen
con `color-mix(in srgb, var(--color-scrim) N%, transparent)` para que funcionen en
los dos temas sin valores duplicados.

## Migración de usos

Reemplazo mecánico en los 55 archivos Vue y en `textures.css`:

| Antiguo | Nuevo |
|---|---|
| `moon` (texto) | `ink` |
| `mist` | `ink-muted` |
| `gold` | `accent` |
| `magic` / `magic-strong` | `secondary` / `secondary-strong` |
| `arcane` / `arcane-strong` | `tertiary` / `tertiary-strong` |
| `orquidea` | `tertiary` |
| `night` (fondo de página) | `page` |
| `night-raised` | `surface-raised` |
| `panel` | `surface` |
| `panel-raised` | `surface-raised` |
| `line` | `edge` |
| `ring-offset-night` | `ring-offset-page` |
| `text-night` sobre acento | `text-on-accent` |

Casos que se revisan a mano, porque el token viejo estaba sobrecargado:

- **Velos de modales**: `Modal`, `MagicWelcome`, `ReadingLightbox` pasan a
  `bg-scrim/70-80`.
- **Insignias sobre portadas**: `BookCard` y `BookSpine` usan `bg-scrim/85`.
- **Fondo de vídeo**: `BarcodeScanner` usa `bg-scrim`.
- **Pistas de progreso**: `CardAlbum` usa `bg-edge`/`surface-sunken`.
- **Etiqueta vertical** de `DecorationCatalog` sobre arte: `bg-scrim/60`.
- **Botones de acento** (`PrimaryButton`, insignia de `Shelf`): `text-on-accent`.
- **`EmptyShelf`** y los lomos conservan `text-crema`: están sobre madera oscura,
  que no cambia entre temas.

La máquina de cartas 3D y las decoraciones conservan sus materiales de madera y
latón; su escenario sigue siendo oscuro en ambos temas y se documenta así.

## Aplicación del tema

- Migración `users.theme` (`string`, por defecto `light`), incluida en `Fillable` y
  con valor por defecto en el modelo.
- Ruta `PATCH /settings/theme` → `settings.theme`, dentro de `auth` + `nickname`.
  Validación `in:light,dark`; guarda y responde `back()`.
- `Settings/Index`: sección «Apariencia» con dos tarjetas seleccionables (Claro /
  Oscuro). Al pulsar, se aplica de inmediato en el DOM y se guarda con
  `router.patch(..., { preserveScroll: true })`. Errores en `text-danger`.
- `resources/views/app.blade.php`: `<html data-theme="{{ auth()->user()?->theme ?? 'light' }}">`
  para que el primer pintado ya salga con el tema correcto.
- `ThemeSync.vue`: componente sin marcado que observa
  `page.props.auth.user?.theme` y escribe `document.documentElement.dataset.theme`
  (por defecto `light`). Se incluye en `AuthenticatedLayout` y `GuestLayout` para
  que login/logout y navegación Inertia no dejen el tema desincronizado.
- CSS: `color-scheme: light` / `dark` por tema para controles nativos y barras de
  desplazamiento.

## Textos nuevos (8 idiomas)

`ui.appearance_heading`, `ui.appearance_help`, `ui.theme_light`,
`ui.theme_dark`, `ui.theme_light_help`, `ui.theme_dark_help`, `ui.theme_error`.

## Pruebas

- `palette.test.js` reescrito: valores claros por defecto, valores oscuros en
  `[data-theme='dark']`, tokens físicos intactos y sombras con `scrim`.
- `theme.test.js`: escanea `resources/css` y `resources/js` y falla si reaparecen
  tokens antiguos (`night`, `panel`, `moon`, `mist`, `gold`, `magic`, `arcane`,
  `line`, `orquidea`) o hex sueltos en componentes (se excluyen `CardMachine.vue`,
  `Welcome.vue` y `app.js`, que conservan colores propios).
- `SettingsThemeTest`: requiere sesión, guarda `light`/`dark`, rechaza otros
  valores y no altera a otras usuarias.
- `ThemeSync.test.js`: aplica el tema del usuario y cae a `light` sin usuario.
- Verificación completa: `php artisan test`, `npm run test`,
  `vendor\bin\pint --test`, `npm run build` y revisión visual de cada pantalla en
  ambos temas a 360 px y escritorio.

## Riesgos

- La migración toca ~700 usos; el test de tokens evita regresiones, pero hace
  falta una pasada visual por pantalla en los dos temas.
- Algunos degradados y texturas muy oscuras pueden pedir ajustes puntuales; se
  corrigen dentro de esta fase, sin cambiar estructura.

## Fuera de alcance

- Cambiar tipografías.
- Tematizar los materiales 3D de la máquina de cartas.
- Un tercer tema o temas por estación.
- Preferencia de tema para invitados (siempre claro).
