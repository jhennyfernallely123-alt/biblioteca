import { describe, expect, it } from 'vitest';
import { moveBookWithin, sameBookOrder } from './libraryOrder';

const books = [
    { id: 1 },
    { id: 2 },
    { id: 3 },
    { id: 4 },
    { id: 5 },
];

describe('moveBookWithin', () => {
    it('mueve el primero al cuarto lugar', () => {
        const result = moveBookWithin(books, 1, 4, 'after');

        expect(result.map((book) => book.id)).toEqual([2, 3, 4, 1, 5]);
    });

    it('mueve un libro al final', () => {
        const result = moveBookWithin(books, 1, 5, 'after');

        expect(result.map((book) => book.id)).toEqual([2, 3, 4, 5, 1]);
    });

    it('mueve un libro antes de otro', () => {
        const result = moveBookWithin(books, 5, 2, 'before');

        expect(result.map((book) => book.id)).toEqual([1, 5, 2, 3, 4]);
    });

    it('no cambia el orden al soltar en la misma posición', () => {
        const result = moveBookWithin(books, 3, 3, 'before');

        expect(result.map((book) => book.id)).toEqual([1, 2, 3, 4, 5]);
        expect(sameBookOrder(books, result)).toBe(true);
    });

    it('no pierde ni duplica libros', () => {
        const result = moveBookWithin(books, 2, 4, 'after');

        expect(result).toHaveLength(books.length);
        expect(new Set(result.map((book) => book.id)).size).toBe(books.length);
    });
});
