# Noche arcana — rediseño de color de la aplicación

**Fecha:** 2026-10-26
**Estado:** aprobado por la usuaria (diseño); pendiente de revisión del spec
**Ámbito:** aplicación completa excepto el mueble de las estanterías

## 1. Objetivo

Sustituir la paleta clara de biblioteca antigua por una noche mágica de violetas profundos, oro y turquesa, manteniendo intacto el mueble de madera de las estanterías y todo lo que se apoya en él (balda, espaldar, portadas y lomos).

La sensación buscada es «entrar en otro mundo»: una sala nocturna donde las estanterías de madera son lo único cálido e iluminado.

## 2. Restricciones

- **El mueble no cambia.** `ShelfBoard`, `Shelf.vue` en su parte de libros, `BookCard`, `BookSpine` y las reglas de `.shelf-case`, `.shelf-board*`, `.book-glow` de `textures.css` se quedan como están. `EmptyShelf` conserva borde, estructura y tamaño, pero su texto pasa de `bosque` a `crema` (ambos de la paleta del mueble) para que se lea sobre el espaldar oscuro.
- **No se redefinen los tokens antiguos:** `bosque`, `salvia`, `oliva`, `oliva-hover`, `madera`, `madera-clara`, `pergamino`, `crema`, `tinta`, `terracota` y `dorado` conservan sus valores. Los libros y el mueble siguen leyéndolos.
- La paleta nueva son tokens independientes; ningún componente del mueble los usa.
- Se mantienen tipografías, radios, sombras y estructura. Solo cambia color y se añaden detalles de fondo.
- Sin imágenes bitmap nuevas: degradados y estrellas con CSS.
- Accesibilidad AA y respeto a `prefers-reduced-motion`.
- La interfaz sigue siendo multilingüe; no se añaden cadenas visibles.

## 3. Tokens nuevos

Añadir en `resources/css/app.css` dentro de `@theme`:

| Token | Valor | Uso |
|---|---|---|
| `--color-night` | `#12101F` | Fondo general |
| `--color-night-raised` | `#1A1730` | Navegación y superficies elevadas |
| `--color-panel` | `#211D3A` | Tarjetas, inputs, modales |
| `--color-panel-raised` | `#2B2550` | Hover de panel, elementos secundarios |
| `--color-line` | `#3A3363` | Bordes y separadores |
| `--color-moon` | `#F1ECFF` | Texto principal |
| `--color-mist` | `#B7AED6` | Texto secundario y placeholders |
| `--color-arcane` | `#7C5CE0` | Botón principal, pestaña activa |
| `--color-arcane-strong` | `#6A4AC9` | Hover/active del botón principal |
| `--color-magic` | `#74D8C9` | Enlaces, foco, acentos |
| `--color-magic-strong` | `#5FC7B8` | Hover de acentos |
| `--color-gold` | `#E8C887` | Títulos, logo, estrellas de valoración |
| `--color-danger` | `#F2A0B0` | Errores y texto de peligro |
| `--color-danger-strong` | `#A8455C` | Fondo de botones destructivos |
| `--color-success` | `#8FE3B8` | Mensajes de éxito |

Base (`@layer base`):

- `body`: `bg-night text-moon`.
- `h1, h2, h3`: `text-gold` con Cormorant.
- `:focus-visible`: anillo `magic` con offset `night`.

## 4. Fondo mágico

Clase `.arcane-sky` en `textures.css` (sustituye a la antigua textura de papel en `AppShell` y `GuestLayout`), con el componente decorativo `ArcaneStars`:

- Base `night`.
- Resplandor radial violeta arriba a la izquierda y turquesa abajo a la derecha, ambos con `color-mix` muy bajo.
- Estrellas tenues en cuatro capas fijas (`ArcaneStars`): tamaños de 0.7 a 1.8 px, opacidades 0.55-0.85 y mezcla de `moon` con algunos acentos `gold`.
- Deriva diagonal lenta (70-150 s por ciclo) y parpadeo suave (6-14 s), desfasados por capa; solo se animan `transform` y `opacity`, sin canvas ni imágenes.
- La capa decorativa va detrás del contenido (`z-index: -1`), no captura clics y no reduce la legibilidad.
- Con `prefers-reduced-motion: reduce` las animaciones se detienen y las estrellas quedan fijas.

## 5. Mapa de componentes

### Navegación y estructura

