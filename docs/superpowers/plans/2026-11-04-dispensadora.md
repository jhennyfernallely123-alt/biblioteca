# Dispensadora 3D (máquina expendedora) — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **NOTA del proyecto (AGENTS.md):** la usuaria hace los commits. Ninguna tarea ejecuta `git`.

**Goal:** Rediseñar `CardMachine` como máquina expendedora con volumen (gabinete de madera 3D, ventana de cristal con las balotas dentro, palanca de latón y trampilla de salida), sin cambiar mecánica ni accesibilidad.

**Architecture:** Cambios solo en el marcado de `CardMachine.vue` y las clases `card-machine*` de `textures.css`. Las clases de comportamiento (`card-machine__ball`, `--selected/--drop`, `card-machine__crank--turning`) se conservan para no romper pruebas ni estados; se añaden bloques nuevos (`__body`, `__window`, `__lever*`, `__slot`).

**Tech Stack:** Vue 3 `<script setup>`, Tailwind 4 + CSS de texturas propio, Vitest + Vue Test Utils.

**Spec:** `docs/superpowers/specs/2026-11-04-dispensadora-design.md`

---

## Task 1: Prueba de estructura (RED)

**Files:**
- Modify: `resources/js/Components/__tests__/CardMachine.test.js`

- [ ] **Step 1: Añadir la prueba de estructura**

Insertar este test dentro de `describe('CardMachine', ...)` (después de «muestra las balotas disponibles…»):

```js
    it('monta la ventana de cristal y la trampilla de salida', () => {
        const wrapper = mountMachine();

        expect(wrapper.find('.card-machine__body').exists()).toBe(true);
        expect(wrapper.find('.card-machine__window').exists()).toBe(true);
        expect(wrapper.get('.card-machine__dome').findAll('.card-machine__ball')).toHaveLength(3);
        expect(wrapper.find('.card-machine__lever').exists()).toBe(true);
        expect(wrapper.find('.card-machine__slot').exists()).toBe(true);
    });
```

- [ ] **Step 2: Ejecutar y ver el rojo**

```bash
cmd.exe /C 'npm run test -- --run resources/js/Components/__tests__/CardMachine.test.js'
```

Esperado: falla «monta la ventana de cristal…» porque `__body`, `__window`, `__lever` y `__slot` no existen aún.

---

## Task 2: Marcado de la máquina (GREEN)

**Files:**
- Modify: `resources/js/Components/CardMachine.vue` (solo la plantilla; el script no cambia)

- [ ] **Step 1: Reescribir la plantilla**

```vue
<template>
    <section class="card-machine mx-auto w-full max-w-md">
        <div class="card-machine__body">
            <div class="card-machine__window">
                <div class="card-machine__dome">
                    <button
                        v-for="offer in offers"
                        :key="offer.index"
                        type="button"
                        class="card-machine__ball h-11 w-11"
                        :class="{
                            'card-machine__ball--selected': selected === offer.index,
                            'card-machine__ball--drop': result && selected === offer.index,
                        }"
                        :style="{ '--bx': ballSpot(offer.index).x, '--by': ballSpot(offer.index).y, '--br': ballSpot(offer.index).r }"
                        :disabled="drawing || !!result"
                        :aria-pressed="selected === offer.index"
                        :aria-label="t('ui.cards_ball_label', { number: offer.index + 1, family: offer.familyLabel })"
                        @click="emit('select', offer.index)"
                    >
                        <CardEmblem :name="offer.familyEmblem" :size="22" />
                    </button>
                </div>
            </div>

            <div class="card-machine__side">
                <button
                    type="button"
                    class="card-machine__crank card-machine__lever"
                    :class="{ 'card-machine__crank--turning': drawing }"
                    :disabled="selected === null || drawing || !!result"
                    :aria-label="t('ui.cards_crank')"
                    @click="emit('crank')"
                >
                    <span class="card-machine__lever-arm">
                        <span class="card-machine__lever-knob"></span>
                    </span>
                </button>

                <div class="card-machine__slot" aria-hidden="true">
                    <div class="card-machine__chute"></div>
                </div>
            </div>
        </div>

        <div aria-live="polite" class="mt-6">
            <p v-if="!result && selected === null" class="text-center text-mist">{{ t('ui.cards_choose_ball') }}</p>

            <span v-if="drawing" class="sr-only">{{ t('ui.cards_cranking') }}</span>

            <template v-if="result">
                <p v-if="!result.duplicate" class="mb-3 text-center font-serif text-lg text-gold">
                    {{ t('ui.cards_new_card') }}
                </p>
                <CardEnvelope :card="result.card" :duplicate="result.duplicate" @open="emit('open')" />
                <p v-if="countdown" class="mt-4 text-center text-sm text-mist">
                    {{ t('ui.cards_reset_in', { time: countdownText }) }}
                </p>
            </template>
        </div>
    </section>
</template>
```

- [ ] **Step 2: Añadir el helper de posiciones en el script (después de `countdownText`)**

