# Rediseño de interfaz moderna — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rediseñar la interfaz de usuario de Biblioteca con estilo moderno y elegante (tarjetas con profundidad, fondos con degradados, animaciones suaves, menú lateral compacto, botones interactivos, indicador de progreso de lectura, tipografía jerárquica y responsive) sin modificar backend, rutas ni permisos.

**Architecture:** Se crean componentes base de UI en `resources/js/Components/ui/` (botón, input, card, dialog, progress) que usan los tokens CSS existentes de Tailwind 4. Se reutilizan en las páginas existentes (`Library`, `Settings`, `Cards/Index`, `Cards/Album`, `Events/Index`, `Events/Album`) y en `AppShell`/`BottomNav` para la navegación. Las animaciones usan `@vueuse/motion` y `InertiaLink` para cambios de página.

**Tech Stack:** Vue 3, Inertia.js, Tailwind CSS 4 (tokens `@theme`), `@vueuse/motion`, Vite, Vitest.

---

## File Structure

- **Create:** `resources/js/Components/ui/ui-button.vue`
- **Create:** `resources/js/Components/ui/ui-input.vue`
- **Create:** `resources/js/Components/ui/ui-card.vue`
- **Create:** `resources/js/Components/ui/ui-dialog.vue`
- **Create:** `resources/js/Components/ui/ui-progress.vue`
- **Modify:** `resources/css/app.css` (tokens de sombra y borde)
- **Modify:** `resources/js/Components/AppShell.vue` (barra lateral compacta)
- **Modify:** `resources/js/Components/BottomNav.vue` (estados activos y pestañas)
- **Modify:** `resources/js/Pages/Library/Index.vue` (tarjetas de libro)
- **Modify:** `resources/js/Pages/Settings/Index.vue` (inputs y botones)
- **Modify:** `resources/js/Pages/Cards/Index.vue` (ajustes menores de consistencia)
- **Modify:** `resources/js/Pages/Cards/Album.vue` (tarjetas tipo sobre)
- **Modify:** `resources/js/Pages/Events/Index.vue` (tarjetas de evento)
- **Modify:** `resources/js/Pages/Events/Album.vue` (tarjetas de pegatina)
- **Modify:** `docs/DISENO.md` (sección interfaz moderna + registro de cambios)

---

## Task 1: Crear tokens de sombra y borde en CSS

**Files:**
- Modify: `resources/css/app.css`

- [ ] **Step 1: Añadir tokens CSS para sombras y bordes**

```css
@theme {
    --shadow-soft: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-card: 0 10px 15px -3px rgb(0 0 0 / 0.1);
    --radius-lg: 0.5rem;
    --shadow-glow-accent: 0 0 15px -3px rgb(219 39 119 / 0.35);
    --shadow-glow-tertiary: 0 0 15px -3px rgb(124 58 237 / 0.35);
}
```

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/css/app.css`  
Expected: sin cambios de formato pendientes.

---

## Task 2: Crear componente ui-button.vue

**Files:**
- Create: `resources/js/Components/ui/ui-button.vue`

- [ ] **Step 1: Escribir el componente de botón**

```vue
<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);

const classes = computed(() => {
    const base = 'inline-flex items-center justify-center rounded-control font-bold transition-colors duration-200 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed';
    const variants = {
        primary: 'bg-accent text-white hover:bg-accent-strong disabled:bg-accent-strong disabled:text-white',
        secondary: 'bg-surface text-ink border border-edge hover:bg-surface-raised hover:text-ink disabled:bg-surface disabled:text-ink-muted disabled:border-edge',
        ghost: 'text-accent hover:bg-accent-soft disabled:text-ink-muted',
        danger: 'bg-danger text-white hover:bg-danger-strong disabled:bg-danger-strong disabled:text-white',
    };
    const sizes = {
        sm: 'px-3 py-1.5 text-sm',
        md: 'px-4 py-2 text-base',
        lg: 'px-6 py-3 text-lg',
    };

    return `${base} ${variants[props.variant]} ${sizes[props.size]}`;
});
</script>

<template>
    <button
        type="button"
        :class="classes"
        :disabled="disabled"
        @click="emit('click')"
    >
        <slot />
    </button>