- `AppShell.vue`: `.arcane-sky`, `ArcaneStars`, sidebar/header/nav inferior en `night-raised`, logo `gold`, enlaces `mist` → `moon`, activo con borde `magic` y fondo `panel`.
- `GuestLayout.vue`: `.arcane-sky`, `ArcaneStars`, logo en `gold`, tarjeta en `panel` con borde `line`.

### Controles base

- `PrimaryButton.vue`: `arcane` / `arcane-strong`, texto `moon`, foco `magic`.
- `SecondaryButton.vue`: `panel` con borde `line`, texto `moon`, hover `panel-raised`.
- `DangerButton.vue`: `danger-strong` con texto `moon`, hover `danger-strong/85`, foco `magic`.
- `TextInput.vue`, `InputLabel.vue`, `Checkbox.vue`, `LanguageSelect.vue`: fondo `panel`, borde `line`, texto `moon`, placeholder/label `mist`, foco `magic`.
- `InputError.vue`: `danger`.
- `Modal.vue`: velo `night/80`, panel `panel`, borde `line`.
- `Dropdown.vue` / `DropdownLink.vue`: `panel`, borde `line`, hover `panel-raised`.
- `NavLink.vue` / `ResponsiveNavLink.vue`: estados `magic`/`moon` en lugar de índigo y grises.
- `RatingStars.vue`: estrellas en `gold`.

### Pantallas

- `Library/Index.vue`: botón «Añadir» en `arcane`; encabezados en `gold`; contenedores de estantería sin fondo (el mueble se ve sobre la noche).
- `Shelf.vue`: título en `gold`, contador en `mist`, botón de modo con borde `line`, texto `moon`, hover `panel-raised`. **Los libros y la balda no se tocan.**
- `Books/Create.vue` y `Books/Edit.vue`: pestañas activas `arcane`, inactivas `panel`; inputs y resultados en panel oscuro; estados de éxito/error con `success`/`danger`.
- `Settings/Index.vue`: tarjetas de sección en `panel`, inputs oscuros, botones `arcane`, mensajes de éxito en `success`, textos de ayuda en `mist`.
- `Profile/Edit.vue` y sus parciales: adaptar los grises de Breeze a `moon`/`mist`/`panel`/`line`; no dejar fondos blancos ni textos oscuros sobre noche.
- Páginas de acceso (`Login`, `Register`, `ForgotPassword`, `ResetPassword`, `VerifyEmail`, `ConfirmPassword`): títulos `gold`, textos `mist`, enlaces `magic`, tarjeta del `GuestLayout`.
- `BarcodeScanner.vue`: visor `night` en lugar de `tinta`.

### Fuera de alcance

- `Welcome.vue` y `Dashboard.vue` no se renderizan (la raíz exige autenticación y `/dashboard` redirige). No se tocan.
- No se cambian textos, rutas, lógica, traducciones ni estructura de datos.

## 6. Detalles mágicos sutiles

- Resplandor suave en hover del botón principal y de las tarjetas enfocadas.
- Bordes interiores de 1 px más claros en paneles para dar profundidad.
- Estrellas del fondo apenas perceptibles; no deben reducir la legibilidad.
- Todas las transiciones ≤ 200 ms; se respeta `prefers-reduced-motion`.

## 7. Accesibilidad

- `moon` sobre `night` y sobre `panel` supera 12:1.
- `mist` sobre `panel` ≥ 5:1.
- `gold` sobre `night` ≥ 7:1.
- Texto de botón `moon` sobre `arcane` ≥ 4.5:1; verificar en implementación.
- Foco visible en todos los controles con anillo `magic`.
- No se depende solo del color para estados: se mantienen textos y etiquetas.

## 8. Verificación

1. `php artisan test` (sin cambios de backend, debe seguir verde).
2. `npm run test` y `npm run build`.
3. Revisión visual a 360 px y escritorio de: acceso, biblioteca, alta/edición, ajustes y perfil.
4. Captura antes/después de una estantería para confirmar que madera, espaldar, balda, portadas y lomos no cambian.
5. Comprobar que las estrellas se mueven con animación y que se detienen con `prefers-reduced-motion: reduce`.
6. Comprobar foco con teclado y contraste.
7. Actualizar `docs/DISENO.md` (paleta, fondo, componentes) y su registro de cambios.

## 9. Riesgos

- Que algún componente Breeze conserve clases grises/blancas y rompa la noche; se revisará con búsqueda de `gray`, `white`, `indigo`, `bg-crema` y `text-tinta` fuera del mueble.
- Que el contraste del violeta con texto claro no llegue a AA; se ajustará el tono antes de cerrar.
- Que las estrellas del fondo distraigan; se bajará la opacidad o se limitarán a las esquinas.
