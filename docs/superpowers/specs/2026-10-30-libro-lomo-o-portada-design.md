# Diseño: lomo o portada por libro

**Fecha:** 2026-10-30
**Estado:** aprobado por la usuaria

> **Actualización 2026-11-09:** la usuaria pidió quitar el selector de vista por defecto de Configuraciones. El modo se cambia por libro en Biblioteca o Estanterías; los libros nuevos usan el valor por defecto de la base de datos (`covers`). El resto del diseño sigue vigente.

## Objetivo

Que cada libro de la biblioteca se pueda mostrar como portada o como lomo de forma individual, sin depender del modo de su estantería.

## Decisiones

- **Cada libro es independiente.** Desaparece el botón de la estantería que cambiaba todos sus libros a la vez.
- **Mezcla en la misma balda.** Los libros de una estantería se muestran en una sola fila respetando su orden: las portadas se ven grandes y los lomos finos, alineados por la base. En móvil la balda se desliza en horizontal; en escritorio ocupa varias filas.
- **Botón rápido solo en escritorio.** Al pasar el ratón por un libro aparece un botón pequeño con icono para alternar portada/lomo. En móvil no se muestra porque tocar un libro lo abre; allí se cambia desde la ficha.
- **Ficha del libro.** Nueva sección «Mostrar como» con selector Portada / Lomo que se aplica al instante.
- **Modo inicial.** Los libros nuevos toman `users.default_view_mode` (Ajustes). Los existentes conservan el modo que tenía su estantería en el momento de la migración.

## Modelo de datos

- `books.view_mode` (`string`, `covers` | `spines`, por defecto `covers`).
- Se elimina `shelves.view_mode` y el botón de la estantería.
- La migración copia a cada libro el `view_mode` de su estantería antes de borrar la columna.

## Backend

- `PATCH /books/{book}/view-mode` con `view_mode` en `covers|spines`; autoriza con la policy `update` del libro y responde `back()` con scroll preservado.
- `BookController@store` asigna el modo por defecto del perfil.
- `ShelfController` deja de asignar `view_mode` al crear estanterías y al crear la estantería de respaldo al eliminar una.

## Frontend

- `Shelf.vue`: contenedor único `flex items-end gap-3 overflow-x-auto sm:flex-wrap`; cada libro se envuelve en un contenedor relativo con su botón de cambio (visible solo en `sm` y al hacer hover/foco).
- `BookCard` mantiene su rejilla interna; se le fija el ancho desde el envoltorio (~100 px móvil, ~110 px escritorio).
- `BookSpine` conserva su ancho calculado.
- `Books/Edit.vue`: sección «Mostrar como» con selector que hace `PATCH` inmediato.

## Textos

Claves nuevas en los 8 idiomas revisados: `ui.view_mode_heading`, `ui.show_as_cover`, `ui.show_as_spine`, `ui.switch_view`.

## Pruebas

- Feature: cambiar el modo del libro propio, prohibido en libro ajeno, 422 con valor inválido, libro nuevo con el modo por defecto del perfil.
- Se elimina la prueba de `shelves.view_mode`.
- Vitest: el botón rápido hace la petición correcta.

## Documentación

- `docs/DISENO.md`: registro de cambios y descripción de la balda mixta.
