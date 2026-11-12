export const COVER_WIDTH = 110;
export const MOBILE_COVER_WIDTH = 100;
export const DECORATION_WIDTH = 64;
export const ITEM_GAP = 12;

export function titleHash(title = '') {
    let value = 0;

    for (const char of title) {
        value = (value * 31 + char.charCodeAt(0)) % 997;
    }

    return value;
}

export function spineMetrics(title = '') {
    const length = title.length;
    const base = 18 + (titleHash(title) % 15);

    for (const fontSize of [10, 9, 8]) {
        const perColumn = Math.max(1, Math.floor(144 / (fontSize * 0.78)));
        const columns = Math.max(1, Math.ceil(length / perColumn));
        const width = 8 + Math.ceil(columns * (fontSize * 1.15 + 0.4));

        if (width <= 88) {
            return { fontSize, width: Math.max(base, width) };
        }
    }

    return { fontSize: 8, width: 88 };
}

export function bookWidth(book, { desktop = true } = {}) {
    if (book?.view_mode === 'spines') {
        return spineMetrics(book.title ?? '').width;
    }

    return desktop ? COVER_WIDTH : MOBILE_COVER_WIDTH;
}
