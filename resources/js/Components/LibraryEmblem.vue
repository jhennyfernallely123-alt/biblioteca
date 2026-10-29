<script setup>
import { useId } from 'vue';

defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
});

const uid = useId();
const id = (name) => `${name}-${uid}`;
const paint = (name) => `url(#${id(name)})`;

const refs = {
    glow: paint('glow'),
    cover: paint('cover'),
    coverSheen: paint('cover-sheen'),
    board: paint('board'),
    gilt: paint('gilt'),
    paper: paint('paper'),
    pageSheen: paint('page-sheen'),
    edges: paint('edges'),
    gutter: paint('gutter'),
    titleGold: paint('title-gold'),
    titleArc: `#${id('title-arc')}`,
    grain: paint('grain'),
    clipPage: paint('clip-page'),
    clipPages: paint('clip-pages'),
};

const sides = ['left', 'right'];

const leftPage =
    'M 280 182 C 236 184 196 186 158 188 C 148 240 128 300 94 344 C 154 348 222 343 280 335 Z';
const leftStack =
    'M 94 344 C 154 348 222 343 280 335 L 280 344 C 222 352 154 357 94 353 Z';
const stackLines = [
    'M 94 347.5 C 154 351.5 222 346.5 280 338.5',
    'M 94 350.5 C 154 354.5 222 349.5 280 341.5',
];
const leftCover =
    'M 280 170 L 146 177 Q 132 178 130 189 L 70 352 Q 68 363 80 365 L 280 350 Z';
const boardBand =
    'M 70 352 Q 68 363 80 365 L 280 350 L 280 364 L 80 379 Q 66 377 65 363 Z';
const boardRim = 'M 70 352 Q 68 363 80 365 L 280 350';
const nearEdgeLight = 'M 98 341 C 156 345 222 340 278 332';
const outerEdgeLight = 'M 154 192 C 146 242 126 300 98 340';
const filigree = [
    'M 92 360 C 102 358 109 355 113 351',
    'M 97 364 C 107 362 114 358 119 353',
    'M 111 355 L 114 358 L 111 361 L 108 358 Z',
];
</script>

