# × para quitar decoraciones en la Biblioteca — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Permitir quitar una decoración directamente desde la Biblioteca con una × en su esquina, visible siempre en móvil y al pasar el ratón en escritorio.

**Architecture:** Se añade el botón al componente `BookcaseDecoration.vue`, que es el que dibuja las decoraciones en la Biblioteca. El borrado reutiliza la ruta y la policy que ya existen (`DELETE /decorations/{id}` con `DecorationPolicy::delete`), así que no hay cambios de backend, rutas, base de datos ni traducciones. La × corta la propagación del `pointerdown` para que nunca active el arrastre.

**Tech Stack:** Vue 3 (script setup), Inertia.js, Tailwind CSS 4, Vitest, Vue Test Utils.

---

## Reglas de ejecución

- **La usuaria hace los commits.** No ejecutar `git add`, `git commit`, `git push` ni `git tag`. Al final de cada tarea, detenerse e informar de los archivos tocados para que la usuaria revise y commitee.
- No añadir colores, tipografías, sombras ni radios nuevos. Usar los tokens de `resources/css/app.css` y los componentes existentes.
- Todo texto visible nuevo va en `lang/es.json` y `lang/en.json`; en esta tarea **no hay texto nuevo**: se reutiliza `ui.decoration_remove`.
- No usar `rg` en PowerShell 5.1. Para leer archivos, usar las herramientas de lectura del agente.
- `docs/DISENO.md` está en UTF-8 con acentos y símbolos (×, «»). PowerShell 5.1 los muestra mal en consola; usar las herramientas de lectura y edición del agente en lugar de `Set-Content`, para no corromper el archivo.
- Mantener compatibilidad con InfinityFree: sin Node.js en el servidor, sin `storage:link`, sin colas ni workers.

## Mapa de archivos

**Crear**

- `resources/js/Components/__tests__/BookcaseDecoration.test.js` — pruebas del componente.

**Modificar**

- `resources/js/Components/BookcaseDecoration.vue` — añade el botón × y la función `remove`.
- `docs/DISENO.md` — actualiza la sección de la Biblioteca y la del objeto decorativo; añade la fila del registro de cambios.

---

### Task 1: Botón × en la Biblioteca

**Files:**
- Create: `resources/js/Components/__tests__/BookcaseDecoration.test.js`
- Modify: `resources/js/Components/BookcaseDecoration.vue`

- [x] **Step 1: Write the failing test**

Create `resources/js/Components/__tests__/BookcaseDecoration.test.js`:

```js
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/vue3';
import BookcaseDecoration from '../BookcaseDecoration.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal();

    return { ...original, router: { delete: vi.fn() } };
});

function makeDecoration(overrides = {}) {
    return { id: 9, kind: 'cup', color: 'rose', shelf_id: 7, after_book_id: 3, sort: 0, ...overrides };
}

describe('BookcaseDecoration', () => {
    it('se identifica para el arrastre y anuncia su nombre', () => {
        const wrapper = mount(BookcaseDecoration, { props: { decoration: makeDecoration() } });

        expect(wrapper.find('[data-decoration-id="9"]').exists()).toBe(true);
        expect(wrapper.find('[role="img"]').attributes('aria-label')).toBe('Taza');
    });

    it('atenúa el objeto mientras se arrastra', () => {
        const wrapper = mount(BookcaseDecoration, {
            props: { decoration: makeDecoration(), dragging: true },
        });

        expect(wrapper.classes()).toContain('opacity-30');
    });

    it('ofrece la × para quitar el objeto', () => {
        const wrapper = mount(BookcaseDecoration, { props: { decoration: makeDecoration() } });

        const button = wrapper.find('[data-decoration-remove]');

        expect(button.exists()).toBe(true);
        expect(button.attributes('aria-label')).toBe('Quitar objeto');
    });

    it('mantiene la × oculta en escritorio y visible en móvil', () => {
        const wrapper = mount(BookcaseDecoration, { props: { decoration: makeDecoration() } });

        const classes = wrapper.find('[data-decoration-remove]').classes();

        expect(classes).toContain('sm:opacity-0');
        expect(classes).toContain('sm:group-hover:opacity-100');
        expect(classes).not.toContain('hidden');
    });

    it('quita el objeto con la ×', async () => {
        const wrapper = mount(BookcaseDecoration, { props: { decoration: makeDecoration() } });

        await wrapper.find('[data-decoration-remove]').trigger('click');

        expect(router.delete).toHaveBeenCalledWith('/decorations/9', {
            preserveScroll: true,
            preserveState: true,
        });
    });

    it('no deja que la × arranque el arrastre', async () => {
        const wrapper = mount(BookcaseDecoration, { props: { decoration: makeDecoration() } });

        const onPointerDown = vi.fn();

        wrapper.element.addEventListener('pointerdown', onPointerDown);

        await wrapper.find('[role="img"]').trigger('pointerdown');

        expect(onPointerDown).toHaveBeenCalledTimes(1);

        await wrapper.find('[data-decoration-remove]').trigger('pointerdown');

        expect(onPointerDown).toHaveBeenCalledTimes(1);
    });
});
```

