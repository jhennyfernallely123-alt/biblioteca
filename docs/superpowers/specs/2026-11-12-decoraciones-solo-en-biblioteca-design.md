# Biblioteca — Las decoraciones solo se ven en la Biblioteca

**Fecha:** 2026-11-12
**Estado:** Diseño aprobado por la usuaria; pendiente de implementación.
**Documento de identidad visual:** `docs/DISENO.md` (documento vivo, de cumplimiento obligatorio).
**Reglas del proyecto para agentes:** `AGENTS.md`.

---

## 1. Objetivo

Las decoraciones dejan de verse en la pantalla de **Estanterías**. A partir de ahora solo se muestran en la **Biblioteca**, que es el mueble donde se★★★★
exhiben los libros y los objetos que los acompañan.

En Estanterías la página queda únicamente con libros: cada balda muestra sus lomos y portadas, sin objetos decorativos.

## 2. Decisiones aprobadas

- Las decoraciones **no se ven en Estanterías**. No se dibujan, no se arrastran y no se borran desde ahí.
- Las decoraciones **siguen igual en la Biblioteca**: se muestran, se pueden arrastrar entre bandas y se quitan con la **×**.
- El **módulo Decoraciones** no cambia: sigue siendo donde se añaden y se quitan los objetos.
- La **base de datos no cambia**: las decoraciones siguen guardadas con su `shelf_id` y su `after_book_id`.
- El helper `shelfItems.js` **no se toca**: es un módulo con sus propias pruebas y sigue siendo correcto con la lista de decoraciones vacía.

## 3. Alcance

### Incluido

- `ShelvesController::index` deja de cargar la relación `decorations` de cada estantería.
- `Shelves/Index.vue` deja de mapear `decoraciones`, de resolver `moveDecoration` y de montar el `DecorationDragGhost`.
- `Shelf.vue` deja de importar y dibujar `ShelfDecoration`.
- Se elimina el componente `ShelfDecoration.vue` y su test, que quedan sin uso.
- Se actualiza `Shelf.test.js` para comprobar que la estantería no dibuja decoraciones.
- Se actualiza `DecorationTest` para comprobar que las estanterías ya no las entregan.
- Se actualiza `docs/DISENO.md`.

### Excluido

- La Biblioteca: no cambia ni una línea.
- El módulo Decoraciones: no cambia.
- `useLibraryDrag`: **no se toca**. Es un hook compartido y la Biblioteca lo necesita.
- `shelfItems.js`: no se toca.
- La base de datos y las migraciones.
- Las traducciones.

## 4. Backend

### 4.1 `ShelvesController`

Hoy la relación se carga aquí:

```php
->with([
    'books' => fn ($query) => $query
        ->withSum('readingSessions', 'minutes')
        ->withCount('readingSessions'),
    'decorations',
])
```

Se quita la línea `'decorations',`:

```php
->with([
    'books' => fn ($query) => $query
        ->withSum('readingSessions', 'minutes')
        ->withCount('readingSessions'),
])
```

Con esto, cada estantería llega al navegador sin decoraciones y la página carga menos datos.

## 5. Frontend

### 5.1 `Shelves/Index.vue`

Cuatro cambios:

1. `cloneShelves` deja de copiar `decorations`; la estantería se queda solo con `books`.
2. Se elimina la función `moveDecoration` completa.
3. Se elimina el import de `DecorationDragGhost` y su bloque `<DecorationDragGhost v-else-if="draggedItem?.type === 'decoration'" ...>`.
4. Se elimina la importación de `DecorationDragGhost` en el `<script setup>`.

La propiedad `draggingDecorationId` que la página pasa a `Shelf` se retira, porque `Shelf` ya no la usa.

### 5.2 `Shelf.vue`

- Se elimina el import de `ShelfDecoration`.
- Se elimina la prop `draggingDecorationId`.
- Se elimina la función `dropPositionForDecoration`.
- La lista `items` pasa a ser solo la lista de libros, sin mezclar decoraciones:

```js
const items = computed(() => props.shelf.books ?? []);
```

- Se elimina el bloque `<ShelfDecoration v-else ... />` del `<template>`, dejando solo el `BookCard`/`BookSpine`.

### 5.3 Componente `ShelfDecoration.vue`

Se **elimina el archivo**. Solo lo usaba Estanterías: la Biblioteca dibuja las suyas con `BookcaseDecoration`, que es un componente distinto y se queda.

Esto arrastra también la eliminación de `resources/js/Components/__tests__/ShelfDecoration.test.js`, que solo prueba ese componente.

## 6. Por qué no se toca `useLibraryDrag`

El hook `useLibraryDrag` lo comparten las dos páginas. La Biblioteca lo usa en modo `virtual` (`{ virtual: true }`): las bandas son los destinos y la caída se traduce a `PATCH /bookcase/move`. Estanterías lo usa en modo normal, con cada estantería como destino.

