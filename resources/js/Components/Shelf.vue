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
    reading: { type: Boolean, default: false },
});
const emit = defineEmits(['open-book']);
const { t } = useI18n();

const isDropTarget = computed(() => props.dropTargetId === props.shelf.id);

const items = computed(() => props.shelf.books ?? []);

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

function toggleBook(book) {
    router.patch(
        `/books/${book.id}/view-mode`,
        {
            view_mode: book.view_mode === 'spines' ? 'covers' : 'spines',
        },
        { preserveScroll: true, preserveState: true }
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
                    class="rounded-control bg-secondary px-2 py-1 text-xs font-semibold text-page"
                >
                    {{ t('ui.drop_here') }}
                </span>
                <span class="text-sm text-ink-muted">{{ countLabel }}</span>
            </div>
        </div>

        <div class="shelf-case">
            <div class="shelf-case__books">
                <EmptyShelf v-if="items.length === 0" />

                <div
                    v-else
                    class="scrollbar-hidden flex flex-row items-end gap-3 overflow-x-auto pb-1 sm:flex-wrap sm:overflow-visible sm:pb-0"
                >
                    <template v-for="item in items" :key="item.id">
                        <div class="group relative shrink-0" :class="item.view_mode === 'spines' ? '' : 'w-[100px] sm:w-[110px]'">
                            <BookSpine
                                v-if="item.view_mode === 'spines'"
                                :book="item"
                                :dragging="draggingBookId === item.id"
                                :drop-position="dropPositionFor(item)"
                                :reading="reading"
                                @open="emit('open-book', $event)"
                            />
                            <BookCard
                                v-else
                                :book="item"
                                :dragging="draggingBookId === item.id"
                                :drop-position="dropPositionFor(item)"
                                :reading="reading"
                                @open="emit('open-book', $event)"
                            />

                            <button
                                type="button"
                                class="absolute -right-1 -top-1 z-10 flex h-7 w-7 items-center justify-center rounded-full border border-edge bg-surface-raised text-ink shadow-card transition-opacity focus-visible:opacity-100 sm:opacity-0 sm:group-hover:opacity-100 sm:focus-visible:opacity-100"
                                :aria-label="t('ui.switch_view')"
                                :title="t('ui.switch_view')"
                                @click.prevent="toggleBook(item)"
                            >
                                <svg
                                    v-if="item.view_mode === 'spines'"
                                    class="h-4 w-4"
                                    viewBox="0 0 14 14"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <rect x="3.5" y="1.5" width="7" height="11" rx="1.4" stroke="currentColor" stroke-width="1.3" />
                                    <path d="M5.75 2v10" stroke="currentColor" stroke-width="1.3" />
                                </svg>
                                <svg
                                    v-else
                                    class="h-4 w-4"
                                    viewBox="0 0 14 14"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <path d="M4 2.5v9M7 2.5v9M10 2.5v9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <ShelfBoard />
        </div>
    </section>
</template>
