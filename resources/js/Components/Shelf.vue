<script setup>
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import BookCard from './BookCard.vue';
import BookSpine from './BookSpine.vue';
import EmptyShelf from './EmptyShelf.vue';
import ShelfBoard from './ShelfBoard.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    shelf: { type: Object, required: true },
    dragActive: { type: Boolean, default: false },
    dropTargetId: { type: Number, default: null },
    draggingBookId: { type: Number, default: null },
    insertion: { type: Object, default: null },
});
const { t } = useI18n();

const isDropTarget = computed(() => props.dropTargetId === props.shelf.id);

function dropPositionFor(book) {
    if (
        props.insertion?.shelfId !== props.shelf.id ||
        props.insertion?.bookId !== book.id
    ) {
        return null;
    }

    return props.insertion.position;
}

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
    <section
        :data-shelf-id="shelf.id"
        :class="{
            'shelf-drag-active': dragActive,
            'shelf-drop-target': isDropTarget,
        }"
    >
        <div class="mb-2 flex items-baseline justify-between gap-2">
            <h2 class="text-2xl">{{ shelf.display_name ?? shelf.name }}</h2>
            <div class="flex flex-wrap items-center justify-end gap-3">
                <span
                    v-if="isDropTarget"
                    class="rounded-control bg-magic px-2 py-1 text-xs font-semibold text-night"
                >
                    {{ t('ui.drop_here') }}
                </span>
                <span class="text-sm text-mist">{{ countLabel }}</span>
                <button
                    type="button"
                    class="rounded-control border border-line px-2 py-1 text-xs text-moon/80 hover:bg-panel-raised"
                    @click="toggleView"
                >
                    {{ shelf.view_mode === 'covers' ? t('ui.show_spines') : t('ui.show_covers') }}
                </button>
            </div>
        </div>

        <div class="shelf-case">
            <div class="shelf-case__books">
                <EmptyShelf v-if="shelf.books.length === 0" />

                <div
                    v-else-if="shelf.view_mode === 'covers'"
                    class="flex flex-row items-end gap-4 overflow-x-auto pb-1 [&>a]:min-w-[100px] sm:grid sm:grid-cols-[repeat(auto-fill,minmax(88px,1fr))] sm:overflow-visible sm:pb-0"
                >
                    <BookCard
                        v-for="book in shelf.books"
                        :key="book.id"
                        :book="book"
                        :dragging="draggingBookId === book.id"
                        :drop-position="dropPositionFor(book)"
                    />
                </div>

                <div v-else class="flex items-end gap-2 overflow-x-auto pb-1">
                    <BookSpine
                        v-for="book in shelf.books"
                        :key="book.id"
                        :book="book"
                        :dragging="draggingBookId === book.id"
                        :drop-position="dropPositionFor(book)"
                    />
                </div>
            </div>

            <ShelfBoard />
        </div>
    </section>
</template>