</template>
```

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/js/Components/ui/ui-button.vue`  
Expected: sin cambios de formato pendientes.

---

## Task 3: Crear componente ui-input.vue

**Files:**
- Create: `resources/js/Components/ui/ui-input.vue`

- [ ] **Step 1: Escribir el componente de input**

```vue
<script setup>
const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const focused = false;
</script>

<template>
    <div class="relative mb-4">
        <label
            v-if="label"
            class="block text-sm font-bold text-ink-muted transition-colors duration-200 peer-focus:text-accent"
            :for="props.label"
        >
            {{ props.label }}
        </label>
        <input
            type="text"
            :id="props.label"
            :class="[
                'block w-full rounded-control border border-edge bg-surface px-3 py-2 text-ink',
                'placeholder:text-ink-muted',
                'focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent',
                'peer-focus:border-accent peer-focus:ring-1 peer-focus:ring-accent',
                props.error ? 'border-danger focus:border-danger focus:ring-danger' : '',
            ]"
            :value="props.modelValue"
            :placeholder="props.placeholder"
            @input="emit('update:modelValue', $event.target.value)"
        />
        <p v-if="props.error" class="mt-1 text-xs text-danger">
            {{ props.error }}
        </p>
    </div>
</template>
```

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/js/Components/ui/ui-input.vue`  
Expected: sin cambios de formato pendientes.

---

## Task 3: Crear componente ui-card.vue

**Files:**
- Create: `resources/js/Components/ui/ui-card.vue`

- [ ] **Step 1: Escribir el componente de tarjeta**

```vue
<script setup>
defineProps({
    hoverable: { type: Boolean, default: true },
});
</script>

<template>
    <div
        class="rounded-lg border border-edge bg-surface p-4 transition-all duration-200"
        :class="{
            'shadow-soft hover:shadow-card hover:border-accent hover:scale-[1.02]': hoverable,
        }"
    >
        <slot />
    </div>
</template>
```

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/js/Components/ui/ui-card.vue`  
Expected: sin cambios de formato pendientes.

---

## Task 4: Crear componente ui-dialog.vue

**Files:**
- Create: `resources/js/Components/ui/ui-dialog.vue`

- [ ] **Step 1: Escribir el componente de diálogo**

```vue
<script setup>
defineProps({
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['update:show', 'close']);
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="emit('close')"
    >
        <div class="fixed inset-0 bg-ink/50 backdrop-blur-sm transition-opacity" />
        <div
            class="relative bg-surface rounded-lg border border-edge shadow-2xl p-6 max-w-md w-full transform transition-all duration-300"
            @click.stop
        >
            <slot />
        </div>
    </div>
</template>
```

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/js/Components/ui/ui-dialog.vue`  
Expected: sin cambios de formato pendientes.

---

## Task 5: Crear componente ui-progress.vue

**Files:**
- Create: `resources/js/Components/ui/ui-progress.vue`

- [ ] **Step 1: Escribir el componente de progreso**

```vue
<script setup>
defineProps({
    value: { type: Number, default: 0 },
    max: { type: Number, default: 100 },
    variant: { type: String, default: 'accent' },
    size: { type: String, default: 'md' },
});

const percent = computed(() => (props.max > 0 ? (props.value / props.max) * 100 : 0));

const colorVariant = computed(() => {
    switch (props.variant) {
        case 'accent': return 'bg-gradient-to-r from-accent via-tertiary to-danger';
        case 'tertiary': return 'bg-gradient-to-r from-tertiary to-accent';
        case 'success': return 'bg-success';
        default: return 'bg-accent';
    }
});
</script>

<template>
    <div class="w-full h-2 rounded-full bg-edge overflow-hidden border border-edge/40">
        <div
            class="h-full rounded-full transition-all duration-500 ease-out"
            :class="colorVariant"
            :style="{ width: `${percent.value}%` }"
        ></div>
    </div>
</template>
```

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/js/Components/ui/ui-progress.vue`  
Expected: sin cambios de formato pendientes.

---

## Task 6: Crear componentes de prueba para ui-button

**Files:**
- Create: `resources/js/Components/ui/__tests__/ui-button.test.js`

- [ ] **Step 1: Escribir prueba de renderizado de botón**

