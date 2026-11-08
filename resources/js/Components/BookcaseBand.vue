<script setup>
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import BookCard from './BookCard.vue';
import BookSpine from './BookSpine.vue';
import BookcaseDecoration from './BookcaseDecoration.vue';
import EmptyShelf from './EmptyShelf.vue';
import SeasonalGarland from './SeasonalGarland.vue';
import ShelfBoard from './ShelfBoard.vue';
import { useI18n } from '@/i18n';
import { mergeShelfItems } from '@/shelfItems';

const props = defineProps({
    band: { type: Object, required: true },
    season: { type: String, default: 'autumn' },
    dragActive: { type: Boolean, default: false },
    dropTargetId: { type: Number, default: null },
    draggingBookId: { type: Number, default: null },
    draggingDecorationId: { type: Number, default: null },
    insertion: { type: Object, default: null },
});

const { t } = useI18n();

const items = computed(() => mergeShelfItems(props.band.books, props.band.decorations));

const isDropTarget = computed(() => props.dropTargetId === props.band.id);

function toggleView(book) {
    router.patch(
        `/books/${book.id}/view-mode`,
        { view_mode: book.view_mode === 'spines' ? 'covers' : 'spines' },
        { preserveScroll: true, preserveState: true }
    );
}

function dropPositionForDecoration(decoration) {
    if (
        props.insertion?.shelfId !== props.band.id ||
        props.insertion?.decorationId !== decoration.id
    ) {
        return null;
    }

    return props.insertion.position;
}

function dropPositionForBook(book) {
    if (props.insertion?.shelfId !== props.band.id || props.insertion?.bookId !== book.id) {
        return null;
    }

    return props.insertion.position;
}
</script>

<template>
    <section
        :data-shelf-id="band.id"
        :class="{
            'shelf-drag-active': dragActive,
            'shelf-drop-target': isDropTarget,
        }"
    >
        <div class="shelf-case">
            <SeasonalGarland
                :season="season"
                compact
                class="pointer-events-none absolute inset-x-2 top-0 z-10"
            />

            <div class="shelf-case__books">
                <EmptyShelf v-if="items.length === 0" />

                <div
                    v-else
                    class="scrollbar-hidden flex flex-row items-end justify-start gap-3 overflow-x-auto pb-1 sm:justify-center sm:overflow-visible sm:pb-0"
                >
                    <template v-for="item in items" :key="item.key">
                        <div
                            v-if="item.type === 'book'"
                            class="group relative shrink-0"
                            :class="item.book.view_mode === 'spines' ? '' : 'w-[100px] sm:w-[110px]'"
                        >
                            <BookSpine
                                v-if="item.book.view_mode === 'spines'"
                                :book="item.book"
                                :dragging="draggingBookId === item.book.id"
                                :drop-position="dropPositionForBook(item.book)"
                            />
                            <BookCard
                                v-else
                                :book="item.book"
                                :dragging="draggingBookId === item.book.id"
                                :drop-position="dropPositionForBook(item.book)"
                            />

                            <button
                                type="button"
                                class="absolute -right-1 -top-1 z-10 flex h-7 w-7 items-center justify-center rounded-full border border-edge bg-surface-raised text-ink shadow-card transition-opacity focus-visible:opacity-100 sm:opacity-0 sm:group-hover:opacity-100 sm:focus-visible:opacity-100"
                                :aria-label="t('ui.switch_view')"
                                :title="t('ui.switch_view')"
                                @pointerdown.stop
                                @touchstart.stop
                                @click.prevent.stop="toggleView(item.book)"
                            >
                                <svg
                                    v-if="item.book.view_mode === 'spines'"
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

                        <BookcaseDecoration
                            v-else
                            :decoration="item.decoration"
                            :dragging="draggingDecorationId === item.decoration.id"
                            :drop-position="dropPositionForDecoration(item.decoration)"
                        />
                    </template>
                </div>
            </div>

            <ShelfBoard />
        </div>
    </section>
</template>
