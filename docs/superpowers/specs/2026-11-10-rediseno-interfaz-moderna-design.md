# Rediseño de interfaz — Biblioteca digital moderna

**Fecha:** 2026-11-10  
**Estado:** Pendiente de revisión de la usuaria  
**Alcance:** Rediseño visual de la interfaz de usuario (frontend) sin modificar backend, rutas ni permisos.

## 1. Objetivo

Transformar la interfaz actual en una biblioteca digital moderna, elegante y mágica: tarjetas con profundidad y sombras suaves, fondos con degradados elegantes, animaciones fluidas al navegar, menú lateral compacto, botones con efectos interactivos, indicador de progreso de lectura atractivo, tipografías jerárquicas y diseño responsive para 360 px, tablet y escritorio.

## 2. Decisiones de diseño

| Decisión | Elección |
|---|---|
| Framework frontend | Vue 3 + Inertia.js (existente) |
| Estilos | Tailwind CSS 4 con tokens existentes (`@theme`) |
| Componentes base | Propios en `resources/js/Components/ui/` (no shadcn-vue completo) |
| Animaciones | `@vueuse/motion` (nativo Vue 3, sin React) |
| Tipografías | Inter (UI), Cormorant Garamond (títulos y detalles literarios) |
| Colores | Tokens existentes de `resources/css/app.css`; ningún hex suelto |
| Estilo visual | Biblioteca antigua moderna: crema, madera, verdes y tonos tierra con acentos rosas/dorados |

## 3. Componentes nuevos (`resources/js/Components/ui/`)

- `ui-button.vue` — variantes `primary`, `secondary`, `ghost`, `danger` con estados hover/active.
- `ui-input.vue` — campo de texto con etiqueta flotante y anillo de foco.
- `ui-card.vue` — tarjeta con borde `edge`, sombra suave, hover elevado.
- `ui-dialog.vue` — modal con velo oscuro y cierre con Escape.
- `ui-progress.vue` — barra y versión circular para progreso de lectura.

Todos usan los tokens CSS existentes (`--color-page`, `--color-surface`, `--color-accent`, `--color-tertiary`, `--color-danger`, `--color-edge`, etc.). No se añaden colores nuevos.

## 4. Páginas afectadas

- `resources/js/Pages/Library/Index.vue` — tarjetas de libro con profundidad y efecto hover.
- `resources/js/Pages/Settings/Index.vue` — inputs con etiqueta flotante y botones actualizados.
- `resources/js/Pages/Cards/Index.vue` — máquina y sobre mantienen su diseño; solo ajustes menores de consistencia.
- `resources/js/Pages/Cards/Album.vue` — rejilla de stickers/cartas con tarjetas tipo sobre cerrado.
- `resources/js/Pages/Events/Index.vue` y `resources/js/Pages/Events/Album.vue` — tarjetas de evento con estilo consistente.
- `resources/js/Components/AppShell.vue` — barra lateral compacta y menú móvil con íconos + etiquetas.
- `resources/js/Components/BottomNav.vue` — pestañas inferiores con estados activos.

## 5. Estilos y efectos

- **Tarjetas:** fondo `surface`, borde `edge`, radio 8px, sombra `0 1px 2px 0 rgb(0 0 0 / 0.05)`; hover: sombra `0 10px 15px -3px rgb(0 0 0 / 0.1)` y borde `accent`.
- **Botones:** `primary` (fondo `accent`, texto blanco), `secondary` (fondo `surface`, borde `edge`), `ghost` (texto `accent`), `danger` (fondo `danger`). Hover: escala 1.02 y cambio de tono.
- **Fondo de página:** `page` (crema) con degradado radial sutil en esquinas.
- **Efectos:** transición de 200ms en hover, `fade-in-up` al montar componentes, fade cruzado al cambiar de página con Inertia.

## 6. Animaciones (`@vueuse/motion`)

- Montaje: entrada `fade-in-up` (opacidad 0 → 1, desplazamiento vertical 10px).
- Cambio de página: fade cruzado con `InertiaLink`.
- Hover en tarjetas y botones: `scale(1.02)` con transición suave.

## 7. Responsive

- **360 px:** columna única, menú lateral colapsable, tarjetas de ancho completo, navegación inferior visible.
- **Tablet (768 px):** rejilla de 2 columnas, menú lateral reducido.
- **Escritorio (1280 px):** rejilla de 4–5 columnas, menú lateral fijo, navegación inferior oculta.

## 8. Archivos a crear

- `resources/js/Components/ui/ui-button.vue`
- `resources/js/Components/ui/ui-input.vue`
- `resources/js/Components/ui/ui-card.vue`
- `resources/js/Components/ui/ui-dialog.vue`
- `resources/js/Components/ui/ui-progress.vue`

## 9. Archivos a modificar

- `resources/css/app.css` — nuevos tokens de sombra y borde.
- `resources/js/Components/AppShell.vue` — barra lateral compacta.
- `resources/js/Components/BottomNav.vue` — estados activos y pestañas.
- `resources/js/Pages/Library/Index.vue`
- `resources/js/Pages/Settings/Index.vue`
- `resources/js/Pages/Cards/Index.vue` (ajustes menores)
- `resources/js/Pages/Cards/Album.vue`
- `resources/js/Pages/Events/Index.vue`
- `resources/js/Pages/Events/Album.vue`
- `docs/DISENO.md` — sección de interfaz moderna y registro de cambios.

## 10. Pruebas

- `npm run test -- --run` (frontend, 177 pruebas existentes + nuevas si se añaden).
- `npm run build` (compilación de producción).
- `php artisan test` (backend, no debe romperse).
- Verificación manual de responsive en 360 px y escritorio.

## 11. Fuera de alcance

- No se modifica el backend, ni rutas, ni permisos, ni lógica de negocio.
- No se instala `shadcn-vue` completo ni `framer-motion` (React).
- No se cambia el catálogo de idiomas ni se añaden claves nuevas salvo las estrictamente necesarias para la nueva interfaz.
