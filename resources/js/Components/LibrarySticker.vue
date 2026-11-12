<script setup>
import { computed } from 'vue';
import { stickers } from './stickerArt.js';

const props = defineProps({
    sticker: { type: String, default: 'maquina_libro' },
    size: { type: Number, default: 96 },
    locked: { type: Boolean, default: false },
});

const current = computed(() => stickers[props.sticker] ?? stickers.maquina_libro);
</script>

<template>
    <span
        class="library-sticker"
        :class="{ 'library-sticker--locked': locked }"
        :style="{ width: `${size}px`, height: `${size}px` }"
    >
        <svg class="library-sticker__svg" viewBox="0 0 120 120" aria-hidden="true" focusable="false">
            <path
                class="library-sticker__cut"
                :d="current.cut"
                fill="var(--color-crema)"
                stroke="var(--color-crema)"
                stroke-width="8"
                stroke-linejoin="round"
            />

            <g v-if="!locked" class="library-sticker__art" v-html="current.art" />
            <g v-else class="library-sticker__locked">
                <path
                    :d="current.cut"
                    fill="var(--color-surface-raised)"
                    stroke="var(--color-surface-raised)"
                    stroke-width="8"
                    stroke-linejoin="round"
                    opacity=".8"
                    transform="translate(60 60) scale(.92) translate(-60 -60)"
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
