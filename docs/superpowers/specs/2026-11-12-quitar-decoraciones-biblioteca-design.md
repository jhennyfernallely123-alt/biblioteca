# Biblioteca — × para quitar decoraciones en la Biblioteca

**Fecha:** 2026-11-12
**Estado:** Implementado y verificado.
**Documento de identidad visual:** `docs/DISENO.md` (documento vivo, de cumplimiento obligatorio).
**Reglas del proyecto para agentes:** `AGENTS.md`.

---

## 1. Objetivo

Permitir quitar una decoración directamente desde la **Biblioteca**, sin tener que ir al módulo Decoraciones.

Hoy las decoraciones de la Biblioteca se dibujan con `BookcaseDecoration`, que no tiene ninguna acción: para quitar una hay que entrar en Decoraciones y pulsar **Quitar** en la tarjeta del tipo correspondiente, lo que **elimina la última copia añadida** de ese tipo, no necesariamente la que se está mirando.

La × nueva se coloca en la esquina de cada decoración y la elimina de forma directa.

## 2. Decisiones aprobadas

- La × aparece solo en la **Biblioteca**. En **Estanterías** no se toca nada: su × actual se mantiene igual (visible al pasar el ratón, oculta en móvil).
- En **móvil la × se ve siempre**, porque no hay «hover». En **escritorio aparece al pasar el ratón**, con el mismo comportamiento que el botón de cambiar a lomo/portada.
- Al pulsar la × se **borra directamente, sin diálogo de confirmación**.
- La × **nunca arrastra**: el gesto de arrastre de la Biblioteca no se activa al pulsarla.
- No hay cambios de backend: la ruta y la autorización ya existen.

## 3. Alcance

### Incluido

- Botón **×** en `BookcaseDecoration.vue`, con el mismo aspecto que el botón de lomo/portada de la Biblioteca.
- Visibilidad: `opacity-0` en reposo en escritorio, visible al pasar el ratón y al recibir el foco con teclado; **siempre visible en móvil**.
- Borrado directo con `router.delete('/decorations/{id}')`.
- Pruebas Vitest del componente.
- Actualización de `docs/DISENO.md`.

### Excluido

- Cambios en `ShelfDecoration.vue` (Estanterías).
- Cambios en rutas, controladores, policies, modelos, migraciones o traducciones.
- Diálogo de confirmación antes de borrar.
- Deshacer el borrado.

## 4. Componente `BookcaseDecoration`

### 4.1 Estructura

El componente ya envuelve el dibujo en un contenedor con las clases `decoration-drag-source group relative shrink-0`. La × se añade como último hijo de ese contenedor, con estas clases:

```html
<button
    type="button"
    class="absolute -right-1 -top-1 z-10 flex h-7 w-7 items-center justify-center rounded-full border border-edge bg-surface-raised text-ink shadow-card transition-opacity focus-visible:opacity-100 sm:opacity-0 sm:group-hover:opacity-100 sm:focus-visible:opacity-100"
    :aria-label="t('ui.decoration_remove')"
    :title="t('ui.decoration_remove')"
    data-decoration-remove
    @pointerdown.stop
    @touchstart.stop
    @click.prevent.stop="remove"
>
    <svg class="h-3.5 w-3.5" viewBox="0 0 14 14" fill="none" aria-hidden="true">
        <path d="M3.5 3.5l7 7m0-7l-7 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
    </svg>
</button>
```

### 4.2 Visibilidad

El patrón de clases es el mismo que usa el botón de lomo/portada en `BookcaseBand.vue`:

| Estado | Escritorio (`sm` y superior) | Móvil (`< sm`) |
|---|---|---|
| Reposo | `sm:opacity-0` → invisible | visible siempre |
| Ratón encima | `sm:group-hover:opacity-100` | visible siempre |
| Foco con teclado | `sm:focus-visible:opacity-100` | visible siempre |

La diferencia con la × de Estanterías es que esta **no** lleva `hidden ... sm:flex`: en móvil se muestra.

### 4.3 Por qué la × no arrastra

`useLibraryDrag` escucha `pointerdown` en el contenedor `.bookcase` y busca un ascendiente con `[data-book-id]` o `[data-decoration-id]`. Como el botón vive dentro del contenedor de la decoración, un `pointerdown` sobre él encontraría igualmente el elemento arrastrable.

Por eso el botón corta la propagación con `@pointerdown.stop` y `@touchstart.stop`, igual que hace el botón de lomo/portada. Como no se llega a `onPointerDown`, nunca se crea un candidato de arrastre y la decoración no se mueve.

El `@click.prevent.stop` sigue el patrón del botón de lomo/portada: evita la acción por defecto del enlace y que el clic siga subiendo. El que impide que un arrastre termine en un borrado es `onClickCapture`, que la página registra en **fase de captura** sobre `.bookcase` (`.bookcase` es ancestro del botón, así que su escuchador se ejecuta antes que el del botón): tras un arrastre, `suppressClick` queda en `true` durante `CLICK_SUPPRESSION_MS` y ese manejador se traga el clic con `preventDefault()` y `stopPropagation()`. Por tanto, soltar un arrastre justo encima de la × no borra nada.

### 4.4 Comportamiento del borrado

```js
function remove() {
    router.delete(`/decorations/${props.decoration.id}`, {
        preserveScroll: true,
        preserveState: true,
    });
}
```