<template>
    <figure
        class="auth-emblem"
        :class="{ 'auth-emblem--compact': compact }"
        aria-hidden="true"
    >
        <div class="auth-emblem__aura" />

        <svg class="auth-emblem__art" viewBox="0 0 560 480" role="presentation" focusable="false">
            <defs>
                <radialGradient :id="id('glow')" cx="50%" cy="60%" r="55%">
                    <stop offset="0" style="stop-color: var(--color-gold)" stop-opacity="0.26" />
                    <stop offset="0.55" style="stop-color: var(--color-gold)" stop-opacity="0.08" />
                    <stop offset="1" style="stop-color: var(--color-gold)" stop-opacity="0" />
                </radialGradient>

                <linearGradient :id="id('cover')" x1="0.15" y1="0" x2="0.75" y2="1">
                    <stop offset="0" style="stop-color: var(--color-line)" />
                    <stop offset="0.3" style="stop-color: var(--color-panel)" />
                    <stop offset="0.72" style="stop-color: var(--color-night)" />
                    <stop offset="1" style="stop-color: var(--color-night)" />
                </linearGradient>

                <radialGradient :id="id('cover-sheen')" cx="26%" cy="26%" r="62%">
                    <stop offset="0" stop-color="var(--color-moon)" stop-opacity="0.1" />
                    <stop offset="0.5" stop-color="var(--color-moon)" stop-opacity="0.03" />
                    <stop offset="1" stop-color="var(--color-moon)" stop-opacity="0" />
                </radialGradient>

                <linearGradient :id="id('board')" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" style="stop-color: var(--color-panel)" />
                    <stop offset="0.35" style="stop-color: var(--color-night)" />
                    <stop offset="1" style="stop-color: var(--color-night)" />
                </linearGradient>

                <linearGradient :id="id('gilt')" x1="0" y1="0" x2="0.4" y2="1">
                    <stop offset="0" style="stop-color: var(--color-gold)" stop-opacity="0.85" />
                    <stop offset="0.6" style="stop-color: var(--color-dorado)" stop-opacity="0.45" />
                    <stop offset="1" style="stop-color: var(--color-dorado)" stop-opacity="0.15" />
                </linearGradient>

                <linearGradient :id="id('paper')" x1="0" y1="0" x2="1" y2="0.08">
                    <stop offset="0" style="stop-color: var(--color-crema)" />
                    <stop offset="0.18" style="stop-color: var(--color-crema)" />
                    <stop offset="0.46" style="stop-color: var(--color-pergamino)" />
                    <stop offset="0.7" style="stop-color: color-mix(in srgb, var(--color-pergamino) 82%, var(--color-dorado))" />
                    <stop offset="0.88" style="stop-color: color-mix(in srgb, var(--color-pergamino) 62%, var(--color-dorado))" />
                    <stop offset="1" style="stop-color: color-mix(in srgb, var(--color-dorado) 62%, var(--color-tinta))" />
                </linearGradient>

                <radialGradient :id="id('page-sheen')" cx="32%" cy="42%" r="58%">
                    <stop offset="0" stop-color="var(--color-moon)" stop-opacity="0.12" />
                    <stop offset="0.55" stop-color="var(--color-moon)" stop-opacity="0.03" />
                    <stop offset="1" stop-color="var(--color-moon)" stop-opacity="0" />
                </radialGradient>

                <linearGradient :id="id('edges')" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" style="stop-color: color-mix(in srgb, var(--color-crema) 88%, var(--color-dorado))" />
                    <stop offset="0.35" style="stop-color: var(--color-pergamino)" />
                    <stop offset="0.8" style="stop-color: color-mix(in srgb, var(--color-pergamino) 68%, var(--color-dorado))" />
                    <stop offset="1" style="stop-color: color-mix(in srgb, var(--color-dorado) 70%, var(--color-tinta))" />
                </linearGradient>

                <linearGradient :id="id('gutter')" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0" stop-color="transparent" />
                    <stop offset="0.3" style="stop-color: color-mix(in srgb, var(--color-tinta) 22%, transparent)" />
                    <stop offset="0.5" style="stop-color: color-mix(in srgb, var(--color-tinta) 58%, transparent)" />
                    <stop offset="0.7" style="stop-color: color-mix(in srgb, var(--color-tinta) 22%, transparent)" />
                    <stop offset="1" stop-color="transparent" />
                </linearGradient>

                <linearGradient :id="id('title-gold')" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" style="stop-color: color-mix(in srgb, var(--color-gold) 62%, var(--color-moon))" />
                    <stop offset="0.42" style="stop-color: var(--color-gold)" />
                    <stop offset="1" style="stop-color: var(--color-dorado)" />
                </linearGradient>

                <path :id="id('title-arc')" d="M 24 132 Q 280 4 536 132" fill="none" />

                <filter :id="id('grain')" x="0" y="0" width="100%" height="100%">
                    <feTurbulence type="fractalNoise" baseFrequency="1.2" numOctaves="2" stitchTiles="stitch" />
                    <feColorMatrix type="matrix" values="0 0 0 0 0.34  0 0 0 0 0.27  0 0 0 0 0.19  0 0 0 0.4 0" />
                </filter>

                <clipPath :id="id('clip-page')">
                    <path :d="leftPage" />
                </clipPath>
                <clipPath :id="id('clip-pages')">
                    <path :d="leftPage" />
                    <path :d="leftPage" transform="translate(560 0) scale(-1 1)" />
                </clipPath>
            </defs>

            <ellipse cx="280" cy="292" rx="252" ry="168" :fill="refs.glow" class="auth-emblem__glow" />

            <ellipse class="auth-emblem__shadow auth-emblem__shadow--soft" cx="280" cy="394" rx="240" ry="28" />
            <ellipse class="auth-emblem__shadow auth-emblem__shadow--tight" cx="280" cy="374" rx="186" ry="13" />

            <g
                v-for="side in sides"
                :key="side"
                class="auth-emblem__half"
                :transform="side === 'right' ? 'translate(560 0) scale(-1 1)' : undefined"
            >
                <path class="auth-emblem__board" :d="boardBand" :fill="refs.board" />
                <path class="auth-emblem__board-rim" :d="boardRim" :stroke="refs.gilt" />

                <path class="auth-emblem__cover" :d="leftCover" :fill="refs.cover" :stroke="refs.gilt" />
                <path :d="leftCover" :fill="refs.coverSheen" />

                <g class="auth-emblem__filigree" :stroke="refs.gilt">
                    <path v-for="(line, index) in filigree" :key="`fil-${index}`" :d="line" />
                    <circle cx="101" cy="358" r="1.3" />
                    <circle cx="117" cy="352" r="1" />
                </g>

                <path class="auth-emblem__stack" :d="leftStack" :fill="refs.edges" />
                <g class="auth-emblem__stack-lines">
                    <path v-for="(line, index) in stackLines" :key="`stack-${index}`" :d="line" />
                </g>

                <path class="auth-emblem__page" :d="leftPage" :fill="refs.paper" />

                <g :clip-path="refs.clipPage">
                    <path :d="leftPage" :fill="refs.pageSheen" />
                    <path class="auth-emblem__page-inner" :d="leftPage" />
                    <rect class="auth-emblem__grain" x="90" y="178" width="194" height="174" :filter="refs.grain" />
                    <path class="auth-emblem__edge-light" :d="nearEdgeLight" />
                    <path class="auth-emblem__edge-light auth-emblem__edge-light--soft" :d="outerEdgeLight" />
                </g>
            </g>

            <g :clip-path="refs.clipPages">
                <rect x="238" y="176" width="84" height="178" :fill="refs.gutter" />
                <path class="auth-emblem__crease" d="M 280 184 L 280 336" />
                <path class="auth-emblem__crease-light" d="M 274 188 L 274 330" />
                <path class="auth-emblem__crease-light" d="M 286 188 L 286 330" />
            </g>

            <ellipse class="auth-emblem__spine-well" cx="280" cy="356" rx="18" ry="6" />

            <text
                class="auth-emblem__word"
                :style="{ fill: refs.titleGold }"
                font-size="70"
                font-weight="600"
                letter-spacing="0.08em"
            >
                <textPath :href="refs.titleArc" startOffset="50%" text-anchor="middle">BIBLIOTECA</textPath>
            </text>
        </svg>
    </figure>
