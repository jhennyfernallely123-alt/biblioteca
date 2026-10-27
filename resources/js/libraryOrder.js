export function moveBookWithin(books, bookId, targetId, place = 'before') {
    const from = books.findIndex((book) => book.id === bookId);
    const target = books.findIndex((book) => book.id === targetId);

    if (from === -1 || target === -1) {
        return books;
    }

    const next = books.slice();
    const [moved] = next.splice(from, 1);
    let insertAt = target + (place === 'after' ? 1 : 0);

    if (from < insertAt) {
        insertAt--;
    }

    insertAt = Math.max(0, Math.min(next.length, insertAt));
    next.splice(insertAt, 0, moved);

    return next;
}

export function sameBookOrder(a, b) {
    return a.length === b.length && a.every((book, index) => book.id === b[index].id);
}
