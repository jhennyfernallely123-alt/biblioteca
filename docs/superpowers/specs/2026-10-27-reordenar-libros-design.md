# Reordenar libros dentro de una estantería

**Fecha:** 2026-10-27
**Estado:** aprobado por la usuaria (diseño); pendiente de implementación
**Ámbito:** orden manual de libros en `Library/Index`, persistencia en `books` y endpoint de reordenamiento

## 1. Objetivo

Permitir cambiar la posición de cualquier libro dentro de su estantería arrastrándolo entre los demás, tanto en modo portadas como en modo lomos, sin perder el arrastre existente entre estanterías ni el clic que abre la ficha del libro.

El orden debe persistir al recargar y al volver a iniciar sesión.

## 2. Decisiones aprobadas

1. El reordenamiento manual se hace **solo dentro de la misma estantería**.
2. Al mover un libro a otra estantería se coloca **al final** de la estantería destino.
3. Los libros nuevos se colocan **al final** de su estantería.
4. El orden se guarda con una **posición numérica por libro**; no se usan posiciones decimales.
5. La reordenación se hace **solo arrastrando**. No se añade un campo de posición en la ficha del libro.
6. La actualización es **optimista**, con reversión y aviso si el servidor falla.

## 3. Modelo de datos

### Migración

Añadir a `books`:

- `position`: entero sin signo, no nulo, valor por defecto `0`.
- Índice `['shelf_id', 'position']`.

Al aplicar la migración, los libros existentes reciben posiciones consecutivas por estantería conservando el orden alfabético que se mostraba hasta ahora:

1. Leer los libros ordenados por `shelf_id` y `title`.
2. Asignar `0, 1, 2...` a cada estantería.
3. Guardar las posiciones.

La migración debe funcionar en MySQL de InfinityFree y en SQLite de las pruebas.

### Consultas

`Shelf::books()` pasa a ordenar por `position` y, como desempate, por `title`. La relación usada por `LibraryController` conserva ese orden.

### Altas y movimientos

- Al crear un libro: `position = max(position) + 1` dentro de su estantería.
- Al mover un libro a otra estantería: se añade al final de la destino.
- Al cambiar la estantería desde la ficha de edición: se añade al final de la nueva estantería.
- Al eliminar una estantería: sus libros se añaden al final de la estantería de respaldo, en su orden actual, y luego se reindexa la respaldo.

Las posiciones pueden quedar con huecos tras una eliminación; el orden visible sigue siendo correcto. Las operaciones que mueven varios libros reindexan las estanterías afectadas dentro de una transacción.

## 4. Servidor

### Endpoint nuevo

`PATCH /books/reorder`

La ruta debe declararse **antes** de `PATCH /books/{book}` para que `reorder` no se interprete como un ID de libro.

Entrada:

```json
{
  "shelf_id": 12,
  "ids": [8, 3, 15, 4]
}
```

Validación:

- `shelf_id`: obligatorio, entero.
- `ids`: obligatorio, array, cada elemento entero y distinto.
- La estantería debe pertenecer a la usuaria autenticada.
- La lista de IDs debe coincidir exactamente con todos los libros de esa estantería, sin faltantes ni ajenos. Si no coincide, responder `422`.

Actualización:

- Dentro de una transacción, asignar `position` según el índice de cada ID en la lista.
- Responder con `back()`.

### `BookController`

- `store()` asigna la posición final de la estantería.
- `update()` conserva la posición si no cambia la estantería; si cambia, asigna la posición final de la nueva.
- `move()` añade al final de la estantería destino.
- Nuevo método `reorder()` con la lógica anterior.
- Añadir `position` a `$fillable`; nunca se acepta desde la petición del formulario, solo lo asigna el controlador.

### `ShelfController`

- Al eliminar una estantería, mover sus libros al respaldo respetando su orden y asignándoles posiciones al final, antes de borrar la estantería.

## 5. Interacción frontend

### Arrastre

Se reutiliza `useLibraryDrag` y la pulsación larga existente:

- Escritorio: el arrastre empieza tras mover 6 px.
- Móvil: pulsación larga de 320 ms; si el dedo se mueve más de 8 px antes, se cancela y la página hace scroll.
- `Escape` cancela.
- El auto-scroll en los bordes superior e inferior se mantiene.

### Destino dentro de la misma estantería

Al arrastrar sobre otro libro de la misma estantería:

- Se calcula si el libro arrastrado quedará **antes** o **después** según la mitad horizontal del libro bajo el puntero.
- Se muestra una línea vertical de inserción entre los libros.
- Si el puntero está en una zona vacía de la estantería, se propone el final.
- Si el puntero está sobre el propio libro arrastrado, no se muestra destino.
- Si la posición final es la misma que la inicial, no se hace ninguna petición.

Al soltar:

1. Se calcula la nueva lista de IDs.
2. Se reemplaza el orden local de forma optimista.
3. Se envía `PATCH /books/reorder` con `shelf_id` e `ids`.
4. Si falla, se restaura la instantánea anterior y se muestra un aviso.

### Destino en otra estantería

Se mantiene la interacción actual: se resalta la estantería con “Suelta aquí” y el libro se añade al final. No se elige una posición exacta al cruzar de estantería.

### Orden local

La función que calcula el nuevo orden se extrae a `resources/js/libraryOrder.js` para poder probarla sin montar la página completa. Debe manejar:

- Insertar antes o después de un libro.
- Mover el primero al cuarto o quinto lugar.
- Mover al final.
- Detectar que el orden no cambió.
- No perder ni duplicar libros.

