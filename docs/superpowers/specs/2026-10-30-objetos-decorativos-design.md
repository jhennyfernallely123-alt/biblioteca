# Diseño: objetos decorativos en las estanterías

**Fecha:** 2026-10-30
**Estado:** aprobado por la usuaria (enfoque A: objetos anclados a un libro)

## Objetivo

Poder decorar las estanterías con objetos (tazas, flores, velas, etc.) colocados entre los libros, como en una biblioteca real. Los objetos se añaden desde Ajustes, se pueden repetir con distintos colores, se arrastran dentro de una estantería o a otra, y se pueden quitar de uno en uno o todos a la vez.

## Decisiones de la usuaria

- Catálogo amplio de **15 objetos**.
- **Varias copias** del mismo objeto, cada una con su **color** (6 colores de la paleta existente).
- Los objetos se **intercalan con los libros** en una sola fila ordenada; al soltarlos entre dos libros, los libros se apartan.
- En Ajustes se añaden **de uno en uno**: se elige color, se pulsa «Añadir» y el objeto aparece al final de la **primera estantería**.
- «Quitar todas» vacía las decoraciones de la biblioteca.
- Quitar uno: **×** al pasar el ratón (escritorio) y botón de quitar en la lista de Ajustes (también en móvil).

## Fuera de alcance

- Cambiar el color de un objeto ya colocado (se quita y se vuelve a añadir).
- Redimensionar, rotar o colocar libremente sobre la balda (sin coordenadas ni capas).
- Subir imágenes propias.
- Activar decoración por estantería o desactivar la función por completo.
- Objetos dentro de la ficha del libro o en la búsqueda.

## Modelo de datos

Tabla `decorations` (migración `2026_10_30_000003_create_decorations_table.php`):

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | FK `users` | `cascadeOnDelete` |
| `shelf_id` | FK `shelves` | `cascadeOnDelete` |
| `kind` | string(32) | uno de los 15 tipos |
| `color` | string(16) | una de las 6 claves |
| `after_book_id` | FK `books`, nullable | `nullOnDelete`; nulo = principio de la balda |
| `sort` | unsignedInteger, default 0 | orden dentro del hueco |
| `timestamps` | | |

Índice por `shelf_id` + `after_book_id` + `sort`.

Modelo `App\Models\Decoration`:

- `$fillable = ['kind', 'color', 'after_book_id', 'sort']`.
- Constantes `KINDS` y `COLORS` (listas cerradas, usadas por la validación y por las pruebas).
- Relaciones `user()`, `shelf()`, `afterBook()`.
- `Shelf::decorations()`: `hasMany(Decoration::class)->orderBy('sort')->orderBy('id')`.

### Reglas de orden

El orden de una balda se compone así (`mergeShelfItems`):

1. Objetos con `after_book_id` nulo, por `sort`.
2. Para cada libro en su orden: el libro y después sus objetos anclados, por `sort`.
3. Objetos anclados a un libro que **no está en esa estantería**: al final, por `sort`.

Consecuencias:

- Un objeto sin ancla queda **de primero**, delante del primer libro.
- Mover un libro de estantería no reescribe nada: si su ancla desaparece de la balda, el objeto se dibuja al final y vuelve a su hueco si el libro regresa.
- Borrar un libro deja sus objetos sin ancla (`nullOnDelete`), que pasan al principio de la balda.

## API

Rutas dentro del grupo `auth` + `nickname`:

| Método | Ruta | Acción |
|---|---|---|
| `POST` | `/decorations` | Añadir al final de la primera estantería |
| `PATCH` | `/decorations/{decoration}` | Mover y/o reanclar |
| `DELETE` | `/decorations/{decoration}` | Quitar uno |
| `DELETE` | `/decorations` | Quitar todos los del usuario |

`DecorationController` (delgado) + `DecorationPolicy` (`update`, `delete` por dueño).

**Crear** — `{kind, color}`:

