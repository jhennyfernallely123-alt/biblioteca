# Biblioteca completa y módulo Estanterías

**Fecha:** 2026-11-02
**Estado:** implementado

## Objetivo

Separar la vista de libros de la gestión de estanterías. La portada de la aplicación
muestra una biblioteca completa con todos los libros en bandas de tres, mientras que la
gestión de estanterías reales (arrastre de libros, renombrado, altas) se traslada a un
módulo propio llamado **Estanterías**.

## Alcance

- Nueva pantalla **Biblioteca** en `/` (`Library/Index`): mueble único con todos los libros
  de la cuenta, agrupados de tres en tres en bandas.
- Nueva pantalla **Estanterías** en `/shelves` (`Shelves/Index`): la pantalla anterior,
  sin cambios funcionales.
- Entrada **Estanterías** en el menú de escritorio y en la barra inferior móvil, entre
  Biblioteca y Decoraciones.
- Decoraciones visibles en las bandas de Biblioteca y arrastrables entre ellas.
- Los libros se arrastran con el mismo gesto que en Estanterías: se pueden colocar en
  cualquier banda, antes o después de otro libro, también hacia bandas anteriores.
- Cada libro respeta su `view_mode` (lomo o portada) y se pueden mezclar en la misma
  banda, igual que en Estanterías.

## Decisiones

- Cada libro guarda su banda (`band`) y su posición dentro de la banda
  (`band_position`). Las bandas son estables: sacar un libro deja la banda con menos
  libros y los de las bandas siguientes no suben.
- Una banda admite cinco libros y una decoración. Al insertar en una banda llena, el
  último libro de esa banda baja al principio de la siguiente y la operación se repite
  hacia abajo (cascada).
- Las bandas vacías no se dibujan; la numeración se conserva para el arrastre.
- Cada banda se comporta como una estantería virtual para el arrastre (`data-shelf-id`),
  de modo que se reutiliza `useLibraryDrag` y `resolveDecorationAnchor`.
- Una decoración ocupa la banda de su libro ancla si esa banda aún no tiene una; si la
  tiene, pasa a la siguiente banda con hueco o a una banda nueva. Nunca hay más de una
  decoración por banda.
- Al soltar una decoración en otra banda se guarda `shelf_id` (el de la estantería del
  libro ancla), `after_book_id` y `sort`, usando `PATCH /decorations/{decoration}`.
- Los cinco libros por banda no incluyen las decoraciones.

## Componentes

- `Pages/Library/Index.vue`: estado local optimista de libros y decoraciones, bandas
  computadas y aviso de error de arrastre.
- `Components/Bookcase.vue` no existe como componente: el marco del mueble se resuelve con
  las clases `.bookcase`, `.bookcase__frame`, `.bookcase__plinth` y `.bookcase__door` en
  `resources/css/textures.css`.
- `Components/BookcaseBand.vue`: balda con hasta cinco libros y una decoración; destino del
  arrastre.
- `Components/BookcaseDecoration.vue`: objeto arrastrable sin botón de borrar.
- `Components/BookSpine.vue` y `BookCard.vue` se usan tal cual, con `dragging` y
  `dropPosition`.
- `useLibraryDrag` acepta `{ virtual: true }`: las bandas son los destinos y la página
  traduce la caída a `PATCH /bookcase/move` con `book_id`, `band` y `position`.
- `resources/js/bookcaseBands.js`: `buildBands(books, decorations)` agrupa por `band`,
  ordena por `band_position` y reparte decoraciones; `insertBookInBand` aplica la misma
  cascada de forma optimista.
- `app/Http/Controllers/BookcaseController.php`: valida la propiedad del libro, inserta la
  posición, encadena la cascada y persiste en una transacción.
- `app/Http/Controllers/ShelvesController.php`: la lógica anterior de `LibraryController`.
- `app/Http/Controllers/LibraryController.php`: devuelve `books` (con su banda) y
  `decorations` globales.
- Migración `2026_11_02_000000_add_band_to_books_table.php`: añade las columnas y reparte
  los libros existentes de cinco en cinco.

## Flujo de datos

1. `GET /` carga todos los libros (con `band` y `band_position`) y las decoraciones.
2. `buildBands` agrupa por banda, ordena por `band_position` y asigna cada decoración a la
   banda de su ancla o a la siguiente con hueco.
3. Al arrastrar un libro, la página calcula la banda y el índice de destino, actualiza el
   estado local con `insertBookInBand` (que baja el sobrante en cascada) y envía
   `PATCH /bookcase/move`.
4. Al arrastrar una decoración, `useLibraryDrag` calcula el ancla con
   `resolveDecorationAnchor` sobre la banda de destino.
5. Si el `PATCH` falla, se restaura la copia anterior y se muestra el aviso `danger`.

## Errores y estados vacíos

- Sin libros: mensaje «Aún no hay libros en tu biblioteca.» y sin mueble.
- Si el `PATCH` falla, se restaura el estado anterior y se muestra
  `ui.book_move_error` o `ui.decoration_move_error`.
- El éxito de mover una decoración se anuncia con el `flash` del servidor.

## Pruebas

- `tests/Feature/LibraryPageTest.php`: orden global, aislamiento por usuario, decoraciones
  propias y módulo de estanterías.
- `tests/Feature/BookcaseTest.php`: cascada al llenar una banda, cascada encadenada, banda
  de origen con menos libros sin refuerzo y libro ajeno.
- `tests/Feature/DecorationTest.php` y `ShelfTranslationTest.php` actualizados a
  `/shelves`.
- `resources/js/bookcaseBands.test.js`: bandas guardadas, orden interno, decoraciones y
  `insertBookInBand` (hueco, no refuerzo y cascada encadenada).
- `BookcaseBand.test.js`: lomo o portada según `view_mode` y atenuación al arrastrar.
- `AppShell.test.js`: cuatro enlaces.

## Fuera de alcance

- Crear o renombrar estanterías fuera de Configuraciones.
- Cambiar el número máximo de libros o decoraciones por banda desde la interfaz.