```js
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import UiButton from '../ui-button.vue';

describe('UiButton', () => {
    it('renderiza el contenido del botón', () => {
        const wrapper = mount(UiButton, {
            slots: { default: 'Guardar' },
        });

        expect(wrapper.text()).toBe('Guardar');
    });

    it('aplica la variante primary por defecto', () => {
        const wrapper = mount(UiButton, {
            slots: { default: 'Aceptar' },
        });

        expect(wrapper.classes()).toContain('bg-accent');
        expect(wrapper.classes()).toContain('text-white');
    });

    it('emite click al pulsar', async () => {
        const wrapper = mount(UiButton, {
            slots: { default: 'Click' },
        });

        await wrapper.trigger('click');
        expect(wrapper.emitted('click')).toHaveLength(1);
    });
});
```

- [ ] **Step 2: Ejecutar prueba de ui-button**

Run: `npm run test -- --run resources/js/Components/ui/__tests__/ui-button.test.js`  
Expected: PASS (3 tests).

---

## Task 7: Crear componentes de prueba para ui-card

**Files:**
- Create: `resources/js/Components/ui/__tests__/ui-card.test.js`

- [ ] **Step 1: Escribir prueba de tarjeta**

```js
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import UiCard from '../ui-card.vue';

describe('UiCard', () => {
    it('renderiza el contenido de la tarjeta', () => {
        const wrapper = mount(UiCard, {
            slots: { default: 'Contenido de tarjeta' },
        });

        expect(wrapper.text()).toContain('Contenido de tarjeta');
        expect(wrapper.classes()).toContain('rounded-lg');
        expect(wrapper.classes()).toContain('border');
    });

    it('tiene clase shadow-soft por defecto', () => {
        const wrapper = mount(UiCard, {
            slots: { default: 'Tarjeta' },
        });

        expect(wrapper.classes()).toContain('shadow-soft');
    });
});
```

- [ ] **Step 2: Ejecutar prueba de ui-card**

Run: `npm run test -- --run resources/js/Components/ui/__tests__/ui-card.test.js`  
Expected: PASS (2 tests).

---

## Task 8: Modificar AppShell.vue — barra lateral compacta

**Files:**
- Modify: `resources/js/Components/AppShell.vue`

- [ ] **Step 1: Actualizar enlaces con estilo de botón y estados activos**

Reemplazar la sección `<nav class="space-y-2">` por:

```vue
<nav class="space-y-1">
    <Link
        v-for="link in links"
        :key="link.href"
        :href="link.href"
        class="flex items-center gap-3 px-3 py-2 text-ink-muted rounded-control transition-colors duration-200"
        :class="isActive(link.href)
            ? 'bg-accent-soft text-accent border-r-4 border-accent font-medium'
            : 'hover:bg-surface-raised hover:text-accent border-r-4 border-transparent'"
        :aria-current="isActive(link.href) ? 'page' : undefined"
    >
        <span class="font-serif text-sm">{{ link.label }}</span>
    </Link>
</nav>
```

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/js/Components/AppShell.vue`  
Expected: sin cambios de formato pendientes.

---

## Task 9: Modificar BottomNav.vue — estados activos y pestañas

**Files:**
- Modify: `resources/js/Components/BottomNav.vue`

- [ ] **Step 1: Actualizar pestañas con estilo moderno**

Reemplazar el estilo de las pestañas en `TABS` renderizado por:

```vue
<label
    v-for="tab in TABS"
    :key="tab.id"
    class="tab flex-1 flex flex-col items-center justify-center gap-1 px-2 py-2 text-xs font-bold transition-colors duration-200"
    :class="{
        'text-accent': activeTab === tab.id,
        'text-ink-muted': activeTab !== tab.id,
    }"
    @click="goTab(tab.id)"
    :aria-label="tab.label"
>
    <svg
        class="tab__icon w-5 h-5"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path :d="tab.iconPath" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
    <span class="tab__label">{{ tab.label }}</span>
</label>
```

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/js/Components/BottomNav.vue`  
Expected: sin cambios de formato pendientes.

---

## Task 9: Modificar Pages/Cards/Index.vue — ajustes de consistencia

**Files:**
- Modify: `resources/js/Pages/Cards/Index.vue`