```js
const ballSpots = [
    { x: '6%', y: '8%', r: '-12deg' },
    { x: '36%', y: '4%', r: '8deg' },
    { x: '64%', y: '10%', r: '-6deg' },
    { x: '16%', y: '38%', r: '14deg' },
    { x: '48%', y: '34%', r: '-10deg' },
    { x: '74%', y: '40%', r: '6deg' },
    { x: '26%', y: '66%', r: '-8deg' },
    { x: '58%', y: '64%', r: '10deg' },
];

function ballSpot(index) {
    return ballSpots[index % ballSpots.length];
}
```

(No hay `defineExpose`; `ballSpot` se usa solo en la plantilla.)

- [ ] **Step 3: Ejecutar y ver el verde estructural**

```bash
cmd.exe /C 'npm run test -- --run resources/js/Components/__tests__/CardMachine.test.js'
```

Esperado: los 8 tests pasan (los visuales aún se ven planos hasta la Task 3; el texto «Girando…» ahora va en `sr-only` y el test de giro solo mira clases/estado, sigue pasando).

---

## Task 3: CSS 3D en `textures.css`

**Files:**
- Modify: `resources/css/textures.css` (bloque `card-machine*`)

- [ ] **Step 1: Sustituir las reglas del gabinete, cúpula, balotas, manivela y rampa por las nuevas**

```css
.card-machine {
  position: relative;
  border-radius: 24px;
  background: transparent;
}

.card-machine__body {
  position: relative;
  display: grid;
  gap: 12px;
  grid-template-columns: 1fr auto;
  border-bottom: 10px solid color-mix(in srgb, var(--color-madera) 55%, #000);
  border-radius: 22px;
  padding: 16px;
  background-image:
    linear-gradient(165deg, color-mix(in srgb, var(--color-madera-clara) 72%, #fff 4%) 0%, var(--color-madera) 55%, color-mix(in srgb, var(--color-madera) 78%, #000) 100%);
  box-shadow:
    inset 0 2px 0 color-mix(in srgb, var(--color-crema) 35%, transparent),
    inset -10px 0 18px -12px rgb(0 0 0 / 70%),
    0 22px 30px -18px rgb(0 0 0 / 70%);
}

.card-machine__body::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 22px;
  border: 2px solid color-mix(in srgb, var(--color-dorado) 55%, transparent);
  box-shadow: inset 0 1px 0 color-mix(in srgb, var(--color-crema) 25%, transparent);
  pointer-events: none;
}

.card-machine__window {
  position: relative;
  border-radius: 16px;
  padding: 8px;
  background-image: linear-gradient(180deg, var(--color-dorado), color-mix(in srgb, var(--color-dorado) 55%, #000));
  box-shadow:
    inset 0 2px 0 rgb(255 255 255 / 45%),
    0 6px 12px -6px rgb(0 0 0 / 60%);
}

.card-machine__dome {
  position: relative;
  height: 190px;
  border-radius: 10px;
  overflow: hidden;
  background-image:
    radial-gradient(circle at 30% 20%, color-mix(in srgb, var(--color-panel-raised) 88%, #fff 6%), var(--color-night));
}

.card-machine__dome::after {
  content: '';
  position: absolute;
  inset: 0;
  background-image: linear-gradient(115deg, rgb(255 255 255 / 16%) 0%, rgb(255 255 255 / 4%) 28%, transparent 42%);
  pointer-events: none;
}

.card-machine__ball {
  position: absolute;
  left: var(--bx, 10%);
  top: var(--by, 10%);
  rotate: var(--br, 0deg);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  color: var(--color-madera);
  background-image: radial-gradient(circle at 32% 28%, var(--color-crema), var(--color-pergamino) 70%);
  box-shadow:
    inset -4px -6px 10px rgb(0 0 0 / 18%),
    inset 3px 4px 6px rgb(255 255 255 / 55%),
    0 4px 8px -2px rgb(0 0 0 / 45%);
  transition: left 0.3s ease, top 0.3s ease, rotate 0.3s ease, box-shadow 0.2s ease;
}

.card-machine__ball--selected {
  left: calc(50% - 22px);
  top: calc(100% - 56px);
  rotate: 0deg;
  box-shadow:
    inset -4px -6px 10px rgb(0 0 0 / 18%),
    inset 3px 4px 6px rgb(255 255 255 / 55%),
    0 0 0 3px var(--color-gold),
    0 8px 14px -4px rgb(0 0 0 / 55%);
}

.card-machine__ball--drop {
  animation: card-ball-drop 0.9s ease-in forwards;
}

@keyframes card-ball-drop {
  0% {
    translate: 0 0;
    opacity: 1;
  }

  60% {
    translate: 0 120%;
    opacity: 1;
  }

  100% {
    translate: 0 140%;
    opacity: 0;
  }
}

.card-machine__side {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 8px;
  border-radius: 14px;
  background-image: linear-gradient(180deg, color-mix(in srgb, var(--color-madera-clara) 55%, transparent), transparent);
}

.card-machine__crank.card-machine__lever {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 120px;
  border: 1px solid color-mix(in srgb, var(--color-dorado) 60%, transparent);
  border-radius: 12px;
  color: var(--color-dorado);
  background-image: linear-gradient(180deg, var(--color-panel), var(--color-night));
  transition: rotate 0.3s ease;
}

.card-machine__lever-arm {
  position: relative;
  display: block;
  width: 10px;
  height: 84px;
  border-radius: 9999px;
  background-image: linear-gradient(90deg, color-mix(in srgb, var(--color-dorado) 70%, #000), var(--color-dorado) 45%, color-mix(in srgb, var(--color-dorado) 60%, #000));
  box-shadow: 0 2px 6px rgb(0 0 0 / 50%);
}

.card-machine__lever-knob {
  position: absolute;
  top: -20px;
  left: 50%;
  translate: -50% 0;
  display: block;
  width: 26px;
  height: 26px;
  border-radius: 9999px;
  background-image: radial-gradient(circle at 32% 28%, var(--color-crema), var(--color-dorado) 75%);
  box-shadow: 0 3px 6px rgb(0 0 0 / 45%);
}

.card-machine__crank--turning {
  animation: card-lever-turn 1.1s ease-in-out;
}

@keyframes card-lever-turn {
  0%,
  100% {
    rotate: 0deg;
  }

  50% {
    rotate: 14deg;
    translate: 0 6px;
  }
}

.card-machine__slot {
  display: flex;
  align-items: flex-end;
  justify-content: center;
  width: 56px;
  padding: 8px;
  border-radius: 10px;
  background-image: linear-gradient(180deg, var(--color-dorado), color-mix(in srgb, var(--color-dorado) 55%, #000));
}

.card-machine__chute {
  width: 100%;
  height: 22px;
  border-radius: 4px;
  background-image: linear-gradient(180deg, var(--color-night), color-mix(in srgb, var(--color-night) 70%, #000));
  box-shadow: inset 0 4px 8px rgb(0 0 0 / 80%);
}

@media (min-width: 768px) {
  .card-machine__body {
    transform: perspective(900px) rotateY(-4deg);
  }
}
```

