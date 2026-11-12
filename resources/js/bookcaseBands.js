import { DECORATION_WIDTH, ITEM_GAP, bookWidth } from './bookDimensions';

export const BOOKS_PER_BAND = 5;
export const DECORATIONS_PER_BAND = 1;

function orderBooks(books = []) {
    return [...books].sort(
        (a, b) =>
            (a.band ?? 0) - (b.band ?? 0) ||
            (a.band_position ?? 0) - (b.band_position ?? 0) ||
            a.id - b.id
    );
}

export function buildBands(books = [], decorations = []) {
    const groups = new Map();

    for (const book of books) {
        const band = book.band ?? 0;

        if (!groups.has(band)) {
            groups.set(band, { id: band, books: [], decorations: [] });
        }

        groups.get(band).books.push(book);
    }

    if (groups.size === 0) {
        groups.set(0, { id: 0, books: [], decorations: [] });
    }

    const bands = [...groups.values()].sort((a, b) => a.id - b.id);

    bands.forEach((band) => {
        band.books.sort(
            (a, b) => (a.band_position ?? 0) - (b.band_position ?? 0) || a.id - b.id
        );
    });

    const bandOfBook = new Map();

    bands.forEach((band) => {
        band.books.forEach((book) => bandOfBook.set(book.id, band));
    });

    const ordered = [...decorations].sort((a, b) => {
        const bandA = a.after_book_id ? bandOfBook.get(a.after_book_id)?.id ?? Infinity : 0;
        const bandB = b.after_book_id ? bandOfBook.get(b.after_book_id)?.id ?? Infinity : 0;

        return bandA - bandB || (a.sort ?? 0) - (b.sort ?? 0) || a.id - b.id;
    });

    for (const decoration of ordered) {
        const anchorBand = decoration.after_book_id
            ? bandOfBook.get(decoration.after_book_id)
            : bands[0];
        const start = anchorBand
            ? Math.max(0, bands.findIndex((band) => band.id === anchorBand.id))
            : bands.length - 1;
        let target = null;

        for (let index = start; index < bands.length; index += 1) {
            if (bands[index].decorations.length < DECORATIONS_PER_BAND) {
                target = bands[index];
                break;
            }
        }

        if (!target) {
            target = {
                id: (bands[bands.length - 1]?.id ?? -1) + 1,
                books: [],
                decorations: [],
            };
            bands.push(target);
        }

        target.decorations.push(decoration);
    }

    return bands;
}

export function insertBookInBand(books, book, band, index) {
    const next = books.map((item) => ({ ...item }));
    const moved = next.find((item) => item.id === book.id);

    if (!moved) {
        return books;
    }

    moved.band = band;

    const groups = new Map();

    for (const item of next) {
        const itemBand = item.band ?? 0;

        if (!groups.has(itemBand)) {
            groups.set(itemBand, []);
        }

        groups.get(itemBand).push(item);
    }

    for (const group of groups.values()) {
        group.sort(
            (a, b) => (a.band_position ?? 0) - (b.band_position ?? 0) || a.id - b.id
        );
    }

    for (const [itemBand, group] of groups) {
        const position = group.findIndex((item) => item.id === moved.id);

        if (position !== -1) {
            group.splice(position, 1);
        }

        if (group.length === 0) {
            groups.delete(itemBand);
        }
    }

    const target = groups.get(band) ?? [];
    const position = Math.max(
        0,
        Math.min(index, target.length, BOOKS_PER_BAND - 1)
    );

    target.splice(position, 0, moved);
    groups.set(band, target);

    let maxBand = Math.max(band, ...groups.keys());

    for (let current = band; current <= maxBand; current += 1) {
        const group = groups.get(current);

        if (!group) {
            continue;
        }

        while (group.length > BOOKS_PER_BAND) {
            const overflow = group.pop();
            const nextBand = current + 1;

            if (!groups.has(nextBand)) {
                groups.set(nextBand, []);
            }

            groups.get(nextBand).unshift(overflow);
            overflow.band = nextBand;
            maxBand = Math.max(maxBand, nextBand);
        }
    }

    const result = [];

    [...groups.entries()]
        .sort((a, b) => a[0] - b[0])
        .forEach(([groupBand, group]) => {
            group.forEach((item, itemPosition) => {
                item.band = groupBand;
                item.band_position = itemPosition;
                result.push(item);
            });
        });

    return result;
}

