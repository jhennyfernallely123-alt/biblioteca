<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from '@/i18n';
import { coverColor } from '@/coverColor';

const props = defineProps({
    book: { type: Object, required: true },
    dragging: { type: Boolean, default: false },
    dropPosition: { type: String, default: null },
    reading: { type: Boolean, default: false },
});
const emit = defineEmits(['open']);
const { t } = useI18n();

const palette = ['bg-bosque', 'bg-oliva', 'bg-madera', 'bg-terracota', 'bg-salvia'];

const hash = computed(() => {
    let value = 0;
    for (const char of props.book.title) value = (value * 31 + char.charCodeAt(0)) % 997;
    return value;
});

const paletteClass = computed(() => palette[hash.value % palette.length]);
const extracted = ref(null);

const metrics = computed(() => {
    const length = props.book.title.length;
    const base = 18 + (hash.value % 15);

    for (const fontSize of [10, 9, 8]) {
        const perColumn = Math.max(1, Math.floor(144 / (fontSize * 0.78)));
        const columns = Math.max(1, Math.ceil(length / perColumn));
        const width = 8 + Math.ceil(columns * (fontSize * 1.15 + 0.4));

        if (width <= 88) {
            return { fontSize, width: Math.max(base, width) };
        }
    }

    return { fontSize: 8, width: 88 };
});

const width = computed(() => metrics.value.width);

const background = computed(() => extracted.value?.background ?? null);
const textColor = computed(() => extracted.value?.text ?? 'var(--color-crema)');
const colorClass = computed(() => (background.value ? '' : paletteClass.value));

onMounted(async () => {
    extracted.value = await coverColor(props.book.id, props.book.cover_path);
});

function onClick(event) {
    if (!props.reading) {
        return;
    }

    event.preventDefault();
    emit('open', props.book);
}
</script>

<template>
    <a
        :href="`/books/${book.id}/edit`"
        class="book-drag-source book-glow relative flex h-40 cursor-grab items-center justify-center rounded-sm text-crema shadow-sm ring-1 ring-tinta/10"
        draggable="false"
        :data-book-id="book.id"
        :class="[
            colorClass,
            {
                'opacity-30': dragging,
                'book-drop-before': dropPosition === 'before',
                'book-drop-after': dropPosition === 'after',
            },
        ]"
        :style="{ width: `${width}px`, backgroundColor: background ?? undefined, color: textColor }"
        :aria-label="`${book.title}, ${book.author ?? t('ui.unknown_author_lower')}`"
        :title="`${book.title} — ${book.author ?? ''}`"
        @click="onClick"
    >
        <span
            class="max-h-full max-w-full px-1 py-2 font-medium leading-[1.15] tracking-[0.08em] [overflow-wrap:anywhere] [writing-mode:vertical-rl]"
            :style="{ fontSize: `${metrics.fontSize}px` }"
        >
            {{ book.title }}
        </span>

        <span
            v-if="reading"
            class="pointer-events-none absolute left-1/2 top-1 z-10 flex h-4 w-4 -translate-x-1/2 items-center justify-center rounded-full bg-night/85 text-gold ring-1 ring-gold/40"
            aria-hidden="true"
        >
            <svg class="h-2.5 w-2.5" viewBox="0 0 12 12" fill="currentColor">
                <path d="M3.5 1.8v8.4L10 6z" />
            </svg>
        </span>

        <div v-if="reading" class="pointer-events-none absolute inset-x-0 bottom-0 h-1 bg-night/70" aria-hidden="true">
            <div class="h-full bg-gold" :style="{ width: `${book.progress ?? 0}%` }" />
        </div>
    </a>
</template>
