import { onBeforeUnmount, ref } from 'vue';

const LONG_PRESS_MS = 320;
const TOUCH_TOLERANCE = 8;
const MOUSE_TOLERANCE = 6;
const EDGE_SCROLL = 90;
const MAX_SCROLL = 18;
const CLICK_SUPPRESSION_MS = 450;

export function useLibraryDrag(getShelves, onDrop) {
    const draggedBook = ref(null);
    const dragMode = ref('covers');
    const pointer = ref({ x: 0, y: 0 });
    const isDragging = ref(false);
    const dropTargetId = ref(null);
    const insertion = ref(null);
    const suppressClick = ref(false);

    let candidate = null;
    let sourceShelf = null;
    let lastPoint = { x: 0, y: 0 };
    let scrollFrame = null;
    let suppressTimer = null;

    function locateBook(id) {
        for (const shelf of getShelves()) {
            const book = shelf.books.find((item) => item.id === id);

            if (book) {
                return { book, shelf };
            }
        }

        return null;
    }

    function shelfIdAt(x, y) {
        const shelf = document.elementFromPoint(x, y)?.closest('[data-shelf-id]');

        return shelf ? Number(shelf.dataset.shelfId) : null;
    }

    function onPointerDown(event) {
        if (event.pointerType === 'touch' || event.button !== 0 || candidate || isDragging.value) {
            return;
        }

        const element = event.target.closest?.('[data-book-id]');
        if (!element) {
            return;
        }

        const found = locateBook(Number(element.dataset.bookId));
        if (!found) {
            return;
        }

        candidate = {
            book: found.book,
            shelf: found.shelf,
            startX: event.clientX,
            startY: event.clientY,
            pointerId: event.pointerId,
            touch: false,
            activated: false,
            timer: null,
        };
        lastPoint = { x: event.clientX, y: event.clientY };
        element.setPointerCapture?.(event.pointerId);

        window.addEventListener('pointermove', onPointerMove);
        window.addEventListener('pointerup', onPointerUp);
        window.addEventListener('pointercancel', onPointerCancel);
    }

    function onPointerMove(event) {
        if (!candidate || candidate.touch || candidate.pointerId !== event.pointerId) {
            return;
        }

        if (!candidate.activated) {
            const distance = Math.hypot(event.clientX - candidate.startX, event.clientY - candidate.startY);

            if (distance < MOUSE_TOLERANCE) {
                return;
            }

            activate(event.clientX, event.clientY);
        }

        updatePointer(event.clientX, event.clientY);
    }

    function onPointerUp(event) {
        if (!candidate || candidate.touch || candidate.pointerId !== event.pointerId) {
            return;
        }

        if (candidate.activated) {
            finishDrag(event.clientX, event.clientY);
        } else {
            cleanup();
        }
    }

    function onPointerCancel() {
        if (candidate && !candidate.touch) {
            cleanup();
        }
    }

    function onTouchStart(event) {
        if (event.touches.length !== 1 || candidate || isDragging.value) {
            return;
        }

        const element = event.target.closest?.('[data-book-id]');
        if (!element) {
            return;
        }

        const found = locateBook(Number(element.dataset.bookId));
        if (!found) {
            return;
        }

        const touch = event.touches[0];
        lastPoint = { x: touch.clientX, y: touch.clientY };
        candidate = {
            book: found.book,
            shelf: found.shelf,
            startX: touch.clientX,
            startY: touch.clientY,
            pointerId: null,
            touch: true,
            activated: false,
            timer: window.setTimeout(() => activate(lastPoint.x, lastPoint.y), LONG_PRESS_MS),
        };

        window.addEventListener('touchmove', onTouchMove, { passive: false });
        window.addEventListener('touchend', onTouchEnd);
        window.addEventListener('touchcancel', onTouchCancel);
    }

    function onTouchMove(event) {
        if (!candidate || !candidate.touch) {
            return;
        }

        const touch = event.touches[0];

        if (!candidate.activated) {
            const distance = Math.hypot(touch.clientX - candidate.startX, touch.clientY - candidate.startY);

            if (distance > TOUCH_TOLERANCE) {
                cleanup();
            }

            return;
        }

        event.preventDefault();
        updatePointer(touch.clientX, touch.clientY);
    }

    function onTouchEnd(event) {
        if (!candidate || !candidate.touch) {
            return;
        }

        const touch = event.changedTouches[0];

        if (candidate.activated) {
            finishDrag(touch.clientX, touch.clientY);
        } else {
            cleanup();
        }
    }

    function onTouchCancel() {
        if (candidate && candidate.touch) {
            cleanup();
        }
    }

    function onContextMenu(event) {
        if (candidate || isDragging.value) {
            event.preventDefault();
        }
    }

    function activate(x, y) {
        if (!candidate || candidate.activated) {
            return;
        }

        candidate.activated = true;
        sourceShelf = candidate.shelf;
        draggedBook.value = candidate.book;
        dragMode.value = candidate.shelf.view_mode === 'spines' ? 'spines' : 'covers';
        isDragging.value = true;
        suppressClick.value = true;
        window.clearTimeout(suppressTimer);
        document.body.classList.add('book-dragging');
        window.addEventListener('keydown', onKeyDown);

        updatePointer(x, y);
        scrollFrame = window.requestAnimationFrame(autoScroll);

        if (candidate.touch) {
            navigator.vibrate?.(10);
        }
    }

    function updatePointer(x, y) {
        lastPoint = { x, y };
        pointer.value = { x, y };

        const target = shelfIdAt(x, y);

        if (!target || !sourceShelf) {
            dropTargetId.value = null;
            insertion.value = null;

            return;
        }

        if (target !== sourceShelf.id) {
            dropTargetId.value = target;
            insertion.value = null;

            return;
        }

        dropTargetId.value = null;

        const element = document.elementFromPoint(x, y)?.closest?.('[data-book-id]');

        if (!element) {
            const last = sourceShelf.books[sourceShelf.books.length - 1];

            insertion.value = last
                ? { shelfId: target, bookId: last.id, position: 'after' }
                : null;

            return;
        }

        const targetBookId = Number(element.dataset.bookId);

        if (targetBookId === draggedBook.value?.id) {
            insertion.value = null;

            return;
        }

        const rect = element.getBoundingClientRect();

        insertion.value = {
            shelfId: target,
            bookId: targetBookId,
            position: x >= rect.left + rect.width / 2 ? 'after' : 'before',
        };
    }

    function autoScroll() {
        if (!isDragging.value) {
            return;
        }

        const height = window.innerHeight;
        let delta = 0;

        if (lastPoint.y < EDGE_SCROLL) {
            delta = -Math.min(MAX_SCROLL, Math.ceil((EDGE_SCROLL - lastPoint.y) / 4));
        } else if (lastPoint.y > height - EDGE_SCROLL) {
            delta = Math.min(MAX_SCROLL, Math.ceil((lastPoint.y - (height - EDGE_SCROLL)) / 4));
        }

        if (delta !== 0) {
            window.scrollBy(0, delta);
            updatePointer(lastPoint.x, lastPoint.y);
        }

        scrollFrame = window.requestAnimationFrame(autoScroll);
    }

    function finishDrag(x, y) {
        const targetId = shelfIdAt(x, y);
        const book = draggedBook.value;
        const currentInsertion = insertion.value;
        const shouldMove = Boolean(targetId && sourceShelf && targetId !== sourceShelf.id);
        const shouldReorder = Boolean(
            targetId && sourceShelf && targetId === sourceShelf.id && currentInsertion
        );

        cleanup();

        if (!book) {
            return;
        }

        if (shouldMove) {
            onDrop(book, targetId);
        } else if (shouldReorder) {
            onDrop(book, targetId, currentInsertion);
        }
    }

    function onKeyDown(event) {
        if (event.key === 'Escape' && candidate?.activated) {
            cleanup();
        }
    }

    function cleanup() {
        if (candidate?.timer) {
            window.clearTimeout(candidate.timer);
        }

        if (candidate?.activated) {
            suppressClick.value = true;
            window.clearTimeout(suppressTimer);
            suppressTimer = window.setTimeout(() => {
                suppressClick.value = false;
            }, CLICK_SUPPRESSION_MS);
        }

        candidate = null;
        sourceShelf = null;
        draggedBook.value = null;
        isDragging.value = false;
        dropTargetId.value = null;
        insertion.value = null;
        document.body.classList.remove('book-dragging');

        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', onPointerUp);
        window.removeEventListener('pointercancel', onPointerCancel);
        window.removeEventListener('touchmove', onTouchMove);
        window.removeEventListener('touchend', onTouchEnd);
        window.removeEventListener('touchcancel', onTouchCancel);
        window.removeEventListener('keydown', onKeyDown);

        if (scrollFrame) {
            window.cancelAnimationFrame(scrollFrame);
        }

        scrollFrame = null;
    }

    function onClickCapture(event) {
        if (!suppressClick.value) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        suppressClick.value = false;
    }

    onBeforeUnmount(() => {
        cleanup();
        window.clearTimeout(suppressTimer);
    });

    return {
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
    };
}