export function packBands(books = [], decorations = [], availableWidth = 0) {
    const ordered = orderBooks(books);
    const usableWidth = Math.max(0, availableWidth - (DECORATION_WIDTH + ITEM_GAP));
    const groups = [];
    let current = [];
    let used = 0;

    for (const book of ordered) {
        const width = bookWidth(book, { desktop: true });
        const needed = current.length === 0 ? width : used + ITEM_GAP + width;

        if (current.length > 0 && needed > usableWidth) {
            groups.push(current);
            current = [];
            used = 0;
        }

        used = current.length === 0 ? width : used + ITEM_GAP + width;
        current.push(book);
    }

    if (current.length > 0) {
        groups.push(current);
    }

    if (groups.length === 0) {
        groups.push([]);
    }

    const bands = groups.map((group, index) => ({
        id: index,
        books: group,
        decorations: [],
    }));

    const bandOfBook = new Map();

    bands.forEach((band) => {
        band.books.forEach((book) => bandOfBook.set(book.id, band));
    });

    const orderedDecorations = [...decorations].sort((a, b) => {
        const bandA = a.after_book_id ? bandOfBook.get(a.after_book_id)?.id ?? Infinity : 0;
        const bandB = b.after_book_id ? bandOfBook.get(b.after_book_id)?.id ?? Infinity : 0;

        return bandA - bandB || (a.sort ?? 0) - (b.sort ?? 0) || a.id - b.id;
    });

    for (const decoration of orderedDecorations) {
        const anchorBand = decoration.after_book_id
            ? bandOfBook.get(decoration.after_book_id)
            : bands[0];
        const start = anchorBand
            ? Math.max(0, bands.findIndex((band) => band.id === anchorBand.id))
            : 0;
        let target = null;

        for (let index = start; index < bands.length; index += 1) {
            if (bands[index].decorations.length < DECORATIONS_PER_BAND) {
                target = bands[index];
                break;
            }
        }

        if (!target) {
            target = {
                id: (bands[bands.length - 1]?.id ?? -1) + 1,
                books: [],
                decorations: [],
            };
            bands.push(target);
        }

        target.decorations.push(decoration);
    }

    return bands;
}

function targetAfter(anchor, allBooks) {
    const band = anchor.band ?? 0;
    const position = anchor.band_position ?? 0;
    const size = allBooks.filter((book) => (book.band ?? 0) === band).length;

    if (position + 1 < size) {
        return { band, position: position + 1 };
    }

    if (size < BOOKS_PER_BAND) {
        return { band, position: size };
    }

    return { band: band + 1, position: 0 };
}

export function visualDropTarget(band, insertion, allBooks = [], draggedBookId = null) {
    const books = (band?.books ?? []).filter((book) => book.id !== draggedBookId);
    let anchor = null;

    if (insertion?.bookId != null) {
        anchor = books.find((book) => book.id === insertion.bookId) ?? null;
    }

    if (!anchor && books.length > 0) {
        anchor = insertion?.position === 'before' ? books[0] : books[books.length - 1];
    }

    if (!anchor) {
        const remaining = orderBooks(allBooks).filter((book) => book.id !== draggedBookId);

        if (remaining.length === 0) {
            return { band: 0, position: 0 };
        }

        anchor = insertion?.position === 'before'
            ? remaining[0]
            : remaining[remaining.length - 1];
    }

    if (insertion?.position === 'before') {
        return { band: anchor.band ?? 0, position: anchor.band_position ?? 0 };
    }

    return targetAfter(anchor, allBooks);
}
