# Arrastrar libros entre estanterías

**Fecha:** 2026-10-27
**Estado:** aprobado por la usuaria (interacción); implementado
**Ámbito:** Biblioteca (`Library/Index`) y sus componentes de estantería, libro y aviso

## 1. Objetivo

Permitir mover un libro a otra estantería arrastrándolo, sin perder el clic normal que abre la ficha de edición y sin sustituir el selector de estantería que ya existe en esa ficha.

La interacción debe sentirse directa y táctil en escritorio y en móvil, y debe integrarse con la estética «Noche arcana» sin tocar el mueble de madera ni la paleta de portadas y lomos.

## 2. Decisiones aprobadas

1. **Arrastrar y soltar** es el gesto principal, en lugar de un menú contextual o botones por libro.
2. **Escritorio:** el arrastre empieza tras mover 6 px con el botón principal; el clic sin movimiento sigue abriendo la ficha.
3. **Móvil:** pulsación larga de 320 ms y arrastre. Si el dedo se desplaza más de 8 px antes de ese tiempo, el gesto se cancela y la página puede hacer scroll.
4. **Misma estantería, fuera de una estantería o Escape:** no se mueve nada y no se hace ninguna petición.
5. **Actualización optimista:** el libro cambia de estantería al instante; si la petición falla, vuelve a su sitio y se muestra un aviso de error.
6. **Alternativa accesible:** la ficha del libro conserva el selector **Mover a otra estantería**. El arrastre es una mejora, no el único camino.

## 3. Flujo de interacción

### Escritorio

1. `pointerdown` sobre `[data-book-id]` con botón principal.
2. A los 6 px de desplazamiento se activa el arrastre.
3. Se crea una miniatura flotante, se atenúa el libro de origen y se resaltan las estanterías.
4. La estantería bajo el puntero se marca como destino con «Suelta aquí».
5. `pointerup` sobre otra estantería ejecuta el movimiento.
6. `Escape` cancela mientras se arrastra.

### Móvil

1. `touchstart` sobre un libro inicia un temporizador de 320 ms.
2. Antes de ese tiempo, un desplazamiento mayor de 8 px cancela el gesto y permite el scroll nativo.
3. Al cumplirse el temporizador se activa el arrastre y se evita el scroll con `preventDefault` en `touchmove`.
4. `touchend` sobre otra estantería ejecuta el movimiento.
5. Un clic sintetizado después del gesto se suprime para no abrir la ficha.

### Auto-scroll

Mientras se arrastra, si el puntero está a menos de 90 px del borde superior o inferior de la ventana, la página se desplaza hasta 18 px por fotograma en esa dirección. El destino se recalcula después de cada desplazamiento.

## 4. Estados visuales

- **Origen:** el libro baja a opacidad 30 %; el cursor es `grabbing`.
- **Miniatura:** sigue al puntero sin transición, con `pointer-events: none` y `aria-hidden="true"`.
  - Modo portadas: miniatura de 76 px de ancho con proporción 2:3, portada o título de respaldo.
  - Modo lomos: rectángulo de 28 × 148 px con el título en vertical.
  - Resplandor `magic` y borde `dorado` suave.
- **Estanterías durante el arrastre:** trazo discontinuo `magic` al 28 %, con offset de 8 px.
- **Destino:** trazo continuo de 2 px `magic`, resplandor exterior `magic` al 24 % y etiqueta **Suelta aquí** en `magic` con texto `night`.
- **Aviso de resultado:** fijo abajo, `role="status"`, `aria-live="polite"`, 4 s, fondo `night-raised`; éxito con borde `success`, error con borde y texto `danger`.
- **`prefers-reduced-motion`:** se mantienen los estados, pero sin transiciones añadidas.

No se introducen tokens nuevos: se usan `night-raised`, `magic`, `gold`, `success`, `danger`, `moon`, `night`, `madera`, `crema`, `tinta`, `dorado` y los radios/sombras existentes.

## 5. Arquitectura

### Composable `resources/js/useLibraryDrag.js`

- Delegación de eventos desde el contenedor de estanterías: `pointerdown` para ratón/lápiz y `touchstart` para táctil.
- Localiza el libro con `[data-book-id]` y la estantería con `[data-shelf-id]` mediante `elementFromPoint().closest(...)`.
- Mantiene el estado: libro arrastrado, modo (portadas/lomos), posición del puntero, destino y supresión del clic.
- Gestiona captura de puntero, long-press, umbrales, auto-scroll con `requestAnimationFrame`, Escape y limpieza de listeners.
- No conoce Inertia ni la estructura de datos de la página; recibe `getShelves()` y `onDrop(book, shelfId)`.

### `Library/Index.vue`

- Clona las estanterías recibidas para poder hacer la actualización optimista sin mutar props.
- Sincroniza el clon cuando Inertia entrega nuevas props.
- Ejecuta `router.patch('/books/{id}/move', { shelf_id })` con `preserveScroll` y `preserveState`.
- Guarda una instantánea antes del cambio; en `onError` y en `onFinish` sin resolución, restaura la instantánea y muestra el aviso de error.
- Monta `BookDragGhost` y el aviso, y conecta los eventos del composable al contenedor de estanterías.