- [x] **Step 2: Run test to verify it fails**

Run:

```bash
npm run test -- BookcaseDecoration.test.js
```

Expected: FAIL because `BookcaseDecoration.vue` has no `[data-decoration-remove]` button.

- [x] **Step 3: Implement the remove button**

Replace `resources/js/Components/BookcaseDecoration.vue` with:

```vue
<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import DecorationArt from './DecorationArt.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    decoration: { type: Object, required: true },
    dragging: { type: Boolean, default: false },
    dropPosition: { type: String, default: null },
});

const { t } = useI18n();

const name = computed(() => t(`ui.decoration_${props.decoration.kind}`));

function remove() {
    router.delete(`/decorations/${props.decoration.id}`, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <div
        class="decoration-drag-source group relative shrink-0"
        :data-decoration-id="decoration.id"
        :class="{
            'opacity-30': dragging,
            'book-drop-before': dropPosition === 'before',
            'book-drop-after': dropPosition === 'after',
        }"
    >
        <div
            class="book-glow flex h-16 w-14 cursor-grab items-end justify-center sm:h-20 sm:w-16"
            role="img"
            :aria-label="name"
            :title="name"
        >
            <DecorationArt :kind="decoration.kind" :color="decoration.color" class="h-full w-full" />
        </div>

        <button
            type="button"
            class="absolute -right-1 -top-1 z-10 flex h-7 w-7 items-center justify-center rounded-full border border-edge bg-surface-raised text-ink shadow-card transition-opacity focus-visible:opacity-100 sm:opacity-0 sm:group-hover:opacity-100 sm:focus-visible:opacity-100"
            data-decoration-remove
            :aria-label="t('ui.decoration_remove')"
            :title="t('ui.decoration_remove')"
            @pointerdown.stop
            @touchstart.stop
            @click.prevent.stop="remove"
        >
            <svg class="h-3.5 w-3.5" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                <path d="M3.5 3.5l7 7m0-7l-7 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            </svg>
        </button>
    </div>
</template>
```

Notas de implementación:

- El atributo `data-decoration-remove` es el gancho que usan las pruebas.
- `@pointerdown.stop` y `@touchstart.stop` cortan la propagación para que la × no active el arrastre de `useLibraryDrag`, que busca un ascendiente con `[data-decoration-id]`.
- El contenedor ya tiene la clase `group`, así que `sm:group-hover:opacity-100` funciona sin tocarlo.
- **No** se añade `hidden ... sm:flex` (a diferencia de la × de `ShelfDecoration.vue`): en móvil la × debe verse siempre.
- No se añaden claves de traducción: se reutiliza `ui.decoration_remove`, que ya existe en `lang/es.json` y `lang/en.json`.

- [x] **Step 4: Run test to verify it passes**

Run:

```bash
npm run test -- BookcaseDecoration.test.js
```

Expected: PASS, 6 tests.

- [x] **Step 5: Run the affected frontend suites**

Run:

```bash
npm run test -- ShelfDecoration.test.js BookcaseBand.test.js Shelf.test.js
```

Expected: PASS. La × de `ShelfDecoration.vue` no se toca, así que estas pruebas deben seguir igual.

- [x] **Step 6: Pause for review**

Inform the user of the files created/modified. The user reviews and commits.

---

### Task 2: Documentación visual

**Files:**
- Modify: `docs/DISENO.md`

- [x] **Step 1: Update the Library section**

In `docs/DISENO.md`, in the section **Biblioteca completa (`Bookcase` + `BookcaseBand`)**, the final sentence currently reads:

> Las decoraciones usan `BookcaseDecoration`, sin botón de borrar.

Replace it with:

> Las decoraciones usan `BookcaseDecoration`, que lleva una **×** en la esquina superior derecha para quitar el objeto: en **escritorio** aparece al pasar el ratón y en **móvil** se ve siempre, con el mismo comportamiento que el botón de cambiar a lomo/portada. Al pulsarla borra la decoración sin confirmación y sin activar el arrastre.

- [x] **Step 2: Update the decoration section**

In the section **Objeto decorativo (`DecorationArt` + `ShelfDecoration`)**, the bullet currently reads:

> - Al pasar el ratón aparece una **×** circular arriba a la derecha (solo escritorio) para quitarlo; en móvil se quita desde Ajustes.

