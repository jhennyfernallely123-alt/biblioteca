export const BOOKS_PER_BAND = 5;
export const DECORATIONS_PER_BAND = 1;

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
