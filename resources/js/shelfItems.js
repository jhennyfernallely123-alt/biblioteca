function sortGroup(group) {
    return group.sort((a, b) => a.sort - b.sort || a.id - b.id);
}

export function mergeShelfItems(books = [], decorations = []) {
    const anchors = new Map();

    for (const decoration of decorations) {
        const key = decoration.after_book_id ?? 0;

        if (!anchors.has(key)) {
            anchors.set(key, []);
        }

        anchors.get(key).push(decoration);
    }

    for (const group of anchors.values()) {
        sortGroup(group);
    }

    const bookIds = new Set(books.map((book) => book.id));
    const items = [];

    for (const decoration of anchors.get(0) ?? []) {
        items.push({ type: 'decoration', key: `d${decoration.id}`, decoration });
    }

    for (const book of books) {
        items.push({ type: 'book', key: `b${book.id}`, book });

        for (const decoration of anchors.get(book.id) ?? []) {
            items.push({ type: 'decoration', key: `d${decoration.id}`, decoration });
        }
    }

    const orphans = [];

    for (const [anchorId, group] of anchors) {
        if (anchorId === 0 || bookIds.has(anchorId)) {
            continue;
        }

        orphans.push(...group);
    }

    for (const decoration of sortGroup(orphans)) {
        items.push({ type: 'decoration', key: `d${decoration.id}`, decoration });
    }

    return items;
}

export function resolveDecorationAnchor(books = [], decorations = [], hit = null, place = 'after', movingId = null) {
    const pool = decorations.filter((decoration) => decoration.id !== movingId);

    const countAt = (afterBookId) =>
        pool.filter((decoration) => (decoration.after_book_id ?? null) === afterBookId).length;

    const atEnd = () => {
        const last = books[books.length - 1] ?? null;
        const afterBookId = last?.id ?? null;

        return { afterBookId, sort: countAt(afterBookId) };
    };

    if (!hit) {
        return atEnd();
    }

    if (hit.type === 'book') {
        const index = books.findIndex((book) => book.id === hit.id);

        if (index === -1) {
            return atEnd();
        }

        const anchor = place === 'after' ? books[index] : books[index - 1] ?? null;
        const afterBookId = anchor?.id ?? null;

        return { afterBookId, sort: countAt(afterBookId) };
    }

    const target = pool.find((decoration) => decoration.id === hit.id);

    if (!target) {
        return atEnd();
    }

    const afterBookId = target.after_book_id ?? null;
    const group = sortGroup(pool.filter((decoration) => (decoration.after_book_id ?? null) === afterBookId));
    const index = group.findIndex((decoration) => decoration.id === target.id);

    return {
        afterBookId,
        sort: Math.max(0, index + (place === 'after' ? 1 : 0)),
    };
}
