<script setup>
import { computed, ref } from 'vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    book: { type: Object, required: true },
    dragging: { type: Boolean, default: false },
    dropPosition: { type: String, default: null },
});

const { t } = useI18n();
const failed = ref(false);

const coverUrl = computed(() =>
    props.book.cover_path && !failed.value ? `/covers/${props.book.id}` : null
);
</script>

<template>
    <a
        :href="`/books/${book.id}/edit`"
        class="book-drag-source group relative block w-full cursor-grab"
        draggable="false"
        :data-book-id="book.id"
        :class="{
            'opacity-30': dragging,
            'book-drop-before': dropPosition === 'before',
            'book-drop-after': dropPosition === 'after',
        }"
        :title="`${book.title} — ${book.author}`"
    >
        <div
            class="book-glow aspect-[2/3] overflow-hidden rounded-card bg-crema shadow-sm ring-1 ring-madera/35"
        >
            <img
                v-if="coverUrl"
                :src="coverUrl"
                :alt="t('ui.cover_alt', { title: book.title })"
                class="h-full w-full object-cover"
                loading="lazy"
                @error="failed = true"
            >
            <div v-else class="flex h-full flex-col justify-center p-2 text-center">
                <span class="font-serif text-base font-semibold text-tinta">{{ book.title }}</span>
                <span class="mt-1 text-xs text-tinta/65">{{ book.author }}</span>
            </div>
        </div>
    </a>
</template>
