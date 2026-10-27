<script setup>
import { computed } from 'vue';
import { useI18n } from '@/i18n';

const props = defineProps({ book: { type: Object, required: true } });
const { t } = useI18n();

const palette = ['bg-bosque', 'bg-oliva', 'bg-madera', 'bg-terracota', 'bg-salvia'];

const hash = computed(() => {
    let value = 0;
    for (const char of props.book.title) value = (value * 31 + char.charCodeAt(0)) % 997;
    return value;
});

const width = computed(() => 18 + (hash.value % 15));
const color = computed(() => palette[hash.value % palette.length]);
</script>

<template>
    <a
        :href="`/books/${book.id}/edit`"
        class="h-40 rounded-sm text-crema shadow-sm transition hover:-translate-y-1"
        :class="color"
        :style="{ width: `${width}px` }"
        :aria-label="`${book.title}, ${book.author ?? t('ui.unknown_author_lower')}`"
        :title="`${book.title} — ${book.author ?? ''}`"
    />
</template>
