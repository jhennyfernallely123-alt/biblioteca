<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import BookDragGhost from '@/Components/BookDragGhost.vue';
import ReadingLightbox from '@/Components/ReadingLightbox.vue';
import Shelf from '@/Components/Shelf.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';
import { moveBookWithin, sameBookOrder } from '@/libraryOrder';
import { useLibraryDrag } from '@/useLibraryDrag';

const props = defineProps({
    shelves: { type: Array, required: true },
    readingBooks: { type: Array, default: () => [] },
});

const { t } = useI18n();

function cloneShelves(shelves) {
    return shelves.map((shelf) => ({
        ...shelf,
        books: (shelf.books ?? []).map((book) => ({ ...book })),
    }));
}

const localShelves = ref(cloneShelves(props.shelves));
const localReading = ref(props.readingBooks.map((book) => ({ ...book })));
const selectedBook = ref(null);
const notice = ref(null);
let noticeTimer = null;

watch(
    () => props.shelves,
    (shelves) => {
        localShelves.value = cloneShelves(shelves);
    },
    { deep: true }
);

watch(
    () => props.readingBooks,
    (books) => {
        localReading.value = (books ?? []).map((book) => ({ ...book }));
    },
    { deep: true }
);

const readingShelf = computed(() => ({
    id: 'reading',
    name: t('ui.reading_shelf'),
    display_name: t('ui.reading_shelf'),
    books: localReading.value,
}));

function openReading(book) {
    selectedBook.value = book;
}

function applyReadingUpdate(book) {
    localReading.value =
        book.status === 'reading'
            ? localReading.value.map((item) => (item.id === book.id ? { ...item, ...book } : item))
            : localReading.value.filter((item) => item.id !== book.id);

    localShelves.value.forEach((shelf) => {
        const target = shelf.books.find((item) => item.id === book.id);

        if (target) {
            Object.assign(target, book);
        }
    });

    if (selectedBook.value?.id === book.id) {
        selectedBook.value = { ...selectedBook.value, ...book };
    }
}

function showNotice(type, text) {
    window.clearTimeout(noticeTimer);
    notice.value = { type, text };
    noticeTimer = window.setTimeout(() => {
        notice.value = null;
    }, 4000);
}

function moveBook(book, targetShelfId) {
    const sourceShelf = localShelves.value.find((shelf) =>
        shelf.books.some((item) => item.id === book.id)
    );
    const targetShelf = localShelves.value.find((shelf) => shelf.id === targetShelfId);

    if (!sourceShelf || !targetShelf || sourceShelf.id === targetShelf.id) {
        return;
    }

    const snapshot = cloneShelves(localShelves.value);
    const index = sourceShelf.books.findIndex((item) => item.id === book.id);
    const [moved] = sourceShelf.books.splice(index, 1);

    moved.shelf_id = targetShelf.id;
    targetShelf.books.push(moved);

    let settled = false;

    router.patch(
        `/books/${book.id}/move`,
        { shelf_id: targetShelf.id },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                settled = true;
                showNotice(
                    'success',
                    t('ui.book_moved', {
                        title: book.title,
                        shelf: targetShelf.display_name ?? targetShelf.name,
                    })
                );
            },
            onError: () => {
                settled = true;
                localShelves.value = snapshot;
                showNotice('error', t('ui.book_move_error'));
            },
            onFinish: () => {
                if (settled) {
                    return;
                }

                localShelves.value = snapshot;
                showNotice('error', t('ui.book_move_error'));
            },
        }
    );
}

function reorderBook(book, shelfId, target) {
    const shelf = localShelves.value.find((item) => item.id === shelfId);

    if (!shelf) {
        return;
    }

    const nextBooks = moveBookWithin(
        shelf.books,
        book.id,
        target.bookId,
        target.position
    );

    if (sameBookOrder(shelf.books, nextBooks)) {
        return;
    }

    const snapshot = cloneShelves(localShelves.value);
    shelf.books = nextBooks;

    let settled = false;

    router.patch(
        '/books/reorder',
        {
            shelf_id: shelf.id,
            ids: nextBooks.map((item) => item.id),
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                settled = true;
                showNotice('success', t('ui.book_reordered'));
            },
            onError: () => {
                settled = true;
                localShelves.value = snapshot;
                showNotice('error', t('ui.book_reorder_error'));
            },
            onFinish: () => {
                if (settled) {
                    return;
                }

                localShelves.value = snapshot;
                showNotice('error', t('ui.book_reorder_error'));
            },
        }
    );
}

function handleDrop(dragged, targetShelfId, target = null) {
    if (target) {
        reorderBook(dragged.item, targetShelfId, target);

        return;
    }

    moveBook(dragged.item, targetShelfId);
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
} = useLibraryDrag(() => localShelves.value, handleDrop);

onBeforeUnmount(() => {
    window.clearTimeout(noticeTimer);
});
</script>

<template>
    <Head :title="t('ui.shelves_title')" />
    <AuthenticatedLayout>
        <div class="mb-6 flex flex-wrap items-center justify-end gap-3">
            <Link href="/books/create" class="rounded-control bg-accent px-4 py-2 text-on-accent hover:bg-accent-strong">
                {{ t('ui.add_book') }}
            </Link>
        </div>

        <p id="book-drag-hint" class="sr-only">{{ t('ui.drag_hint') }}</p>

        <Shelf
            v-if="localReading.length"
            :shelf="readingShelf"
            reading
            class="mb-8"
            @open-book="openReading"
        />

        <div
            class="space-y-8"
            aria-describedby="book-drag-hint"
            @pointerdown="onPointerDown"
            @touchstart="onTouchStart"
            @contextmenu="onContextMenu"
            @click.capture="onClickCapture"
            @dragstart.prevent
        >
            <Shelf
                v-for="shelf in localShelves"
                :key="shelf.id"
                :shelf="shelf"
                :drag-active="isDragging"
                :drop-target-id="dropTargetId"
                :dragging-book-id="draggedItem?.type === 'book' ? draggedItem.item.id : null"
                :insertion="insertion"
            />
        </div>

        <BookDragGhost
            v-if="draggedItem?.type === 'book'"
            :book="draggedItem.item"
            :mode="dragMode"
            :x="pointer.x"
            :y="pointer.y"
        />

        <ReadingLightbox
            v-if="selectedBook"
            :book="selectedBook"
            @close="selectedBook = null"
            @updated="applyReadingUpdate"
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
                        ? 'border-danger/40 bg-surface-raised text-danger'
                        : 'border-success/40 bg-surface-raised text-ink'
                "
            >
                {{ notice.text }}
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>
