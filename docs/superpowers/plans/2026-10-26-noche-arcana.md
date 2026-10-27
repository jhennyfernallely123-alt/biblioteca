# Noche arcana — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Recolorear toda la aplicación a una noche mágica violeta, oro y turquesa, dejando intacto el mueble de las estanterías.

**Architecture:** Se añaden tokens nuevos e independientes en `@theme`; los tokens antiguos del mueble no se tocan. Cada pantalla y control se migra de las clases antiguas (`bg-crema`, `text-tinta`, `bg-oliva`, grises de Breeze) a las nuevas. Un test de Vitest congela los valores del mueble. Sin cambios de lógica, rutas ni textos.

**Tech Stack:** Laravel 13, Inertia, Vue 3, Tailwind CSS 4, Vitest.

**Commits:** No ejecutar `git add/commit`; según `AGENTS.md` los hace la usuaria. Cada tarea termina con verificación, no con commit.

---

### Task 1: Tokens y fondo mágico

**Files:**
- Create: `resources/js/Components/__tests__/palette.test.js`
- Modify: `resources/css/app.css`
- Modify: `resources/css/textures.css`

- [ ] **Step 1: Escribir el test que congela la paleta**

```js
import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const css = readFileSync(new URL('../../../css/app.css', import.meta.url), 'utf8');
const textures = readFileSync(new URL('../../../css/textures.css', import.meta.url), 'utf8');

const token = (name) => css.match(new RegExp(`--color-${name}:\\s*(#[0-9A-Fa-f]{6})`))?.[1];

describe('paleta noche arcana', () => {
    it('define los tokens nuevos', () => {
        expect(token('night')).toBe('#12101F');
        expect(token('night-raised')).toBe('#1A1730');
        expect(token('panel')).toBe('#211D3A');
        expect(token('panel-raised')).toBe('#2B2550');
        expect(token('line')).toBe('#3A3363');
        expect(token('moon')).toBe('#F1ECFF');
        expect(token('mist')).toBe('#B7AED6');
        expect(token('arcane')).toBe('#7C5CE0');
        expect(token('arcane-strong')).toBe('#6A4AC9');
        expect(token('magic')).toBe('#74D8C9');
        expect(token('gold')).toBe('#E8C887');
    });

    it('conserva los tokens del mueble', () => {
        expect(token('bosque')).toBe('#2F4A3C');
        expect(token('oliva')).toBe('#556B4F');
        expect(token('madera')).toBe('#6B4F3A');
        expect(token('madera-clara')).toBe('#8B6B4F');
        expect(token('crema')).toBe('#FAF4E6');
        expect(token('tinta')).toBe('#2B2620');
        expect(token('terracota')).toBe('#A9714B');
        expect(token('dorado')).toBe('#C2A878');
        expect(token('pergamino')).toBe('#F3E9D2');
    });

    it('mantiene la madera en la balda y añade el cielo arcano', () => {
        expect(textures).toContain('var(--color-madera-clara)');
        expect(textures).toContain('.arcane-sky');
    });
});
```

- [ ] **Step 2: Ejecutar el test y verlo fallar**

Run: `npm run test -- palette`
Expected: FAIL porque `night` no existe y `.arcane-sky` no está definido.

- [ ] **Step 3: Añadir tokens y base en `app.css`**

Dentro de `@theme`, después de `--color-dorado`, añadir:

```css
  --color-night: #12101F;
  --color-night-raised: #1A1730;
  --color-panel: #211D3A;
  --color-panel-raised: #2B2550;
  --color-line: #3A3363;
  --color-moon: #F1ECFF;
  --color-mist: #B7AED6;
  --color-arcane: #7C5CE0;
  --color-arcane-strong: #6A4AC9;
  --color-magic: #74D8C9;
  --color-magic-strong: #5FC7B8;
  --color-gold: #E8C887;
  --color-danger: #F2A0B0;
  --color-danger-strong: #A8455C;
  --color-success: #8FE3B8;
```

Sustituir el bloque `@layer base` por:

```css
@layer base {
  body {
    @apply bg-night text-moon font-sans antialiased;
  }

  h1, h2, h3 {
    @apply font-serif text-gold;
  }

  :focus-visible {
    @apply outline-none ring-2 ring-magic ring-offset-2 ring-offset-night;
  }
}
```

- [ ] **Step 4: Añadir `.arcane-sky` en `textures.css`**

Al principio del archivo, antes de `.paper-texture`:

```css
.arcane-sky {
  background-color: var(--color-night);
  background-image:
    radial-gradient(ellipse 70% 55% at 10% 0%, color-mix(in srgb, var(--color-arcane) 30%, transparent), transparent 70%),
    radial-gradient(ellipse 65% 50% at 90% 100%, color-mix(in srgb, var(--color-magic) 16%, transparent), transparent 70%),
    radial-gradient(circle at 18% 18%, color-mix(in srgb, var(--color-moon) 45%, transparent) 0 1px, transparent 1.6px),
    radial-gradient(circle at 72% 9%, color-mix(in srgb, var(--color-moon) 35%, transparent) 0 1px, transparent 1.6px),
    radial-gradient(circle at 34% 64%, color-mix(in srgb, var(--color-moon) 28%, transparent) 0 1px, transparent 1.6px),
    radial-gradient(circle at 86% 42%, color-mix(in srgb, var(--color-moon) 32%, transparent) 0 1px, transparent 1.6px),
    radial-gradient(circle at 58% 86%, color-mix(in srgb, var(--color-moon) 26%, transparent) 0 1px, transparent 1.6px);
  background-attachment: fixed;
  background-repeat: no-repeat;
  background-size: cover;
}
```

- [ ] **Step 5: Verificar tests y build**

Run: `npm run test -- palette`
Expected: PASS.

Run: `npm run build`
Expected: build correcto.

---

### Task 2: Navegación y acceso

**Files:**
- Modify: `resources/js/Components/AppShell.vue`
- Modify: `resources/js/Layouts/GuestLayout.vue`

- [ ] **Step 1: Migrar `AppShell.vue`**

Cambios exactos:

- `class="paper-texture min-h-screen md:flex"` → `class="arcane-sky min-h-screen md:flex"`
- `class="hidden bg-bosque p-4 text-crema md:flex md:w-60 md:flex-col"` → `class="hidden border-e border-line bg-night-raised/95 p-4 text-moon md:flex md:w-60 md:flex-col"`
- Logo `class="mb-8 font-serif text-2xl"` → `class="mb-8 font-serif text-2xl text-gold"`
- Enlaces `class="block rounded-control border-s-2 px-3 py-2 hover:bg-white/10"` → `class="block rounded-control border-s-2 px-3 py-2 text-mist hover:bg-panel hover:text-moon"`
- Activo `'border-dorado bg-white/10'` → `'border-magic bg-panel text-moon'`
- `class="mt-auto pt-6 text-sm text-crema/70"` → `class="mt-auto pt-6 text-sm text-mist"`
- Botón de salida `class="min-h-11 w-full rounded-control border border-crema/30 px-3 py-2 text-start hover:bg-white/10"` → `class="min-h-11 w-full rounded-control border border-line px-3 py-2 text-start text-moon hover:bg-panel"`
- Header móvil `class="flex items-center justify-between bg-bosque px-4 py-3 text-crema md:hidden"` → `class="flex items-center justify-between border-b border-line bg-night-raised/95 px-4 py-3 text-moon md:hidden"`
- Título móvil `class="font-serif text-xl"` → `class="font-serif text-xl text-gold"`
- Botón móvil `class="min-h-11 rounded-control border border-crema/30 px-3 py-2 text-sm"` → `class="min-h-11 rounded-control border border-line px-3 py-2 text-sm text-moon hover:bg-panel"`
- Nav inferior `class="fixed inset-x-0 bottom-0 flex justify-around bg-bosque py-2 text-crema md:hidden"` → `class="fixed inset-x-0 bottom-0 flex justify-around border-t border-line bg-night-raised/95 py-2 text-moon md:hidden"`
- Activo móvil `'border-dorado'` → `'border-magic text-gold'`

- [ ] **Step 2: Migrar `GuestLayout.vue`**

Reemplazar el template completo por:

```vue
<template>
    <div class="arcane-sky flex min-h-screen flex-col items-center justify-center px-4 py-8">
        <div class="mb-6 text-center">
            <Link href="/" class="font-serif text-3xl text-gold">Biblioteca</Link>
            <p class="mt-1 text-sm text-mist">Tu biblioteca personal</p>
        </div>

        <div class="w-full max-w-[420px] rounded-card border border-line bg-panel p-6 shadow-modal">
            <slot />
        </div>
    </div>
</template>
```

- [ ] **Step 3: Verificar**

Run: `npm run build`
Expected: build correcto.

---

### Task 3: Controles base

**Files:**
- Modify: `resources/js/Components/PrimaryButton.vue`
- Modify: `resources/js/Components/SecondaryButton.vue`
- Modify: `resources/js/Components/DangerButton.vue`
- Modify: `resources/js/Components/TextInput.vue`
- Modify: `resources/js/Components/InputLabel.vue`
- Modify: `resources/js/Components/InputError.vue`
- Modify: `resources/js/Components/Checkbox.vue`
- Modify: `resources/js/Components/Modal.vue`
- Modify: `resources/js/Components/Dropdown.vue`
- Modify: `resources/js/Components/DropdownLink.vue`
- Modify: `resources/js/Components/NavLink.vue`
- Modify: `resources/js/Components/ResponsiveNavLink.vue`
- Modify: `resources/js/Components/RatingStars.vue`

- [ ] **Step 1: Botones**

`PrimaryButton.vue`:

```html
class="inline-flex min-h-11 items-center justify-center rounded-control border border-transparent bg-arcane px-4 py-2 text-sm font-medium text-moon transition duration-150 ease-in-out hover:bg-arcane-strong focus:bg-arcane-strong focus:outline-none focus:ring-2 focus:ring-magic focus:ring-offset-2 focus:ring-offset-night active:bg-arcane-strong disabled:opacity-50"
```

`SecondaryButton.vue`:

```html
class="inline-flex min-h-11 items-center justify-center rounded-control border border-line bg-panel px-4 py-2 text-sm font-medium text-moon shadow-soft transition duration-150 ease-in-out hover:bg-panel-raised focus:outline-none focus:ring-2 focus:ring-magic focus:ring-offset-2 focus:ring-offset-night disabled:opacity-50"
```

`DangerButton.vue`:

```html
class="inline-flex min-h-11 items-center justify-center rounded-control border border-transparent bg-danger-strong px-4 py-2 text-sm font-medium text-moon transition duration-150 ease-in-out hover:bg-danger-strong/85 focus:outline-none focus:ring-2 focus:ring-magic focus:ring-offset-2 focus:ring-offset-night disabled:opacity-50"
```

- [ ] **Step 2: Campos y errores**

`TextInput.vue`:

```html
class="rounded-control border-line bg-panel px-3 py-2 text-moon shadow-soft placeholder:text-mist focus:border-magic focus:ring-magic"
```

`InputLabel.vue`:

```html
<label class="block text-sm font-medium text-mist">
```

`InputError.vue`:

```html
<p class="text-sm text-danger">
```

`Checkbox.vue`:

```html
class="rounded border-line bg-panel text-arcane shadow-sm focus:ring-magic"
```

- [ ] **Step 3: Modal, dropdowns y enlaces de navegación**

`Modal.vue`:
- Velo: `class="absolute inset-0 bg-night/80 backdrop-blur-sm"` (antes `bg-gray-500 opacity-75`).
- Panel: `class="mb-6 transform overflow-hidden rounded-modal border border-line bg-panel shadow-modal transition-all sm:mx-auto sm:w-full"` (antes `rounded-lg bg-white shadow-xl`).

`Dropdown.vue`:
- `default: 'py-1 bg-white'` → `default: 'py-1 bg-panel'`
- `class="rounded-md ring-1 ring-black ring-opacity-5"` → `class="rounded-control ring-1 ring-line"`
- El contenedor `class="absolute z-50 mt-2 rounded-md shadow-lg"` → `class="absolute z-50 mt-2 rounded-control shadow-modal"`.

`DropdownLink.vue`:

```html
class="block w-full px-4 py-2 text-start text-sm leading-5 text-moon transition duration-150 ease-in-out hover:bg-panel-raised focus:bg-panel-raised focus:outline-none"
```

`NavLink.vue`:

```js
props.active
    ? 'inline-flex items-center px-1 pt-1 border-b-2 border-magic text-sm font-medium leading-5 text-gold focus:outline-none transition duration-150 ease-in-out'
    : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-mist hover:text-moon hover:border-line focus:outline-none transition duration-150 ease-in-out',
```

`ResponsiveNavLink.vue`:

```js
props.active
    ? 'block w-full ps-3 pe-4 py-2 border-s-4 border-magic text-start text-base font-medium text-gold bg-panel focus:outline-none transition duration-150 ease-in-out'
    : 'block w-full ps-3 pe-4 py-2 border-s-4 border-transparent text-start text-base font-medium text-mist hover:text-moon hover:bg-panel-raised hover:border-line focus:outline-none focus:bg-panel-raised transition duration-150 ease-in-out',
```

`RatingStars.vue`:
- `'text-dorado' : 'text-dorado/40'` → `'text-gold' : 'text-gold/40'`

- [ ] **Step 4: Verificar**

Run: `npm run build`
Expected: build correcto.

---

### Task 4: Acceso y perfil

**Files:**
- Modify: `resources/js/Pages/Auth/Login.vue`
- Modify: `resources/js/Pages/Auth/Register.vue`
- Modify: `resources/js/Pages/Auth/ForgotPassword.vue`
- Modify: `resources/js/Pages/Auth/ResetPassword.vue`
- Modify: `resources/js/Pages/Auth/VerifyEmail.vue`
- Modify: `resources/js/Pages/Auth/ConfirmPassword.vue`
- Modify: `resources/js/Pages/Profile/Edit.vue`
- Modify: `resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.vue`
- Modify: `resources/js/Pages/Profile/Partials/UpdatePasswordForm.vue`
- Modify: `resources/js/Pages/Profile/Partials/DeleteUserForm.vue`

- [ ] **Step 1: Páginas de acceso**

Aplicar en los seis archivos:

| Antes | Después |
|---|---|
| `text-bosque` (títulos y hover de enlaces) | `text-gold` |
| `text-tinta/60`, `text-tinta/65`, `text-tinta/70` | `text-mist` |
| `text-oliva` en enlaces | `text-magic` |
| `text-oliva` en mensaje de estado | `text-success` |
| `hover:text-bosque` | `hover:text-gold` |
| `border-madera/20` | `border-line` |
| `focus:ring-salvia` | `focus:ring-magic` |

- [ ] **Step 2: `Profile/Edit.vue`**

Cada tarjeta:

```html
<div class="rounded-card border border-line bg-panel p-4">
```

(antes `border-madera/25 bg-crema/70`).

- [ ] **Step 3: Parciales de perfil**

`UpdateProfileInformationForm.vue` y `UpdatePasswordForm.vue`:

| Antes | Después |
|---|---|
| `text-gray-900` | `text-gold` |
| `text-gray-600` | `text-mist` |
| `text-gray-800` | `text-moon` |
| `text-green-600` | `text-success` |
| `focus:ring-indigo-500` | `focus:ring-magic` |
| `text-gray-900` en enlace hover | `hover:text-gold` |

`DeleteUserForm.vue`: `text-gray-900` → `text-gold`, `text-gray-600` → `text-mist`.

- [ ] **Step 4: Verificar**

Run: `npm run test`
Expected: 13 tests pasan.

Run: `npm run build`
Expected: build correcto.

---

### Task 5: Biblioteca y cabecera de estantería

**Files:**
- Modify: `resources/js/Pages/Library/Index.vue`
- Modify: `resources/js/Components/Shelf.vue`
- Modify: `resources/js/Components/EmptyShelf.vue`

- [ ] **Step 1: Botón «Añadir»**

En `Library/Index.vue`:

```html
<Link href="/books/create" class="rounded-control bg-arcane px-4 py-2 text-moon hover:bg-arcane-strong">
```

- [ ] **Step 2: Cabecera de `Shelf.vue`**

Solo la cabecera; no tocar `EmptyShelf`, `BookCard`, `BookSpine` ni `ShelfBoard`.

- Contador: `class="text-sm text-tinta/65"` → `class="text-sm text-mist"`
- Botón de modo: `class="rounded-control border border-madera/35 px-2 py-1 text-xs text-tinta/75 hover:bg-crema"` → `class="rounded-control border border-line px-2 py-1 text-xs text-moon/80 hover:bg-panel-raised"`
- `EmptyShelf.vue`: mantener borde `border-madera/35` y cambiar solo el texto `text-bosque` → `text-crema` para que se lea sobre el espaldar oscuro.

- [ ] **Step 3: Verificar que el mueble no cambió**

Run: `git diff -- resources/js/Components/BookCard.vue resources/js/Components/BookSpine.vue resources/js/Components/ShelfBoard.vue resources/css/textures.css`
Expected: no hay cambios en esos archivos (salvo `.arcane-sky` añadido en Task 1).

Run: `npm run build`
Expected: build correcto.

---

### Task 6: Alta y edición de libros

**Files:**
- Modify: `resources/js/Pages/Books/Create.vue`
- Modify: `resources/js/Pages/Books/Edit.vue`
- Modify: `resources/js/Components/BarcodeScanner.vue`

- [ ] **Step 1: `Books/Create.vue`**

Reemplazos globales:

| Antes | Después |
|---|---|
| `border-madera/35 bg-crema` (inputs, selects, textarea) | `border border-line bg-panel text-moon placeholder:text-mist` |
| `bg-oliva text-crema` (pestaña activa) | `bg-arcane text-moon` |
| `bg-crema text-tinta` (pestaña inactiva) | `bg-panel text-mist` |
| `bg-oliva px-4 py-2 text-crema hover:bg-oliva-hover` (botones) | `bg-arcane px-4 py-2 text-moon hover:bg-arcane-strong` |
| `border-madera/35 px-4 py-2 text-sm text-tinta hover:bg-pergamino` | `border-line px-4 py-2 text-sm text-moon hover:bg-panel-raised` |
| `text-tinta/60` | `text-mist` |
| `text-terracota` | `text-danger` |
| `border-madera/35 bg-crema p-3 text-left hover:bg-pergamino` | `border-line bg-panel p-3 text-left text-moon hover:bg-panel-raised` |
| `bg-pergamino ... text-madera` (portada de sustitución en resultados) | `bg-panel-raised ... text-gold` |
| `text-tinta/65` | `text-mist` |
| `text-tinta/50` | `text-mist/80` |

Los `input type="file"` llevan además:

```html
class="text-sm text-mist file:me-3 file:rounded-control file:border-0 file:bg-panel-raised file:px-3 file:py-1 file:text-moon"
```

La base de las pestañas debe quedar:

```html
class="rounded-control border border-line px-3 py-2"
```

- [ ] **Step 2: `Books/Edit.vue`**

| Antes | Después |
|---|---|
| `bg-crema shadow-card ring-1 ring-madera/35` (preview) | `bg-panel shadow-card ring-1 ring-line` |
| `text-tinta` (preview sin portada) | `text-moon` |
| `text-tinta/60` | `text-mist` |
| `border-madera/35 bg-crema` | `border border-line bg-panel text-moon placeholder:text-mist` |
| `bg-oliva ... text-crema hover:bg-oliva-hover` | `bg-arcane ... text-moon hover:bg-arcane-strong` |
| `border-terracota ... text-terracota` | `border-danger ... text-danger hover:bg-danger/10` |
| `border-madera/25 bg-crema/70` (sección mover) | `border-line bg-panel` |
| `bg-bosque px-4 py-2 text-crema` (mover) | `bg-arcane px-4 py-2 text-moon hover:bg-arcane-strong` |

- [ ] **Step 3: `BarcodeScanner.vue`**

- Visor: `class="aspect-[4/3] w-full rounded-md bg-tinta/80"` → `class="aspect-[4/3] w-full rounded-md bg-night"`.
- Error: `text-terracota` → `text-danger`.

- [ ] **Step 4: Verificar**

Run: `npm run build`
Expected: build correcto.

---

### Task 7: Ajustes y selector de idioma

**Files:**
- Modify: `resources/js/Pages/Settings/Index.vue`
- Modify: `resources/js/Components/LanguageSelect.vue`

- [ ] **Step 1: Formularios y botones de `Settings/Index.vue`**

| Antes | Después |
|---|---|
| `border-madera/35 bg-crema px-3 py-2` (inputs/selects) | `border-line bg-panel px-3 py-2 text-moon placeholder:text-mist` |
| `bg-oliva px-4 py-2 text-crema hover:bg-oliva-hover` | `bg-arcane px-4 py-2 text-moon hover:bg-arcane-strong` |
| `bg-bosque px-4 py-2 text-crema` (crear estantería) | `bg-arcane px-4 py-2 text-moon hover:bg-arcane-strong` |
| `border-madera/35 bg-crema p-3` (tarjeta de estantería) | `border-line bg-panel p-3` |
| `border-madera/30 bg-white/50` (renombrar) | `border-line bg-panel-raised text-moon` |
| `border-madera/35 px-2 py-1 text-sm` (botones pequeños) | `border-line px-2 py-1 text-sm text-moon hover:bg-panel-raised` |
| `border-terracota ... text-terracota` (eliminar) | `border-danger ... text-danger hover:bg-danger/10` |
| `text-tinta/65` | `text-mist` |
| `text-terracota` (errores) | `text-danger` |
| `text-oliva` (éxito de idioma) | `text-success` |

- [ ] **Step 2: `LanguageSelect.vue`**

| Antes | Después |
|---|---|
| `border-madera/35 bg-crema` (buscador y lista) | `border-line bg-panel` |
| `border-madera/15 ... hover:bg-pergamino` | `border-line ... text-moon hover:bg-panel-raised` |
| `'bg-pergamino font-semibold'` | `'bg-panel-raised font-semibold'` |
| `text-tinta/60` | `text-mist` |

El buscador debe llevar además `text-moon placeholder:text-mist`.

- [ ] **Step 3: Verificar**

Run: `npm run test`
Expected: 13 tests pasan.

Run: `npm run build`
Expected: build correcto.

---

### Task 8: Documentación y verificación final

**Files:**
- Modify: `docs/DISENO.md`

- [ ] **Step 1: Actualizar `DISENO.md`**

- Sección de paleta: añadir la tabla de tokens nuevos con sus hex y uso; indicar que la paleta del mueble sigue vigente y es excluyente.
- Sección de fondo: documentar `.arcane-sky` (degradados violeta/turquesa y estrellas CSS, sin imágenes).
- Componentes: documentar navegación, tarjetas, modales, formularios, botones y foco en la noche.
- Registro de cambios: añadir la fila del 2026-10-26 con «Noche arcana: nueva paleta violeta, oro y turquesa para toda la app; el mueble de las estanterías se conserva igual».

- [ ] **Step 2: Buscar restos de la paleta antigua fuera del mueble**

Run:

```powershell
Get-ChildItem resources/js -Recurse -Filter *.vue | Select-String -Pattern 'bg-crema|text-tinta|bg-oliva|text-bosque|bg-bosque|border-madera|bg-white|text-gray-|bg-gray-|border-gray-|text-indigo|bg-indigo|border-indigo|text-red-'
```

Expected: solo apariciones dentro de `BookCard.vue`, `BookSpine.vue`, `Shelf.vue` (si queda alguna en libros), `ShelfBoard.vue`, `EmptyShelf.vue` y `coverColor.js` (paleta del mueble). Cualquier otra aparición es un fallo: migrarla.

- [ ] **Step 3: Tests y build completos**

Run: `php artisan test`
Expected: 73 tests pasan.

Run: `npm run test`
Expected: 13 tests pasan.

Run: `npm run build`
Expected: build correcto.

- [ ] **Step 4: Revisión visual**

- Abrir acceso, biblioteca, alta, edición, ajustes y perfil a 360 px y escritorio.
- Confirmar contraste, foco con teclado y que no hay fondos blancos ni textos oscuros sobre noche.
- Capturar una estantería y comparar con la versión anterior: madera, espaldar, balda, portadas y lomos deben ser idénticos.
- Revisar que las estrellas del fondo no molestan la lectura; si molestan, bajar su opacidad en `.arcane-sky`.

- [ ] **Step 5: Informar a la usuaria**

Indicar archivos modificados y recordar que ella hace el commit. No ejecutar `git add` ni `git commit`.