- Valida `kind` en `Decoration::KINDS` y `color` en `Decoration::COLORS`.
- Estantería destino: la del usuario con menor `position` (desempate por `id`).
- Ancla: el último libro de esa estantería (o nulo si está vacía); `sort` = número de objetos ya anclados ahí.
- Responde `back()->with('success', __('ui.decoration_added', ['name' => …, 'shelf' => …]))`.

**Mover** — `{shelf_id, after_book_id, sort}`:

- `shelf_id` debe ser del usuario; `after_book_id` nulo o un libro del usuario **que esté en `shelf_id`**.
- Actualiza `shelf_id`, `after_book_id` y `sort` (recortado al rango válido) y renumera los objetos del hueco de destino y del hueco de origen.
- Responde `back()->with('success', __('ui.decoration_moved', ['name' => …, 'shelf' => …]))`.

**Quitar uno**: borra y responde con `ui.decoration_removed`.
**Quitar todos**: `$request->user()->decorations()->delete()` y responde con `ui.decorations_cleared`.

## Servidor → vistas

- `LibraryController@index`: `->with(['books', 'decorations'])`; cada `Shelf` incluye `decorations`.
- `SettingsController@index`: añade `decorations` del usuario con `shelf:id,name` (para la lista «Tus objetos»).

## Interfaz

### Ajustes → sección «Decoración»

Situada después de «Estanterías».

- Título `ui.decorations_heading` y ayuda `ui.decorations_help`.
- Catálogo en rejilla (`grid gap-3 sm:grid-cols-2 lg:grid-cols-3`), una tarjeta por objeto:
  - Dibujo `DecorationArt` sobre fondo `panel-raised` (alto ~96 px).
  - Nombre traducido.
  - Seis muestras de color redondas (`h-6 w-6 rounded-full border`), con `aria-pressed` y anillo `magic` en la elegida.
  - Botón **Añadir** (`bg-arcane`), que hace `router.post('/decorations', { kind, color })`.
  - Color por defecto por tipo (mapa `DEFAULT_COLOR`), p. ej. rosas → `rose`, planta → `green`, vela → `gold`, gato → `clay`.
- Botón **Quitar todas** (`border-danger text-danger`) con `window.confirm(ui.decorations_remove_all_confirm)` y `router.delete('/decorations')`.
- Lista **Tus objetos** (`ui.decorations_placed`): filas con dibujo pequeño, nombre, punto de color, nombre de la estantería y **×** (`ui.decoration_remove`). Vacía → `ui.decorations_empty`.

### Biblioteca (`Shelf.vue`)

- La fila mezcla libros y objetos con `mergeShelfItems(shelf.books, shelf.decorations)`.
- Objeto: contenedor `group relative shrink-0` de ~56 px (64 px desde `sm`), dibujo de 64–80 px de alto, sombra suave sobre la balda, alineado al fondo como los libros.
- Halo `gold` al pasar el ratón; sin desplazamiento ni escala.
- **×** circular arriba a la derecha, `hidden … sm:flex`, opacidad 0 hasta `group-hover` (mismo patrón que el botón portada/lomo), `aria-label`/`title` = `ui.decoration_remove`.
- `aria-label` y `title` del objeto con su nombre traducido.
- No son enlaces, no abren ficha y no cuentan en «N libros».
- El estado vacío sigue dependiendo solo de los libros.

### Arrastrar (`useLibraryDrag`)

- Generaliza el elemento arrastrado a `{ type: 'book' | 'decoration', item, shelf }`, detectando `[data-book-id]` y `[data-decoration-id]`.
- Se mantienen los umbrales (6 px ratón, 320 ms + tolerancia 8 px táctil), el auto-scroll, el resaltado de destinos con «Suelta aquí», el bloqueo del clic posterior y la cancelación con Escape.
- Miniatura: `BookDragGhost` para libros (sin cambios) y `DecorationDragGhost` para objetos (el dibujo a tamaño real).
- Cálculo del destino al soltar:
  - Sobre un libro: ancla = ese libro si el puntero está en su mitad derecha; si no, el libro anterior (o nulo).
  - Sobre un objeto: misma ancla y `sort` del objeto señalado (+1 si mitad derecha).
  - Zona libre de la balda: ancla = último libro, al final.
  - Otra estantería: se aplica la misma lógica con los libros de la estantería destino.
