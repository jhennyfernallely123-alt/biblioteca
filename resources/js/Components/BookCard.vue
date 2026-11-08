<script setup>
import { computed, ref } from 'vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    book: { type: Object, required: true },
    dragging: { type: Boolean, default: false },
    dropPosition: { type: String, default: null },
    reading: { type: Boolean, default: false },
});

const emit = defineEmits(['open']);

const { t } = useI18n();
const failed = ref(false);

const coverUrl = computed(() =>
    props.book.cover_path && !failed.value ? `/covers/${props.book.id}` : null
);

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
        class="book-drag-source group relative block w-full cursor-grab"
        draggable="false"
        :data-book-id="book.id"
        :class="{
            'opacity-30': dragging,
            'book-drop-before': dropPosition === 'before',
            'book-drop-after': dropPosition === 'after',
        }"
        :title="`${book.title} — ${book.author}`"
        @click="onClick"
    >
        <div
            class="book-glow relative aspect-[2/3] overflow-hidden rounded-card bg-crema shadow-sm ring-1 ring-madera/35"
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

            <span
                v-if="reading"
                class="pointer-events-none absolute left-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-scrim/85 text-dorado ring-1 ring-dorado/40"
                aria-hidden="true"
            >
                <svg class="h-3 w-3" viewBox="0 0 12 12" fill="currentColor">
                    <path d="M3.5 1.8v8.4L10 6z" />
                </svg>
            </span>

            <div v-if="reading" class="pointer-events-none absolute inset-x-0 bottom-0 h-1.5 bg-scrim/70" aria-hidden="true">
                <div class="h-full bg-accent" :style="{ width: `${book.progress ?? 0}%` }" />
            </div>
        </div>
    </a>
</template>
