<script setup>
import { computed } from 'vue';

const props = defineProps({
    itemKey: { type: String, default: 'star' },
    size: { type: Number, default: 96 },
    locked: { type: Boolean, default: false },
});

const cuts = [
    'M60 4c15-1 31 5 40 17 9 12 12 29 5 43-7 14-22 24-39 25-17 1-34-6-43-19C14 57 11 39 19 26 27 13 44 5 60 4Z',
    'M58 5c17-2 34 5 43 19 9 14 8 32-1 45-9 13-25 21-42 20-17-1-32-10-40-24C10 51 13 33 23 21 33 10 44 7 58 5Z',
    'M60 5c16 0 33 7 41 21 8 14 7 32-2 44-9 12-25 19-41 19-16 0-32-7-41-19-9-12-10-30-2-44C23 12 44 5 60 5Z',
];

const artworks = {
    feria_pluma: `
        <ellipse cx="60" cy="86" rx="24" ry="7" fill="var(--color-madera-clara)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M40 86h40v10a8 8 0 0 1-8 8H48a8 8 0 0 1-8-8V86Z" fill="var(--color-madera)" stroke="var(--color-tinta)" stroke-width="2"/>
        <ellipse cx="60" cy="90" rx="14" ry="4" fill="var(--color-tinta)"/>
        <path d="M48 86c3-24 15-48 38-64-1 24-10 46-26 62l-8 6" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M86 22c-8 16-19 29-33 39" fill="none" stroke="var(--color-dorado)" stroke-width="2"/>
        <path d="M74 34c-6 2-11 6-15 11M80 30c-5 2-9 5-13 9M68 44c-5 3-9 6-12 10M83 26c-6 2-11 5-15 9" fill="none" stroke="var(--color-dorado)" stroke-width="1.6"/>
        <circle cx="34" cy="44" r="2.4" fill="var(--color-accent)"/>
        <circle cx="88" cy="66" r="2" fill="var(--color-accent)"/>
        <path d="M30 30l2 6 6 2-6 2-2 6-2-6-6-2 6-2 2-6Z" fill="var(--color-accent)"/>
    `,
    feria_lampara: `
        <path d="M48 32c0-11 5-17 12-17s12 6 12 17" fill="none" stroke="var(--color-tinta)" stroke-width="2.6"/>
        <path d="M42 34h36l-5 9H47l-5-9Z" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="2"/>
        <rect x="47" y="43" width="26" height="32" rx="6" fill="var(--color-accent)" opacity=".35" stroke="var(--color-dorado)" stroke-width="2"/>
        <path d="M60 50c5 5 6 9 6 12a6 6 0 0 1-12 0c0-4 2-7 6-12Z" fill="var(--color-terracota)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M42 75h36l-4 9H46l-4-9Z" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M48 84v6M60 84v6M72 84v6" stroke="var(--color-tinta)" stroke-width="2.4" fill="none"/>
        <circle cx="60" cy="56" r="17" fill="none" stroke="var(--color-accent)" stroke-width="1.2" opacity=".55"/>
        <path d="M32 50l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        <circle cx="90" cy="64" r="2" fill="var(--color-accent)"/>
    `,
    dragones_escama: `
        <path d="M60 18c14 2 26 20 26 39 0 17-12 30-26 30S34 74 34 57c0-19 12-37 26-39Z" fill="var(--color-oliva)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M44 52c5 3 10 3 16 0M60 52c5 3 10 3 16 0M38 64c6 4 12 4 18 1M64 65c6 3 12 3 18-1M46 76c5 3 10 3 14 1M64 77c5 2 10 2 14-1" fill="none" stroke="var(--color-dorado)" stroke-width="2"/>
        <path d="M48 38c3 2 6 2 9 0M63 40c3 2 6 2 9 0" fill="none" stroke="var(--color-accent)" stroke-width="1.4" opacity=".7"/>
        <circle cx="50" cy="44" r="1.8" fill="var(--color-accent)"/>
        <circle cx="70" cy="44" r="1.8" fill="var(--color-accent)"/>
        <path d="M34 84c8-4 16-5 26-2 10-3 18-2 26 2" fill="none" stroke="var(--color-madera)" stroke-width="3"/>
        <path d="M26 30l2 6 6 2-6 2-2 6-2-6-6-2 6-2 2-6Z" fill="var(--color-accent)"/>
    `,
    dragones_alas: `
        <path d="M52 46C40 34 26 31 16 34c6 10 15 17 27 21" fill="var(--color-salvia)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M68 46c12-12 26-15 36-12-6 10-15 17-27 21" fill="var(--color-salvia)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M20 36c8 2 15 6 21 12M100 36c-8 2-15 6-21 12M24 33c7 1 13 4 18 9M96 33c-7 1-13 4-18 9" fill="none" stroke="var(--color-tinta)" stroke-width="1.4" opacity=".55"/>
        <path d="M60 40c8 0 13 6 13 14 0 10-5 20-13 27-8-7-13-17-13-27 0-8 5-14 13-14Z" fill="var(--color-oliva)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M52 48l-6-4 6-2M68 48l6-4-6-2" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <circle cx="55" cy="50" r="2" fill="var(--color-accent)"/>
        <circle cx="65" cy="50" r="2" fill="var(--color-accent)"/>
        <path d="M55 86c2-4 8-4 10 0" fill="none" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M86 24l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    invierno_copo: `
        <circle cx="60" cy="52" r="30" fill="var(--color-secondary)" opacity=".22" stroke="var(--color-dorado)" stroke-width="2.4"/>
        <path d="M36 82h48l-6 12H42l-6-12Z" fill="var(--color-madera)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M40 82h40" stroke="var(--color-dorado)" stroke-width="3"/>
        <path d="M60 32v40M44 40l32 24M76 40 44 64" stroke="var(--color-ink)" stroke-width="2.2" fill="none"/>
        <path d="M60 34l-5 5M60 34l5 5M60 70l-5-5M60 70l5-5M46 42l1 7M46 42l-7-1M74 42l-1 7M74 42l7-1M46 62l1-7M46 62l-7 1M74 62l-1-7M74 62l7 1" stroke="var(--color-ink)" stroke-width="1.8" fill="none"/>
        <circle cx="44" cy="58" r="2" fill="var(--color-ink)"/>
        <circle cx="76" cy="56" r="2" fill="var(--color-ink)"/>
        <circle cx="52" cy="70" r="1.6" fill="var(--color-ink)"/>
        <circle cx="70" cy="72" r="1.6" fill="var(--color-ink)"/>
        <path d="M86 22l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    invierno_campana: `
        <path d="M54 22c0-5 3-8 6-8s6 3 6 8" fill="none" stroke="var(--color-tinta)" stroke-width="2.6"/>
        <path d="M52 22c-8 0-14 6-14 14" fill="none" stroke="var(--color-danger-strong)" stroke-width="3"/>
        <path d="M68 22c8 0 14 6 14 14" fill="none" stroke="var(--color-danger-strong)" stroke-width="3"/>
        <path d="M44 72c0-22 6-38 16-38s16 16 16 38Z" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M40 72h40l-4 8H44l-4-8Z" fill="var(--color-madera-clara)" stroke="var(--color-tinta)" stroke-width="2"/>
        <circle cx="60" cy="84" r="5" fill="var(--color-tinta)"/>
        <path d="M56 44c2-3 6-3 8 0" fill="none" stroke="var(--color-accent)" stroke-width="2" opacity=".7"/>
        <path d="M50 22c2-6 6-9 10-9s8 3 10 9c-6-2-14-2-20 0Z" fill="var(--color-danger-strong)" stroke="var(--color-tinta)" stroke-width="1.8"/>
        <circle cx="34" cy="46" r="2" fill="var(--color-accent)"/>
        <circle cx="88" cy="54" r="2" fill="var(--color-accent)"/>
    `,
    enamorados_corazon: `
        <path d="M60 96C32 76 22 61 22 46c0-12 9-20 19-20 8 0 15 4 19 13 4-9 11-13 19-13 10 0 19 8 19 20 0 15-10 30-38 50Z" fill="var(--color-danger-strong)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M34 46l26 18 26-18" fill="none" stroke="var(--color-crema)" stroke-width="2.4" opacity=".8"/>
        <path d="M60 52v18" stroke="var(--color-crema)" stroke-width="2.4" opacity=".6"/>
        <circle cx="60" cy="60" r="9" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M60 65c-4-3-6-6-6-9 0-2 2-4 4-4 1 0 2 1 2 2 1-1 2-2 3-2 2 0 4 2 4 4 0 3-2 6-7 9Z" fill="var(--color-danger-strong)"/>
        <path d="M28 30l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        <circle cx="94" cy="66" r="2" fill="var(--color-accent)"/>
    `,
    enamorados_rosa: `
        <path d="M42 92c8-4 16-4 24 0 6-3 12-3 18 0" fill="none" stroke="var(--color-madera)" stroke-width="3"/>
        <path d="M60 84V54" stroke="var(--color-bosque)" stroke-width="4"/>
        <path d="M58 72c-10-2-16-8-18-16 9 0 16 5 20 13" fill="var(--color-salvia)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M62 66c10-2 16-8 18-16-9 0-16 5-20 13" fill="var(--color-oliva)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M60 30c11-8 23-1 21 10 9 3 10 14 2 19 3 10-8 16-16 11-6 9-19 5-20-5-12 0-16-12-9-19-5-9 5-19 15-17 2 1 4 1 7 1Z" fill="var(--color-danger)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M50 38c4-6 12-7 17-3M72 42c5 3 6 9 2 13M64 56c-4 5-11 5-15 1M48 50c-4-3-4-8-1-12" fill="none" stroke="var(--color-danger-strong)" stroke-width="2"/>
        <circle cx="60" cy="46" r="7" fill="var(--color-danger-strong)" stroke="var(--color-tinta)" stroke-width="1.4"/>
        <path d="M60 42c2 2 4 4 4 6a4 4 0 0 1-8 0c0-2 2-4 4-6Z" fill="var(--color-danger)"/>
        <path d="M88 26l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    brujas_calavera: `
        <path d="M60 30c18 0 30 12 30 28 0 10-5 18-12 23v10H42V81c-7-5-12-13-12-23 0-16 12-28 30-28Z" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <ellipse cx="48" cy="56" rx="7" ry="8" fill="var(--color-tinta)"/>
        <ellipse cx="72" cy="56" rx="7" ry="8" fill="var(--color-tinta)"/>
        <circle cx="48" cy="56" r="2.4" fill="var(--color-tertiary)"/>
        <circle cx="72" cy="56" r="2.4" fill="var(--color-tertiary)"/>
        <path d="M60 62l-4 8h8l-4-8Z" fill="var(--color-tinta)"/>
        <path d="M50 82v8M56 80v10M64 80v10M70 82v8" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M50 30c0-6 4-10 10-10s10 4 10 10" fill="none" stroke="var(--color-tinta)" stroke-width="2"/>
        <rect x="52" y="14" width="16" height="14" rx="3" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M60 8c4 4 5 7 5 9a5 5 0 0 1-10 0c0-2 1-5 5-9Z" fill="var(--color-terracota)" stroke="var(--color-tinta)" stroke-width="1.4"/>
        <path d="M86 40l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
    `,
    brujas_pocion: `
        <circle cx="60" cy="64" r="24" fill="var(--color-secondary)" opacity=".3" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <path d="M50 46c-5-4-6-10-6-14h32c0 4-1 10-6 14" fill="none" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M52 32h16v12l6 8H46l6-8V32Z" fill="var(--color-secondary)" opacity=".45" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M38 64a22 22 0 0 0 44 0c0-8-10-10-22-10s-22 2-22 10Z" fill="var(--color-tertiary)" opacity=".75" stroke="var(--color-tinta)" stroke-width="2"/>
        <circle cx="52" cy="64" r="3" fill="var(--color-secondary)"/>
        <circle cx="66" cy="70" r="2.4" fill="var(--color-secondary)"/>
        <circle cx="60" cy="58" r="2" fill="var(--color-secondary)"/>
        <rect x="50" y="14" width="20" height="10" rx="3" fill="var(--color-madera)" stroke="var(--color-tinta)" stroke-width="2"/>
        <path d="M44 58h32" stroke="var(--color-dorado)" stroke-width="2"/>
        <circle cx="88" cy="34" r="2" fill="var(--color-accent)"/>
        <circle cx="32" cy="46" r="1.8" fill="var(--color-accent)"/>
    `,
    flores_ramo: `
        <path d="M60 82c-8-8-12-18-12-28M60 82c0-10 3-20 8-28M60 82c4-8 10-14 18-18M60 82c-6-8-14-12-24-14" fill="none" stroke="var(--color-bosque)" stroke-width="3"/>
        <path d="M42 80l18 28 18-28Z" fill="var(--color-pergamino)" stroke="var(--color-dorado)" stroke-width="2"/>
        <path d="M46 80h28l-4 10H50l-4-10Z" fill="var(--color-madera-clara)" opacity=".65"/>
        <circle cx="44" cy="48" r="9" fill="var(--color-terracota)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <circle cx="44" cy="48" r="3" fill="var(--color-accent)"/>
        <circle cx="70" cy="44" r="9" fill="var(--color-danger)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <circle cx="70" cy="44" r="3" fill="var(--color-accent)"/>
        <circle cx="82" cy="62" r="8" fill="var(--color-tertiary)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <circle cx="82" cy="62" r="2.6" fill="var(--color-accent)"/>
        <circle cx="36" cy="66" r="8" fill="var(--color-danger-strong)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <circle cx="36" cy="66" r="2.6" fill="var(--color-accent)"/>
        <path d="M30 58c4-2 8-2 12 0M64 36c3-3 7-4 11-3M86 54c4 1 7 4 8 8" fill="none" stroke="var(--color-salvia)" stroke-width="2"/>
        <path d="M60 90c4 2 8 2 12 0" fill="none" stroke="var(--color-danger-strong)" stroke-width="2.4"/>
    `,
    flores_planta: `
        <path d="M38 78h44l-7 26H45l-7-26Z" fill="var(--color-terracota)" stroke="var(--color-tinta)" stroke-width="2.2"/>
        <rect x="34" y="72" width="52" height="9" rx="4" fill="var(--color-madera-clara)" stroke="var(--color-tinta)" stroke-width="2"/>
        <ellipse cx="60" cy="76" rx="21" ry="4" fill="var(--color-tinta)" opacity=".45"/>
        <path d="M60 74V40" stroke="var(--color-bosque)" stroke-width="3"/>
        <path d="M60 58c-10-2-16-9-17-18 10 0 17 6 19 15" fill="var(--color-oliva)" stroke="var(--color-tinta)" stroke-width="1.8"/>
        <path d="M60 52c10-2 16-9 17-18-10 0-17 6-19 15" fill="var(--color-salvia)" stroke="var(--color-tinta)" stroke-width="1.8"/>
        <path d="M60 40c-6-2-10-7-11-13 7 0 12 4 14 10" fill="var(--color-oliva)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M60 46c6-2 10-7 11-13-7 0-12 4-14 10" fill="var(--color-salvia)" stroke="var(--color-tinta)" stroke-width="1.6"/>
        <path d="M50 54c2-5 5-8 9-10M70 48c-2-5-5-8-9-10" fill="none" stroke="var(--color-tinta)" stroke-width="1.2" opacity=".4"/>
        <path d="M86 30l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        <circle cx="34" cy="52" r="1.8" fill="var(--color-accent)"/>
    `,
    star: `
        <path d="M60 22l9 22 24 2-18 15 6 24-21-13-21 13 6-24-18-15 24-2 9-22Z" fill="var(--color-accent)" stroke="var(--color-tinta)" stroke-width="2"/>
        <circle cx="60" cy="60" r="6" fill="var(--color-crema)"/>
    `,
};

const keys = Object.keys(artworks);

const cut = computed(() => {
    const index = keys.indexOf(props.itemKey);

    return cuts[(index < 0 ? 0 : index) % cuts.length];
});

const art = computed(() => artworks[props.itemKey] ?? artworks.star);
</script>

<template>
    <span
        class="event-sticker"
        :class="{ 'event-sticker--locked': locked }"
        :style="{ width: `${size}px`, height: `${size}px` }"
    >
        <svg class="event-sticker__svg" viewBox="0 0 120 120" aria-hidden="true" focusable="false">
            <path class="event-sticker__cut" :d="cut" />

            <g v-if="! locked" class="event-sticker__art" v-html="art" />
            <g v-else class="event-sticker__locked">
                <path
                    :d="cut"
                    fill="var(--color-surface-raised)"
                    opacity=".8"
                    transform="translate(60 60) scale(.82) translate(-60 -60)"
                />
                <text
                    x="60"
                    y="72"
                    text-anchor="middle"
                    font-size="30"
                    font-family="Georgia, serif"
                    fill="var(--color-ink-muted)"
                >?</text>
            </g>
        </svg>
    </span>
</template>