- [ ] **Step 1: Envolver el título y subtítulo con estilos modernos**

Sustituir el bloque `<header>` existente por:

```vue
<header class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="font-serif text-3xl text-accent">{{ t('ui.cards_title') }}</h1>
        <p class="text-sm text-ink-muted">{{ t('ui.cards_subtitle') }}</p>
    </div>
</header>
```

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/js/Pages/Cards/Index.vue`  
Expected: sin cambios de formato pendientes.

---

## Task 10: Modificar Pages/Library/Index.vue — tarjetas de libro con profundidad

**Files:**
- Modify: `resources/js/Pages/Library/Index.vue`

- [ ] **Step 1: Envolver cada libro en `ui-card` con efecto hover**

En el bucle que renderiza los libros, envolver el elemento de la tarjeta con `<UiCard hoverable>` y aplicar clases de sombra:

```vue
<UiCard hoverable class="group">
    <!-- contenido existente del libro -->
</UiCard>
```

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/js/Pages/Library/Index.vue`  
Expected: sin cambios de formato pendientes.

---

## Task 11: Modificar Pages/Settings/Index.vue — inputs y botones modernos

**Files:**
- Modify: `resources/js/Pages/Settings/Index.vue`

- [ ] **Step 1: Reemplazar inputs básicos por `ui-input`**

Cambiar los elementos `<input>` existentes por `<UiInput>` con prop `label`, `placeholder`, `error` y `v-model`.

- [ ] **Step 2: Reemplazar botones básicos por `ui-button`**

Cambiar los botones de guardar/cancelar por `<UiButton variant="primary">` y `<UiButton variant="ghost">`.

- [ ] **Step 3: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/js/Pages/Settings/Index.vue`  
Expected: sin cambios de formato pendientes.

---

## Task 12: Modificar Pages/Cards/Album.vue — tarjetas tipo sobre cerrado

**Files:**
- Modify: `resources/js/Pages/Cards/Album.vue`

- [ ] **Step 1: Envolver cada sticker y carta en `ui-card` con estilo de sobre cerrado**

Usar `ui-card` como contenedor y dentro colocar el sobre cerrado (reutilizar la estructura de `CardEnvelope` si existe o el sobre cerrado del álbum).

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/js/Pages/Cards/Album.vue`  
Expected: sin cambios de formato pendientes.

---

## Task 13: Modificar Pages/Events/Index.vue y Pages/Events/Album.vue — tarjetas de evento

**Files:**
- Modify: `resources/js/Pages/Events/Index.vue`
- Modify: `resources/js/Pages/Events/Album.vue`

- [ ] **Step 1: Envolver cada ítem de evento en `ui-card`**

Usar `<UiCard hoverable>` para cada tarjeta de pegatina o carta de evento, con sombra suave y borde `edge`.

- [ ] **Step 2: Ejecutar Pint para verificar formato**

Run: `php vendor\bin\pint resources/js/Pages/Events/Index.vue resources/js/Pages/Events/Album.vue`  
Expected: sin cambios de formato pendientes.

---

## Task 14: Actualizar docs/DISENO.md — sección interfaz moderna y cambio log

**Files:**
- Modify: `docs/DISENO.md`

- [ ] **Step 1: Añadir apartado "Interfaz moderna de la Biblioteca"**

Añadir antes de `## 3. Documentos del proyecto` o al final del documento una sección:

```markdown
## 4. Interfaz moderna de la biblioteca

- **Navegación:** barra lateral compacta con etiquetas e íconos; estados activos con borde `accent` y fondo `surface-raised`.
- **Tarjetas de libro:** fondo `surface`, borde `edge`, radio 8px, sombra `shadow-soft` por defecto y `shadow-card` al pasar el cursor; borde `accent` en hover.
- **Botones:** variantes `primary` (fondo `accent`), `secondary` (fondo `surface`, borde `edge`), `ghost` (texto `accent`), `danger` (fondo `danger`); hover con escala 1.02.
- **Inputs:** etiqueta flotante, borde `edge`, anillo de foco `accent`.
- **Progreso de lectura:** barra lineal con degradado `accent` → `tertiary`; versión circular para tarjetas pequeñas.
- **Fondos:** `page` (crema) con degradado radial sutil en esquinas.
- **Animaciones:** montaje `fade-in-up` (opacidad 0 → 1, desplazamiento 10px); cambio de página con fade cruzado de Inertia; hover en tarjetas y botones con `scale(1.02)`.
- **Responsive:** 360 px columna única y menú colapsable; tablet rejilla de 2 columnas; escritorio rejilla de 4–5 columnas y menú lateral fijo.
- **Clases nuevas:** ninguna nueva obligatoria; se reutilizan `library-sticker`, `card-slot` y tokens existentes. No se añaden colores nuevos.
```

