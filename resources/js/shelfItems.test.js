import { describe, expect, it } from 'vitest';
import { mergeShelfItems, resolveDecorationAnchor } from './shelfItems';

const books = [
    { id: 1, title: 'Alfa' },
    { id: 2, title: 'Beta' },
];

function ids(items) {
    return items.map((item) => (item.type === 'book' ? item.book.id : item.decoration.id));
}

describe('mergeShelfItems', () => {
    it('coloca los objetos sin ancla antes del primer libro', () => {
        const items = mergeShelfItems(books, [
            { id: 10, after_book_id: null, sort: 0 },
        ]);

        expect(ids(items)).toEqual([10, 1, 2]);
    });

    it('coloca los objetos anclados después de su libro', () => {
        const items = mergeShelfItems(books, [
            { id: 10, after_book_id: 1, sort: 0 },
            { id: 11, after_book_id: 1, sort: 1 },
            { id: 12, after_book_id: 2, sort: 0 },
        ]);

        expect(ids(items)).toEqual([1, 10, 11, 2, 12]);
    });

    it('manda al final los objetos cuyo ancla no está en la estantería', () => {
        const items = mergeShelfItems(books, [
            { id: 10, after_book_id: 99, sort: 0 },
            { id: 11, after_book_id: null, sort: 0 },
        ]);

        expect(ids(items)).toEqual([11, 1, 2, 10]);
    });

    it('respeta sort y desempata por id', () => {
        const items = mergeShelfItems(books, [
            { id: 12, after_book_id: null, sort: 1 },
            { id: 11, after_book_id: null, sort: 1 },
            { id: 10, after_book_id: null, sort: 0 },
        ]);

        expect(ids(items)).toEqual([10, 11, 12, 1, 2]);
    });
});

describe('resolveDecorationAnchor', () => {
    const decorations = [
        { id: 10, after_book_id: 1, sort: 0 },
        { id: 11, after_book_id: 1, sort: 1 },
    ];

    it('ancla al final cuando no hay elemento bajo el puntero', () => {
        expect(resolveDecorationAnchor(books, decorations, null, 'after')).toEqual({
            afterBookId: 2,
            sort: 0,
        });
    });

    it('ancla antes del libro cuando se suelta en su mitad izquierda', () => {
        expect(resolveDecorationAnchor(books, decorations, { type: 'book', id: 2 }, 'before')).toEqual({
            afterBookId: 1,
            sort: 2,
        });
    });

    it('ancla antes del primer libro cuando se suelta sobre el primero por la izquierda', () => {
        expect(resolveDecorationAnchor(books, decorations, { type: 'book', id: 1 }, 'before')).toEqual({
            afterBookId: null,
            sort: 0,
        });
    });

    it('coloca después del objeto señalado', () => {
        expect(resolveDecorationAnchor(books, decorations, { type: 'decoration', id: 10 }, 'after')).toEqual({
            afterBookId: 1,
            sort: 1,
        });
    });

    it('excluye el objeto que se está moviendo', () => {
        expect(resolveDecorationAnchor(books, decorations, { type: 'book', id: 1 }, 'after', 10)).toEqual({
            afterBookId: 1,
            sort: 1,
        });
    });
});