La ruta `DELETE /decorations/{decoration}` ya existe en `routes/web.php` y `DecorationController::destroy` ya aplica `authorize('delete', $decoration)`, que comprueba `$user->id === $decoration->user_id`. El servidor responde con `back()->with('success', __('ui.decoration_removed', ...))`, que la Biblioteca muestra con el aviso verde ya existente.

`preserveState: true` mantiene el estado local de la página. El re-render de Inertia repone `props.decorations` y el `watch` de `Library/Index.vue` (líneas 41-46) actualiza `localDecorations`, de modo que la decoración desaparece de la balda sin recargar la página a mano. Es el mismo mecanismo que ya usa Estanterías con `props.shelves`.

### 4.5 Textos

Se reutiliza la clave existente `ui.decoration_remove` ("Quitar objeto" / "Remove object"). **No se añaden claves nuevas** a `lang/es.json` ni `lang/en.json`.

## 5. Colores y tokens

No se introducen colores, tipografías, sombras ni radios nuevos. Se usan los tokens existentes: `edge`, `surface-raised`, `ink`, `shadow-card`, `rounded-full` y `h-7 w-7`, idénticos a los del botón de lomo/portada.

## 6. Accesibilidad

- El botón es un `<button type="button">` con `aria-label` y `title` en el nombre traducido del objeto.
- Es alcanzable con teclado: el `focus-visible` lo saca del estado invisible.
- `aria-hidden="true"` en el SVG, que es decorativo.
- El texto alternativo del objeto (`role="img"` con `aria-label`) no cambia.

## 7. Pruebas

### 7.1 Vitest

`resources/js/Components/__tests__/BookcaseDecoration.test.js` (nuevo), siguiendo el patrón de `ShelfDecoration.test.js`:

- La × existe con su `aria-label` traducido ("Quitar objeto", tomado de `lang/es.json` por el respaldo de `useI18n`).
- Al pulsarla se llama a `router.delete('/decorations/{id}', { preserveScroll: true, preserveState: true })`.
- La × lleva `data-decoration-remove` y las clases `sm:opacity-0` y `sm:group-hover:opacity-100` que la mantienen oculta en escritorio.
- **La × no propaga el `pointerdown`**: se registra un escuchador `pointerdown` en el elemento raíz del componente y se comprueba que, al pulsar el **dibujo**, el escuchador se dispara una vez (control: el evento sí burbujea) y que, al pulsar la **×**, el contador **sigue en uno** (la × corta la propagación). Es la comprobación directa de que el botón no arranca el arrastre.

El módulo de Inertia se mockea como en `ShelfDecoration.test.js`: `vi.mock('@inertiajs/vue3', ...)` con `router: { delete: vi.fn() }`.

No hay pruebas backend nuevas: la ruta y la autorización no cambian. La suite Pest existente (`DecorationTest`) sigue siendo la red de seguridad.

**Prueba de regresión añadida tras el diagnóstico:** `DecorationTest` incluye ahora un caso que reproduce el flujo completo del navegador ante un borrado con `router.delete`: la respuesta `DELETE` debe ser `303`, y la petición `GET` que sigue a esa redirección debe devolver **JSON de Inertia** (`X-Inertia: true`, `Content-Type: application/json`) con `component` `Library/Index`, el aviso de éxito en `props.flash.success` y `props.decorations` ya sin el objeto quitado. Cubre que el borrado no degrade a una recarga de página completa, que es lo que dejaba la pantalla en blanco.

Para que esa prueba funcione hacen falta las cabeceras `X-Inertia: true` y `X-Inertia-Version` con la versión vigente (`hash_file('xxh128', public/build/manifest.json)`); sin la versión, Inertia responde `409` con `X-Inertia-Location`, que el cliente interpreta como "location visit" y recarga la página entera.

### 7.2 Comprobaciones manuales

- A 360 px: la × se ve sobre cada decoración sin tapar el dibujo.
- En escritorio: la × no se ve hasta pasar el ratón por la decoración.
- Con teclado: al tabular, la × aparece y se puede pulsar con Enter.
- Al borrar, la decoración desaparece de la balda y sale el aviso verde.
- Intentar arrastrar la decoración por su × no mueve nada.
- Arrastrar una decoración y soltarla justo encima de otra × no borra esa decoración.

## 8. Documentación visual

En `docs/DISENO.md`:

- En la sección **Biblioteca completa (`Bookcase` + `BookcaseBand`)**, la frase *"Las decoraciones usan `BookcaseDecoration`, sin botón de borrar"* se sustituye por la descripción de la nueva ×.
- En la sección **Objeto decorativo (`DecorationArt` + `ShelfDecoration`)**, se aclara que la regla de móvil sin × se aplica a Estanterías y que la Biblioteca sí la muestra en móvil.
- Se añade una fila con fecha **2026-11-12** al registro de cambios.

## 9. Riesgos y consideraciones

- **Tocar por accidento:** en móvil la × está siempre visible, así que un toque cerca de la esquina borra la decoración sin aviso previo. Es el comportamiento pedido y el mismo que ya tiene el botón de lomo/portada. Quien quiera quitar decoraciones en móvil puede seguir usando el módulo Decoraciones, que **Quitar** elimina la última copia del tipo.
- **Colisión de posiciones:** la × se sitúa en `-right-1 -top-1`, la misma esquina que el botón de lomo/portada. En la Biblioteca las decoraciones y los libros no se solapan, así que ambas esquinas quedan libres.
- **Borrado de la copia correcta:** la × borra la decoración concreta que muestra, no la última del tipo. Es un cambio de semántica respecto al botón **Quitar** del módulo Decoraciones, y es intencionado.