El hook resuelve decoraciones para calcular la posición de inserción (`decorationInsertion`) y busca la decoración arrastrada en `shelf.decorations`. Como ambas páginas comparten el hook, **quitarle el soporte de decoraciones rompería la Biblioteca**, donde sí se arrastran.

La limpieza se hace, por tanto, en la página de Estanterías: se deja de pedir decoraciones, se deja de resolver su movimiento y se deja de pintar su fantasma.

## 7. Por qué no se toca `shelfItems.js`

`mergeShelfItems` mezcla libros y decoraciones de una estantería. Lo usa `Shelf.vue` y tiene su propio archivo de pruebas (`resources/js/shelfItems.test.js`).

Con la lista de decoraciones vacía devuelve solo los libros, que es exactamente lo que se quiere. Como la Biblioteca mezcla con `bookcaseBands.js` (otro módulo), `mergeShelfItems` queda como un módulo válido y correcto. Retirarlo sería una limpieza mayor sin beneficio y con riesgo de romper sus pruebas.

## 8. Comportamiento resultante

- **Estanterías**: cada balda muestra sus libros. Sin objetos, sin × y sin arrastre de objetos.
- **Biblioteca**: sin cambios. Los objetos se ven, se arrastran entre bandas y se quitan con la ×.
- **Módulo Decoraciones**: sin cambios. Añadir y quitar objetos sigue igual, y el botón **Quitar** sigue eliminando la última copia del tipo.
- **Arrastre en Estanterías**: se sigue moviendo libros con el mismo gesto.

## 9. Pruebas

### 9.1 Vitest

- `resources/js/Components/__tests__/Shelf.test.js`: el caso *"intercala los objetos con los libros según su ancla"* se elimina, porque ya no hay objetos que intercalar. Se añade un caso que comprueba que la estantería **no** dibuja ninguna decoración aunque los datos las traigan, para que la regresión quede cubierta.
- `resources/js/Components/__tests__/ShelfDecoration.test.js`: se elimina junto con el componente.
- `resources/js/shelfItems.test.js`: **no cambia**. El módulo sigue igual.

### 9.2 Pest

En `tests/Feature/DecorationTest.php`, el caso *"entrega las decoraciones en las estanterías"* se sustituye por uno que comprueba lo contrario: la página de Estanterías **no** incluye las decoraciones.

```php
it('no entrega las decoraciones en las estanterías', function () {
    $shelf = Shelf::factory()->for($this->user)->create(['position' => 0]);
    Decoration::factory()->for($this->user)->for($shelf)->create(['kind' => 'cup']);

    $this->get(route('shelves.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('shelves.0.decorations'));
});
```

El resto de `DecorationTest` no cambia: el módulo Decoraciones sigue entregando los objetos, y el borrado desde la Biblioteca sigue cubierto por la prueba de regresión añadida el 2026-11-12.

### 9.3 Comprobaciones manuales

- En Estanterías no se ve ninguna decoración en ninguna balda.
- En Estanterías se sigue viendo el contador de libros y el resto de la página sin cambios.
- Arrastrar un libro entre estanterías sigue funcionando.
- En la Biblioteca las decoraciones se ven, se arrastran y se quitan con la × igual que antes.
- El módulo Decoraciones sigue añadiendo y quitando objetos.

## 10. Documentación visual

En `docs/DISENO.md`:

- En la sección **Estantería (`Shelf` + `ShelfBoard`)**, se aclara que las baldas muestran solo libros y que los objetos decorativos se ven únicamente en la Biblioteca.
- En la sección **Objeto decorativo (`DecorationArt` + `ShelfDecoration`)**, se cambia el encabezado por **Objeto decorativo (`DecorationArt` + `BookcaseDecoration`)**, se describe la × de la Biblioteca y se elimina la referencia a `ShelfDecoration` y a su × de escritorio.
- Se añade una fila con fecha **2026-11-12** al registro de cambios.

## 11. Riesgos y consideraciones

- **Las decoraciones que ya existen no se pierden.** Siguen en la base de datos con su estantería y su ancla, y se siguen viendo en la Biblioteca. Lo único que cambia es que dejan de dibujarse en Estanterías.
- **Las decoraciones ancladas a un libro de una estantería concreta** siguen ancladas a ese libro. En la Biblioteca la colocación visual la decide `packBands`, que reparte los libros según el ancho disponible; por eso una misma decoración puede verse en una banda u otra según cómo se repartan. Este comportamiento **ya existía** y no cambia con este trabajo.
- **Menos datos por carga:** al no cargar la relación `decorations`, la página de Estanterías es más ligera.
- **El arrastre de objetos sigue existiendo en la Biblioteca**, que es donde tiene sentido: allí las bandas se reempaquetan según el ancho y un objeto debe poder cambiar de banda.