</template>

<style scoped>
.auth-emblem {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: min(100%, 480px);
    max-width: 100%;
    margin-inline: auto;
    isolation: isolate;
}

.auth-emblem--compact {
    width: min(100%, 214px);
}

.auth-emblem__aura {
    position: absolute;
    inset: 8% 4% 2%;
    z-index: 0;
    border-radius: 999px;
    background: radial-gradient(
        ellipse,
        color-mix(in srgb, var(--color-arcane) 26%, transparent) 0%,
        color-mix(in srgb, var(--color-gold) 8%, transparent) 52%,
        transparent 76%
    );
    filter: blur(30px);
    animation: auth-emblem-breathe 11s ease-in-out infinite alternate;
    pointer-events: none;
}

.auth-emblem__art {
    position: relative;
    z-index: 1;
    display: block;
    width: 100%;
    height: auto;
    aspect-ratio: 560 / 480;
    overflow: visible;
}

.auth-emblem__glow {
    opacity: 0.9;
    animation: auth-emblem-pulse 9s ease-in-out infinite alternate;
}

.auth-emblem__shadow {
    fill: var(--color-night);
}

.auth-emblem__shadow--soft {
    opacity: 0.6;
    filter: blur(16px);
}

.auth-emblem__shadow--tight {
    opacity: 0.8;
    filter: blur(7px);
}

.auth-emblem__board-rim {
    fill: none;
    stroke-width: 1.1;
    opacity: 0.55;
}

.auth-emblem__cover {
    stroke-width: 1;
    stroke-linejoin: round;
    filter: drop-shadow(0 10px 16px rgba(0, 0, 0, 0.55));
}

.auth-emblem__filigree {
    fill: none;
    stroke-width: 0.9;
    stroke-linecap: round;
    stroke-linejoin: round;
    opacity: 0.7;
}

.auth-emblem__filigree circle {
    fill: color-mix(in srgb, var(--color-gold) 80%, transparent);
    stroke: none;
}

.auth-emblem__stack {
    stroke: color-mix(in srgb, var(--color-tinta) 46%, transparent);
    stroke-width: 0.8;
    filter: drop-shadow(0 3px 4px rgba(0, 0, 0, 0.45));
}

.auth-emblem__stack-lines path {
    fill: none;
    stroke: color-mix(in srgb, var(--color-tinta) 26%, transparent);
    stroke-width: 0.7;
}

.auth-emblem__page {
    stroke: none;
}

.auth-emblem__page-inner {
    fill: none;
    stroke: color-mix(in srgb, var(--color-tinta) 30%, transparent);
    stroke-width: 18;
    opacity: 0.55;
    filter: blur(7px);
}

.auth-emblem__grain {
    opacity: 0.55;
}

.auth-emblem__edge-light {
    fill: none;
    stroke: color-mix(in srgb, var(--color-crema) 92%, transparent);
    stroke-width: 1.4;
    opacity: 0.75;
    stroke-linecap: round;
}

.auth-emblem__edge-light--soft {
    stroke-width: 1;
    opacity: 0.35;
}

.auth-emblem__crease {
    fill: none;
    stroke: color-mix(in srgb, var(--color-tinta) 72%, transparent);
    stroke-width: 1.4;
    opacity: 0.6;
}

.auth-emblem__crease-light {
    fill: none;
    stroke: color-mix(in srgb, var(--color-crema) 85%, transparent);
    stroke-width: 1;
    opacity: 0.5;
}

.auth-emblem__spine-well {
    fill: var(--color-night);
    opacity: 0.6;
    filter: blur(4px);
}

.auth-emblem__word {
    font-family: "Cormorant Garamond", Georgia, serif;
    filter:
        drop-shadow(0 0 14px color-mix(in srgb, var(--color-gold) 50%, transparent))
        drop-shadow(0 5px 12px rgba(0, 0, 0, 0.55));
}

@keyframes auth-emblem-breathe {
    from { opacity: 0.5; transform: scale(0.97); }
    to { opacity: 0.85; transform: scale(1.03); }
}

@keyframes auth-emblem-pulse {
    from { opacity: 0.55; }
    to { opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
    .auth-emblem__aura,
    .auth-emblem__glow {
        animation: none;
    }
}
</style>