- [ ] **Step 2: Eliminar la regla vieja `.card-machine__base` (ya no se usa en el marcado) y actualizar el bloque `prefers-reduced-motion` de las cartas**

```css
@media (prefers-reduced-motion: reduce) {
  .card-machine__ball,
  .card-machine__ball--selected {
    transition: none;
  }

  .card-machine__ball--drop,
  .card-machine__crank--turning {
    animation: none;
  }

  .card-machine__dome::after {
    display: none;
  }

  .card-envelope__flap {
    transition: none;
  }
}
```

- [ ] **Step 3: Ejecutar pruebas y build**

```bash
cmd.exe /C 'npm run test'
cmd.exe /C 'npm run build'
```

Esperado: 40 archivos / 154+ tests verde; build OK.

---

## Task 4: Documentación y verificación final

**Files:**
- Modify: `docs/DISENO.md`

- [ ] **Step 1: Actualizar la viñeta «Bombo (`CardMachine`)» de la sección Cartas**

Texto nuevo:

```markdown
- **Bombo (`CardMachine`):** máquina expendedora de madera con volumen: gabinete con cornisa de latón, costado en sombra y sombra proyectada (`.card-machine__body`); ventana de cristal con marco `dorado` (`.card-machine__window`) y reflejo diagonal, con las 8 balotas `pergamino` dentro en desorden (posiciones y rotaciones deterministas) y la elegida deslizándose al surco inferior con anillo `gold`; panel lateral (`.card-machine__side`) con palanca de latón de pomo (`.card-machine__lever`, conserva `card-machine__crank` y el giro `--turning`) y trampilla de salida (`.card-machine__slot` + `__chute`) donde cae la balota. En 360 px el frente es recto y el panel pasa debajo de la ventana.
```

- [ ] **Step 2: Añadir la fila al registro de cambios (fecha 2026-11-04)**

```markdown
| 2026-11-04 | La dispensadora de cartas pasa de bombo plano a máquina expendedora con volumen: gabinete de madera 3D con cornisa de latón, ventana de cristal con reflejo y las balotas sueltas dentro, palanca de latón con pomo y trampilla de salida. Mismos tokens, estados y accesibilidad; sin colores nuevos. | Agente |
```

- [ ] **Step 3: Verificación completa**

```bash
cmd.exe /C 'php artisan test'
cmd.exe /C 'npm run test'
cmd.exe /C 'vendor\bin\pint --test'
cmd.exe /C 'npm run build'
```

- [ ] **Step 4: Revisión manual de la usuaria**

360 px, 768 px y escritorio; balotas con teclado; palanca desactivada sin selección; `prefers-reduced-motion` sin animaciones. Informar archivos tocados y dejar el commit a la usuaria.
