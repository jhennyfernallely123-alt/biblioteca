<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import BookDragGhost from '@/Components/BookDragGhost.vue';
import Shelf from '@/Components/Shelf.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useI18n } from '@/i18n';
import { moveBookWithin, sameBookOrder } from '@/libraryOrder';
import { useLibraryDrag } from '@/useLibraryDrag';

const props = defineProps({ shelves: { type: Array, required: true } });

const { t } = useI18n();

function cloneShelves(shelves) {
    return shelves.map((shelf) => ({
        ...shelf,
        books: shelf.books.map((book) => ({ ...book })),
    }));
}

const localShelves = ref(cloneShelves(props.shelves));
const notice = ref(null);
let noticeTimer = null;

watch(
    () => props.shelves,
    (shelves) => {
        localShelves.value = cloneShelves(shelves);
    },
    { deep: true }
);

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

function handleDrop(book, targetShelfId, target = null) {
    if (target) {
        reorderBook(book, targetShelfId, target);

        return;
    }

    moveBook(book, targetShelfId);
}

const {
    draggedBook,
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
    <Head :title="t('ui.library_title')" />
    <AuthenticatedLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-3xl md:text-4xl">{{ t('ui.library_title') }}</h1>
            <Link href="/books/create" class="rounded-control bg-arcane px-4 py-2 text-moon hover:bg-arcane-strong">
                {{ t('ui.add_book') }}
            </Link>
        </div>

        <p id="book-drag-hint" class="sr-only">{{ t('ui.drag_hint') }}</p>

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
                :dragging-book-id="draggedBook?.id ?? null"
                :insertion="insertion"
            />
        </div>

        <BookDragGhost
            v-if="draggedBook"
            :book="draggedBook"
            :mode="dragMode"
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
