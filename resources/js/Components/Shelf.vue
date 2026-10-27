<script setup>
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import BookCard from './BookCard.vue';
import BookSpine from './BookSpine.vue';
import EmptyShelf from './EmptyShelf.vue';
import ShelfBoard from './ShelfBoard.vue';
import { useI18n } from '@/i18n';

const props = defineProps({ shelf: { type: Object, required: true } });
const { t } = useI18n();

const countLabel = computed(() =>
    props.shelf.books.length === 1
        ? t('ui.one_book')
        : t('ui.books_count', { count: props.shelf.books.length })
);

function toggleView() {
    router.patch(
        `/shelves/${props.shelf.id}`,
        {
            name: props.shelf.name,
            view_mode: props.shelf.view_mode === 'covers' ? 'spines' : 'covers',
        },
        { preserveScroll: true }
    );
}
</script>

<template>
    <section>
        <div class="mb-2 flex items-baseline justify-between gap-2">
            <h2 class="text-2xl">{{ shelf.display_name ?? shelf.name }}</h2>
            <div class="flex items-center gap-3">
                <span class="text-sm text-tinta/65">{{ countLabel }}</span>
                <button
                    type="button"
                    class="rounded-control border border-madera/35 px-2 py-1 text-xs text-tinta/75 hover:bg-crema"
                    @click="toggleView"
                >
                    {{ shelf.view_mode === 'covers' ? t('ui.show_spines') : t('ui.show_covers') }}
                </button>
            </div>
        </div>

        <EmptyShelf v-if="shelf.books.length === 0" />

        <div
            v-else-if="shelf.view_mode === 'covers'"
            class="grid grid-cols-[repeat(auto-fill,minmax(88px,1fr))] items-end gap-4"
        >
            <BookCard v-for="book in shelf.books" :key="book.id" :book="book" />
        </div>

        <div v-else class="flex items-end gap-2 overflow-x-auto pb-1">
            <BookSpine v-for="book in shelf.books" :key="book.id" :book="book" />
        </div>

        <ShelfBoard />
    </section>
</template>