### `Shelf.vue`

- Añade `data-shelf-id` y las props `dragActive`, `dropTargetId` y `draggingBookId`.
- Aplica `shelf-drag-active` y `shelf-drop-target`, muestra la etiqueta «Suelta aquí» y pasa `dragging` a las tarjetas o lomos.

### `BookCard.vue` y `BookSpine.vue`

- Añaden `data-book-id`, `draggable="false"` y la clase `book-drag-source`.
- Atenúan el libro cuando `dragging` es verdadero.
- Conservan título, autor, `aria-label`, tooltip y enlace de edición.

### `BookDragGhost.vue`

- Renderiza con `Teleport` al `body`.
- Muestra portada o lomo según el modo de la estantería de origen.
- Es decorativo (`aria-hidden`) y no recibe eventos.

## 6. Servidor

No se añaden rutas ni lógica nueva: se reutiliza `PATCH /books/{book}/move` (`BookController@move`), que ya valida `shelf_id`, comprueba la propiedad del libro y de la estantería y responde con `back()`.

Se añade una prueba de regresión: un libro propio no puede moverse a una estantería de otro usuario (`403`, sin cambio de `shelf_id`).

## 7. Textos

Claves nuevas en los ocho idiomas curados (`es`, `en`, `ca`, `gl`, `pt`, `fr`, `de`, `it`):

- `ui.drop_here`: «Suelta aquí».
- `ui.book_moved`: `«:title» se movió a «:shelf».`.
- `ui.book_move_error`: «No se pudo mover el libro. Inténtalo de nuevo.».
- `ui.drag_hint`: ayuda del gesto para lectores de pantalla.

Los textos visibles se leen con `t('ui.*')`; no hay cadenas nuevas literales en componentes.

## 8. Accesibilidad

- El gesto no es la única vía: el selector de la ficha sigue disponible y se puede usar con teclado.
- La ayuda del gesto está en un párrafo `sr-only` enlazado con `aria-describedby`.
- El resultado se anuncia con `role="status"` y `aria-live="polite"`.
- Los estados de destino no dependen solo del color: hay trazo continuo y la etiqueta «Suelta aquí».
- La miniatura es decorativa y no captura el puntero.
- Se respeta `prefers-reduced-motion`.

## 9. Responsive

- En móvil se usa pulsación larga para no bloquear el scroll normal.
- Las estanterías mantienen sus áreas táctiles y su disposición; el gesto funciona en modo portadas y lomos.
- La etiqueta «Suelta aquí» se coloca en la cabecera de la estantería con `flex-wrap` para no desbordar a 360 px.
- El aviso se muestra por encima de la navegación inferior (`bottom-20`) y baja a `bottom-6` en escritorio.

## 10. Archivos afectados

- `resources/js/useLibraryDrag.js` (nuevo).
- `resources/js/Components/BookDragGhost.vue` (nuevo).
- `resources/js/Pages/Library/Index.vue`.
- `resources/js/Components/Shelf.vue`.
- `resources/js/Components/BookCard.vue`.
- `resources/js/Components/BookSpine.vue`.
- `resources/css/textures.css`.
- `lang/{es,en,ca,gl,pt,fr,de,it}.json`.
- `tests/Feature/BookTest.php`.
- `resources/js/Components/__tests__/{BookCard,BookSpine,Shelf}.test.js`.
- `docs/DISENO.md`.
- Este documento.

## 11. Verificación

1. `npm run test`: pruebas de datos, atenuación, destino y etiqueta.
2. `php artisan test`: 74 pruebas, incluida la estantería ajena.
3. `npm run build`.
4. Navegador real con Edge headless y Playwright:
   - Arrastre de portada en escritorio con miniatura, resaltados y aviso.
   - Clic normal después del arrastre abre la ficha.
   - Escape cancela.
   - Arrastre de lomo muestra la miniatura vertical.
   - Misma estantería y soltar fuera no hacen peticiones.
   - Fallo de red revierte el cambio y muestra el aviso de error.
   - Móvil a 390 px con `hasTouch`: pulsación larga, arrastre, aviso y clic posterior; sin overflow horizontal.
5. Capturas de escritorio y móvil durante el arrastre.
6. Revisión de `docs/DISENO.md` y su registro de cambios.

## 12. Fuera de alcance

- Reordenar libros dentro de la misma estantería.
- Mover estanterías completas.
- Arrastrar desde la ficha de edición.
- Nuevos endpoints, colas o dependencias en producción.
- Cambios en la paleta o el mueble.

## 13. Riesgos y mitigaciones

- **Confundir arrastre con clic:** umbral de 6 px y supresión temporal del clic tras el gesto.
- **Bloquear el scroll móvil:** long-press con cancelación por movimiento y `preventDefault` solo después de activar.
- **Pérdida de datos por optimismo:** instantánea local y reversión en error o fin sin confirmación.
- **Arrastre nativo de imágenes o enlaces:** `draggable="false"`, `-webkit-user-drag: none` y `@dragstart.prevent`.
- **Destino difícil de ver:** trazo, resplandor y etiqueta textual, no solo color.