Replace it with:

> - Al pasar el ratón aparece una **×** circular arriba a la derecha para quitarlo. En **Estanterías** la × solo existe en escritorio y en móvil se quita desde el módulo Decoraciones; en **Biblioteca** la × se ve siempre en móvil.

- [x] **Step 3: Add the changelog row**

In the table at the end of `docs/DISENO.md`, append a row:

```markdown
| 2026-11-12 | Las decoraciones de la **Biblioteca** admiten quitarse con una **×** en la esquina superior derecha, con el mismo aspecto que el botón de cambiar a lomo/portada: en escritorio aparece al pasar el ratón y en móvil se ve siempre. Al pulsarla borra el objeto sin confirmación y sin activar el arrastre. En **Estanterías** la × no cambia (solo escritorio). Sin colores, tipografías, sombras ni radios nuevos. | Agente |
```

- [x] **Step 4: Verify the changelog row is present**

Run:

```powershell
Select-String -Path docs\DISENO.md -Pattern '2026-11-12'
```

Expected: at least the new row about the decorations × (the catalog master rows from the previous feature are also dated 2026-11-12).

- [x] **Step 5: Pause for review**

Inform the user of the files modified. The user reviews and commits.

---

### Task 3: Verificación completa

**Files:**
- No se modifican archivos.

- [x] **Step 1: Run the complete frontend suite**

Run:

```bash
npm run test
```

Expected: PASS, all tests (the previous suite had 185 tests in 46 files; now 46 + 1 files and 185 + 6 tests).

- [x] **Step 2: Run the production build**

Run:

```bash
npm run build
```

Expected: build completes successfully.

- [x] **Step 3: Run the backend suite**

Run:

```bash
php artisan test
```

Expected: PASS. No backend changed, so the suite must stay at 238 passed.

- [x] **Step 4: Check the working tree for whitespace errors**

Run:

```bash
git diff --check
```

Expected: only the pre-existing CRLF warnings for `docs/DISENO.md`, `lang/es.json`, `lang/en.json` and `resources/css/textures.css`.

- [x] **Step 5: Manual acceptance checks**

Ask the user to verify in the browser:

- [x] A 360 px: la × se ve sobre cada decoración de la Biblioteca sin tapar el dibujo.
- [x] En escritorio: la × no se ve hasta pasar el ratón por la decoración.
- [x] Con teclado: al tabular, la × aparece y se puede pulsar con Enter.
- [x] Al borrar, la decoración desaparece de la balda y sale el aviso verde «Taza se quitó de la biblioteca».
- [x] Intentar arrastrar la decoración por su × no mueve nada.
- [x] Arrastrar una decoración y soltarla encima de otra × no borra esa decoración.
- [x] En **Estanterías** la × sigue igual: aparece al pasar el ratón y no se ve en móvil.

- [x] **Step 6: Report and hand off**

Inform the user of every file created and modified, the verification output, and any manual checks pending. The user reviews and commits.

---

## Self-review

- **Cobertura del spec:** La sección 4.1 (estructura del botón) y la 4.2 (visibilidad) se implementan en la Task 1, Step 3. La 4.3 (no arrastra) se cubre con `@pointerdown.stop` y con el test de no-propagación. La 4.4 (borrado) con `router.delete` y su test. La 4.5 (textos) reutiliza `ui.decoration_remove`, sin claves nuevas. La sección 5 (tokens) se respeta usando las mismas clases del botón lomo/portada. La sección 6 (accesibilidad) con `aria-label`, `title` y `aria-hidden` en el SVG. La sección 7.1 (Vitest) con los 6 tests de la Task 1; la 7.2 (comprobaciones manuales) con la Task 3, Step 5. La sección 8 (documentación) con la Task 2. La sección 9 (riesgos) está cubierta: el aviso de "tocar por accidento" se leen a la usuaria en el informe final.
- **Alcance:** una sola tarea de código y una de documentación. No se toca `ShelfDecoration.vue` (excluido en el spec), ni rutas, controladores, modelos, migraciones ni traducciones.
- **Placeholder scan:** sin TBD, TODO ni pasos sin código. Cada paso de código incluye el bloque completo.
- **Coherencia de tipos:** `props.decoration.id` es el id que se usa en la URL; `data-decoration-remove` es el gancho que se usa en el test y en el componente; `ui.decoration_remove` es la clave de traducción en ambos. `router.delete` con `{ preserveScroll: true, preserveState: true }` es idéntico al patrón de `ShelfDecoration.vue`.
- **Verificación previa a la implementación:** `npm run test` da 185 tests en 46 archivos y `php artisan test` da 238 passed. Los números esperados de este plan salen de esa línea base.