- `Library/Index.vue` envía `PATCH /decorations/{id}` con `{ shelf_id, after_book_id, sort }`, aplica el cambio de forma optimista, muestra `ui.decoration_moved` y revierte con `ui.decoration_move_error` si falla.

## Arte y paleta

`resources/js/Components/DecorationArt.vue` (`kind`, `color`, `size` opcional) con los 15 SVG planos en un `viewBox` 64 × 80:

`cup`, `roses`, `plant`, `candle`, `hourglass`, `crystal_ball`, `lamp`, `cat`, `skull`, `cage`, `astrolabe`, `tea_set`, `music_box`, `quill`, `photo_frame`.

- El color elegido pinta la pieza principal; los neutros salen de `madera`, `tinta` y `pergamino`.
- Mapa de color → token existente (sin hex nuevos):
  - `rose` → `orquidea`
  - `cream` → `crema`
  - `green` → `salvia`
  - `forest` → `bosque`
  - `gold` → `gold`
  - `clay` → `terracota`

## Accesibilidad y móvil

- Cada objeto con `aria-label` descriptivo y `title`.
- La **×** es un `button` real con foco visible; en móvil se oculta y se quita desde Ajustes.
- El arrastre en móvil usa la misma pulsación larga que los libros; si no, la balda sigue desplazándose en horizontal.
- `ui.drag_hint` se amplía para mencionar que también se arrastran los objetos.

## Claves de idioma nuevas (8 idiomas)

`ui.decorations_heading`, `ui.decorations_help`, `ui.decoration_add`, `ui.decorations_remove_all`, `ui.decorations_remove_all_confirm`, `ui.decorations_placed`, `ui.decorations_empty`, `ui.decoration_remove`, `ui.decoration_color`, `ui.decoration_added`, `ui.decoration_moved`, `ui.decoration_move_error`, `ui.decoration_removed`, `ui.decorations_cleared`, `ui.decoration_cup`, `ui.decoration_roses`, `ui.decoration_plant`, `ui.decoration_candle`, `ui.decoration_hourglass`, `ui.decoration_crystal_ball`, `ui.decoration_lamp`, `ui.decoration_cat`, `ui.decoration_skull`, `ui.decoration_cage`, `ui.decoration_astrolabe`, `ui.decoration_tea_set`, `ui.decoration_music_box`, `ui.decoration_quill`, `ui.decoration_photo_frame`, `ui.color_rose`, `ui.color_cream`, `ui.color_green`, `ui.color_forest`, `ui.color_gold`, `ui.color_clay`.

## Pruebas

PHP (`tests/Feature/DecorationTest.php`):

1. Añadir crea el objeto al final de la primera estantería con el ancla correcta.
2. Rechaza `kind` y `color` inválidos.
3. No permite mover ni quitar objetos de otra persona (403).
4. Mueve a otra estantería y reancla.
5. Rechaza un ancla que no está en la estantería destino o es de otro usuario.
6. Quitar uno y quitar todos (solo los del usuario).
7. Borrar la estantería borra sus objetos; borrar el libro deja el objeto sin ancla.
8. `LibraryController` entrega `decorations`; Ajustes entrega la lista con su estantería.

JS:

- `mergeShelfItems`: principio, entre dos libros, ancla ausente → final, orden estable y varias copias.
- `Shelf.test.js`: pinta objetos intercalados y la **×** solo en escritorio.
- Ajustes: añadir y quitar todas llaman a las rutas correctas.

## Documentación

- `docs/DISENO.md`: nueva subsección «Objetos decorativos (`DecorationArt`)», actualización de la sección de la estantería (fila mixta libros + objetos), ampliación del texto de arrastre y fila nueva en el registro de cambios con fecha 2026-10-30.
- Este spec queda como referencia de la funcionalidad.