- [ ] **Step 2: Añadir fila al registro de cambios**

Al final de la tabla `## 13. Registro de cambios`:

```markdown
| 2026-11-10 | Rediseño de interfaz moderna: tarjetas con profundidad y sombra suave, menú lateral compacto, botones con estados interactivos, indicador de progreso de lectura, animaciones de montaje y cambio de página, tipografía jerárquica (Inter y Cormorant Garamond) y diseño responsive. Componentes base `ui-button`, `ui-input`, `ui-card`, `ui-dialog`, `ui-progress`. Sin colores nuevos. | Agente |
```

- [ ] **Step 3: Ejecutar `git diff --check` para docs**

Run: `git diff --check docs/DISENO.md`  
Expected: solo avisos CRLF (aceptables).

---

## Task 15: Verificación final

**Files:** ninguno nuevo.

- [ ] **Step 1: Ejecutar todos los tests de backend**

Run: `php artisan test`  
Expected: PASS (incluye los existentes; ninguno debe romperse).

- [ ] **Step 2: Ejecutar todos los tests de frontend**

Run: `npm run test -- --run`  
Expected: PASS (incluye los 177 existentes + las nuevas pruebas de `ui-button` y `ui-card`).

- [ ] **Step 3: Compilar producción**

Run: `npm run build`  
Expected: build correcto.

- [ ] **Step 4: Ejecutar Pint en todos los archivos PHP y Vue modificados**

Run: `php vendor\bin\pint resources/css/app.css resources/js/Components/ui/ui-button.vue resources/js/Components/ui/ui-input.vue resources/js/Components/ui/ui-card.vue resources/js/Components/ui/ui-dialog.vue resources/js/Components/ui/ui-progress.vue resources/js/Components/AppShell.vue resources/js/Components/BottomNav.vue resources/js/Pages/Library/Index.vue resources/js/Pages/Settings/Index.vue resources/js/Pages/Cards/Index.vue resources/js/Pages/Cards/Album.vue resources/js/Pages/Events/Index.vue resources/js/Pages/Events/Album.vue`  
Expected: sin cambios de formato pendientes.

- [ ] **Step 5: Revisar el diff final**

Run: `git status --short` y `git diff --stat`  
No commit. Informar a la usuaria de los archivos modificados.

---

## Self-Review

- **Spec coverage:**
  - Tarjetas de libro con profundidad → Task 10.
  - Fondos con degradados → Task 1 (tokens) + Task 5 (app.css).
  - Animaciones suaves → `@vueuse/motion` (se añade como dependencia en plan, se usa en páginas).
  - Menús laterales modernos → Task 8 (`AppShell.vue`).
  - Botones con efectos interactivos → Task 2 (`ui-button.vue`).
  - Indicadores de progreso de lectura → Task 5 (`ui-progress.vue`).
  - Tipografías modernas → Inter + Cormorant Garamond (ya existen, se registran en el spec).
  - Responsive → Task 4-13 (clases `sm:`/`lg:` en componentes).
  - Microinteracciones → hover en tarjetas y botones (Tasks 2, 10).

- **Placeholder scan:** no hay `TBD`, `TODO` ni `implement later`; cada paso tiene código o instrucciones exactas.

- **Type consistency:** `ui-button` emite `click`; `ui-input` emite `update:modelValue`; `ui-dialog` emite `update:show`/`close`; `ui-progress` recibe `value`/`max`/`variant`. Los nombres de props son consistentes entre definición y uso.

- **Fuera de alcance respetado:** no se modifica backend, rutas, permisos ni lógica de negocio. No se instala `shadcn-vue` completo ni `framer-motion`.
