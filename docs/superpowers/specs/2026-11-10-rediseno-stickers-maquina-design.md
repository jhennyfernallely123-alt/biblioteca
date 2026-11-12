# Rediseño de los stickers de la máquina expendedora

**Fecha:** 2026-11-10  
**Estado:** Diseño aprobado por la usuaria, pendiente de revisión del spec  
**Alcance:** solo los 20 stickers de la máquina (`LibrarySticker`). Los 12 stickers de eventos (`EventSticker`) no se tocan.

## 1. Objetivo

Los stickers actuales se ven rudimentarios y poco cohesivos: dibujos simples, siluetas genéricas y un acabado que no parece una pegatina real. El rediseño debe convertir los 20 objetos de biblioteca en ilustraciones detalladas de estilo cohesivo, con silueta troquelada propia y colores naturales, sin salirse de la identidad visual del proyecto.

## 2. Decisiones de diseño

| Decisión | Elección |
|---|---|
| Estilo | Ilustración detallada y cohesiva, de detalle medio con sombreado suave |
| Silueta | Una silueta troquelada propia por sticker, que sigue la forma del objeto |
| Fondo interior | Blanco limpio dentro de la silueta |
| Colores | Solo tokens existentes; colores naturales correspondientes a cada objeto |
| Alcance | Solo los 20 stickers de la máquina |
| Stickers de eventos | Sin cambios |

## 3. Componente

`resources/js/Components/LibrarySticker.vue` pasa a ser data-driven:

- El array genérico `cuts` y el mapa `artworks` actuales se sustituyen por un mapa `stickers` con 20 entradas, una por clave de `config/cards.php` (`maquina_libro`, `maquina_pluma`, ..., `maquina_cristal`).
- Cada entrada contiene:
  - `art`: string SVG con la ilustración detallada.
  - `cut`: path cerrado de silueta troquelada en el `viewBox` 0 0 120 120.
- Se mantienen las props actuales: `sticker`, `size`, `locked`.
- Se mantienen las clases y la estructura del template para no romper estilos ni tests:
  - `.library-sticker`
  - `.library-sticker__svg`
  - `.library-sticker__cut`
  - `.library-sticker__art`
  - `.library-sticker__locked`
  - `.library-sticker--locked`
- El estado bloqueado deja de usar una silueta genérica: reutiliza la silueta del sticker en `surface-raised` con el `?` centrado.

## 4. Arte

- Ilustraciones SVG de detalle medio: contornos consistentes en `tinta` (2–2.5 px), formas claras y sombreado suave mediante capas de opacidad y brillos sutiles.
- Cada objeto centrado en el lienzo 120×120, con proporciones reconocibles y espacio suficiente para la silueta.
- Colores naturales según el objeto (por ejemplo, madera marrón, planta verde, tinta morada, rosa rosa, dorado para detalles metálicos), usando solo los tokens de `resources/css/app.css`.
- Sin hex sueltos en el componente; todos los colores se referencian como `var(--color-*)`.

## 5. Silueta troquelada

- Cada sticker tiene un path cerrado propio que sigue el contorno del objeto, con una separación suficiente para crear el borde blanco característico de una pegatina troquelada.
- El path se dibuja con relleno `var(--color-crema)` detrás del arte.
- La silueta debe ser legible y no demasiado compleja: lo suficiente para reconocer el objeto, pero sin recargar el conjunto.
- El componente seguirá escalando la silueta con el prop `size`.

## 6. Estados

- **Desbloqueado:** silueta `crema` + arte detallado.
- **Bloqueado:** la misma silueta en `surface-raised` con opacidad reducida y un `?` en `ink-muted`; sin arte.
- **Tamaño:** el componente sigue aceptando `size` y ajustando el ancho/alto del `span`.

## 7. Accesibilidad

- El SVG se mantiene decorativo (`aria-hidden="true"`, `focusable="false"`).
- El nombre del sticker lo aporta el álbum, no el componente.
- El estado bloqueado no añade texto visible nuevo; el `?` es decorativo.

## 8. Documentación

- Actualizar el punto **Stickers de la máquina (`LibrarySticker`)** de `docs/DISENO.md` para describir:
  - Ilustración detallada y cohesiva.
  - Silueta troquelada propia por sticker.
  - Colores naturales con tokens existentes.
  - Estado bloqueado con la misma silueta.
- Añadir una fila al registro de cambios con fecha 2026-11-10.

## 9. Pruebas

- Actualizar `resources/js/Components/__tests__/LibrarySticker.test.js`:
  - Verificar que los 20 stickers renderizan arte y silueta.
  - Verificar que cada sticker usa su propia silueta (no una genérica compartida).
  - Verificar que el estado bloqueado usa la silueta del sticker y el `?`.
- Ejecutar:
  - `npm run test -- --run`
  - `npm run build`
  - `php artisan test` (el backend no cambia, pero se confirma que no se rompe nada).

## 10. Fuera de alcance

- No se rediseñan los 12 stickers de eventos.
- No se cambia el layout del álbum ni de la máquina.
- No se añaden colores nuevos ni se modifican tokens.
- No se cambian claves de configuración ni de idioma.