## 6. Estados visuales

- **Línea de inserción:** vertical, 2 px, color `magic`, con resplandor suave del mismo tono y extremos redondeados. Se dibuja en el hueco anterior o posterior al libro objetivo.
- **Origen:** opacidad 30 %, como en el arrastre actual.
- **Estantería destino entre estanterías:** trazo continuo `magic` y etiqueta “Suelta aquí”, sin cambios.
- **Aviso de éxito:** “Libro reordenado.” con borde `success`.
- **Aviso de error:** “No se pudo cambiar el orden. Inténtalo de nuevo.” con borde y texto `danger`.
- **Movimiento reducido:** la línea aparece sin animación adicional.

No se introducen tokens nuevos. Se usan `magic`, `gold`, `night`, `night-raised`, `moon`, `success`, `danger` y los radios/sombras existentes. El mueble, las portadas y los lomos no cambian.

## 7. Textos

Claves nuevas en los ocho idiomas curados (`es`, `en`, `ca`, `gl`, `pt`, `fr`, `de`, `it`):

- `ui.book_reordered`: “Libro reordenado.”
- `ui.book_reorder_error`: “No se pudo cambiar el orden. Inténtalo de nuevo.”
- Actualizar `ui.drag_hint` para mencionar que también se puede cambiar la posición dentro de la estantería.

Todos los textos visibles se leen con `t('ui.*')`.

## 8. Accesibilidad

- El gesto mantiene la alternativa existente para mover entre estanterías desde la ficha del libro.
- La reordenación dentro de la estantería es solo por arrastre, según la decisión de la usuaria; no se añade campo de posición.
- La ayuda del gesto sigue en un párrafo `sr-only` enlazado con `aria-describedby`.
- Los avisos usan `role="status"` y `aria-live="polite"`.
- La línea de inserción no depende solo del color: es un trazo continuo y el destino también se anuncia en la ayuda.
- Se respeta `prefers-reduced-motion`.

## 9. Pruebas

### Backend

- Reordena varios libros de una estantería propia y persiste las posiciones.
- Rechaza una estantería ajena.
- Rechaza una lista con IDs de otra estantería, IDs ajenos o lista incompleta.
- Rechaza IDs duplicados.
- Un libro nuevo se crea al final.
- Mover un libro a otra estantería lo coloca al final.
- Cambiar de estantería desde la ficha lo coloca al final.
- Eliminar una estantería coloca sus libros al final del respaldo sin romper el orden.

### Frontend

- `libraryOrder.test.js`: mover antes/después, primero a cuarto, al final, sin cambio y sin pérdida de elementos.
- `Shelf.test.js`: la línea de inserción aparece en el libro y lado correctos.
- `BookCard.test.js` y `BookSpine.test.js`: clase de destino aplicada sin alterar enlace ni accesibilidad.

### Verificación manual

- `npm run test`
- `php artisan test`
- `npm run build`
- Navegador real en escritorio y a 390 px con gesto táctil:
  - Reordenar portadas y lomos.
  - Mover el primero al cuarto o quinto lugar.
  - Soltar en zona vacía para ir al final.
  - Soltar en el mismo lugar no hace petición.
  - Fallo de red revierte el orden.
  - Mover a otra estantería sigue añadiendo al final.

## 10. Despliegue

- La nueva migración se aplica con el flujo existente `/deploy/migrate/{token}`.
- No se añaden dependencias, colas, workers ni requisitos de Node.js en el servidor.
- No se usan enlaces simbólicos.

## 11. Fuera de alcance

- Elegir posición exacta al mover entre estanterías.
- Campo de posición en la ficha del libro.
- Reordenar varios libros a la vez.
- Cambiar el orden de las estanterías, que ya existe.
- Cambios en la paleta o en el mueble.

## 12. Archivos previstos

- `database/migrations/2026_10_27_000000_add_position_to_books_table.php` (nuevo).
- `app/Models/Book.php`.
- `app/Models/Shelf.php`.
- `app/Http/Controllers/BookController.php`.
- `app/Http/Controllers/ShelfController.php`.
- `routes/web.php`.
- `resources/js/useLibraryDrag.js`.
- `resources/js/libraryOrder.js` (nuevo).
- `resources/js/Pages/Library/Index.vue`.
- `resources/js/Components/Shelf.vue`.
- `resources/js/Components/BookCard.vue`.
- `resources/js/Components/BookSpine.vue`.
- `resources/css/textures.css`.
- `lang/{es,en,ca,gl,pt,fr,de,it}.json`.
- `tests/Feature/BookTest.php`.
- `tests/Feature/ShelfTest.php`.
- `resources/js/Components/__tests__/{Shelf,BookCard,BookSpine}.test.js`.
- `resources/js/libraryOrder.test.js` (nuevo).
- `docs/DISENO.md`.

## 13. Riesgos y mitigaciones

- **Confundir reordenar con mover de estantería:** la línea de inserción solo aparece dentro de la estantería de origen; al salir, cambia al resaltado de “Suelta aquí”.
- **Pérdida de orden por optimismo:** instantánea local y reversión en error.
- **Listas incompletas o manipuladas:** el servidor exige coincidencia exacta con los libros de la estantería.
- **Cambios de estantería que pierden la posición:** el servidor siempre asigna el final al mover o cambiar de estantería.
- **Borrar una estantería con posiciones repetidas:** se reindexa el respaldo después de anexar los libros.
- **Ruta capturada por `{book}`:** declarar `/books/reorder` antes de `/books/{book}`.
