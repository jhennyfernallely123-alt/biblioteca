<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import BookcaseBand from '@/Components/BookcaseBand.vue';
import BookDragGhost from '@/Components/BookDragGhost.vue';
import DecorationDragGhost from '@/Components/DecorationDragGhost.vue';
import EmptyShelf from '@/Components/EmptyShelf.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';
import { BOOKS_PER_BAND, buildBands, insertBookInBand } from '@/bookcaseBands';
import { useLibraryDrag } from '@/useLibraryDrag';
import { currentSeason } from '@/seasons';

const props = defineProps({
    books: { type: Array, default: () => [] },
    decorations: { type: Array, default: () => [] },
});

const { t } = useI18n();
const season = currentSeason();

const localBooks = ref(props.books.map((book) => ({ ...book })));
const localDecorations = ref(props.decorations.map((decoration) => ({ ...decoration })));
const notice = ref(null);
let noticeTimer = null;

watch(
    () => props.books,
    (books) => {
        localBooks.value = (books ?? []).map((book) => ({ ...book }));
    },
    { deep: true }
);

watch(
    () => props.decorations,
    (decorations) => {
        localDecorations.value = (decorations ?? []).map((decoration) => ({ ...decoration }));
    },
    { deep: true }
);

const bands = computed(() => buildBands(localBooks.value, localDecorations.value));
const visibleBands = computed(() =>
    bands.value.filter((band) => band.books.length > 0 || band.decorations.length > 0)
);

function showNotice(type, text) {
    window.clearTimeout(noticeTimer);
    notice.value = { type, text };
    noticeTimer = window.setTimeout(() => {
        notice.value = null;
    }, 4000);
}

function moveBook(book, insertion) {
    const band = visibleBands.value.find((item) => item.id === insertion?.shelfId);

    if (!band) {
        return;
    }

    const others = band.books.filter((item) => item.id !== book.id);
    const after = insertion?.position !== 'before';
    let index = others.length;

    if (insertion?.bookId) {
        const target = others.findIndex((item) => item.id === insertion.bookId);

        if (target !== -1) {
            index = target + (after ? 1 : 0);
        }
    } else if (!after) {
        index = 0;
    }

    index = Math.max(0, Math.min(index, others.length, BOOKS_PER_BAND - 1));

    const snapshot = localBooks.value;
    localBooks.value = insertBookInBand(localBooks.value, book, band.id, index);

    let settled = false;

    router.patch(
        '/bookcase/move',
        { book_id: book.id, band: band.id, position: index },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                settled = true;
            },
            onError: () => {
                settled = true;
                localBooks.value = snapshot;
                showNotice('error', t('ui.book_move_error'));
            },
            onFinish: () => {
                if (settled) {
                    return;
                }

                localBooks.value = snapshot;
                showNotice('error', t('ui.book_move_error'));
            },
        }
    );
}

function moveDecoration(decoration, bandId, anchor) {
    const band = visibleBands.value.find((item) => item.id === bandId);

    if (!band || !anchor) {
        return;
    }

    const anchorBook = band.books.find((book) => book.id === anchor.afterBookId);
    const shelfId = anchorBook?.shelf_id ?? band.books[0]?.shelf_id ?? decoration.shelf_id;

    if (shelfId == null) {
        return;
    }

    const next = localDecorations.value.map((item) => ({ ...item }));
    const moved = next.find((item) => item.id === decoration.id);

    if (!moved) {
        return;
    }

    moved.shelf_id = shelfId;
    moved.after_book_id = anchor.afterBookId;

    const group = next
        .filter(
            (item) =>
                item.id !== moved.id &&
                item.shelf_id === shelfId &&
                (item.after_book_id ?? null) === (anchor.afterBookId ?? null)
        )
        .sort((a, b) => a.sort - b.sort || a.id - b.id);

    const index = Math.max(0, Math.min(anchor.sort, group.length));

    group.splice(index, 0, moved);
    group.forEach((item, position) => {
        item.sort = position;
    });

    const snapshot = localDecorations.value;
    localDecorations.value = next;

    let settled = false;

    router.patch(
        `/decorations/${decoration.id}`,
        {
            shelf_id: shelfId,
            after_book_id: anchor.afterBookId,
            sort: anchor.sort,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                settled = true;
            },
            onError: () => {
                settled = true;
                localDecorations.value = snapshot;
                showNotice('error', t('ui.decoration_move_error'));
            },
            onFinish: () => {
                if (settled) {
                    return;
                }

                localDecorations.value = snapshot;
                showNotice('error', t('ui.decoration_move_error'));
            },
        }
    );
}

function handleDrop(dragged, bandId, payload) {
    if (dragged.type === 'decoration') {
        moveDecoration(dragged.item, bandId, payload);

        return;
    }

    moveBook(dragged.item, payload);
}

const {
    draggedItem,
    dragMode,
    pointer,
    isDragging,
    dropTargetId,
    insertion,
    onPointerDown,
    onTouchStart,
    onContextMenu,
    onClickCapture,
} = useLibraryDrag(() => visibleBands.value, handleDrop, { virtual: true });

onBeforeUnmount(() => {
    window.clearTimeout(noticeTimer);
});
</script>

<template>
    <Head :title="t('ui.library_title')" />
    <AuthenticatedLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-3xl md:text-4xl">{{ t('ui.library_title') }}</h1>
            <Link href="/books/create" class="rounded-control bg-arcane px-4 py-2 text-moon hover:bg-arcane-strong">
                {{ t('ui.add_book') }}
            </Link>
        </div>

        <p id="bookcase-drag-hint" class="sr-only">{{ t('ui.drag_hint') }}</p>

        <EmptyShelf v-if="localBooks.length === 0" :message="t('ui.empty_library')" />

        <div
            v-else
            class="bookcase"
            aria-describedby="bookcase-drag-hint"
            @pointerdown="onPointerDown"
            @touchstart="onTouchStart"
            @contextmenu="onContextMenu"
            @click.capture="onClickCapture"
            @dragstart.prevent
        >
            <div class="bookcase__frame">
                <BookcaseBand
                    v-for="band in visibleBands"
                    :key="band.id"
                    :band="band"
                    :season="season"
                    :drag-active="isDragging"
                    :drop-target-id="dropTargetId"
                    :dragging-book-id="draggedItem?.type === 'book' ? draggedItem.item.id : null"
                    :dragging-decoration-id="draggedItem?.type === 'decoration' ? draggedItem.item.id : null"
                    :insertion="insertion"
                />
            </div>

            <div class="bookcase__plinth" aria-hidden="true">
                <span class="bookcase__door" />
                <span class="bookcase__door" />
            </div>
        </div>

        <BookDragGhost
            v-if="draggedItem?.type === 'book'"
            :book="draggedItem.item"
            :mode="dragMode"
            :x="pointer.x"
            :y="pointer.y"
        />

        <DecorationDragGhost
            v-else-if="draggedItem?.type === 'decoration'"
            :decoration="draggedItem.item"
            :x="pointer.x"
            :y="pointer.y"
        />

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="notice"
                role="status"
                aria-live="polite"
                class="pointer-events-none fixed bottom-20 left-1/2 z-50 w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 rounded-card border px-4 py-3 text-sm shadow-modal md:bottom-6"
                :class="
                    notice.type === 'error'
                        ? 'border-danger/40 bg-night-raised text-danger'
                        : 'border-success/40 bg-night-raised text-moon'
                "
            >
                {{ notice.text }}
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>
