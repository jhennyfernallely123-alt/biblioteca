import { describe, expect, it } from 'vitest';
import { buildBands, insertBookInBand } from './bookcaseBands';

const book = (id, band = 0, bandPosition = 0) => ({
    id,
    band,
    band_position: bandPosition,
    shelf_id: 1,
});
const decoration = (id, after) => ({ id, after_book_id: after, sort: 0 });

describe('buildBands', () => {
    it('respeta la banda guardada y el orden interno', () => {
        const bands = buildBands([book(1, 0, 1), book(2, 0, 0), book(3, 1, 0)]);

        expect(bands.map((band) => band.id)).toEqual([0, 1]);
        expect(bands[0].books.map((item) => item.id)).toEqual([2, 1]);
        expect(bands[1].books.map((item) => item.id)).toEqual([3]);
    });

    it('no rellena una banda vacía con libros de la siguiente', () => {
        const bands = buildBands([book(1, 0, 0), book(2, 2, 0)]);

        expect(bands.map((band) => band.id)).toEqual([0, 2]);
        expect(bands[0].books.map((item) => item.id)).toEqual([1]);
        expect(bands[1].books.map((item) => item.id)).toEqual([2]);
    });

    it('coloca cada decoración en la banda de su libro ancla', () => {
        const bands = buildBands([book(1, 0), book(2, 1)], [decoration(9, 2)]);

        expect(bands[1].decorations.map((item) => item.id)).toEqual([9]);
    });

    it('reparte las decoraciones sobrantes en bandas siguientes', () => {
        const bands = buildBands([book(1, 0)], [decoration(9, 1), decoration(10, 1)]);

        expect(bands[0].decorations.map((item) => item.id)).toEqual([9]);
        expect(bands[1].decorations.map((item) => item.id)).toEqual([10]);
    });

    it('crea una banda vacía cuando no hay libros', () => {
        const bands = buildBands([], []);

        expect(bands).toHaveLength(1);
        expect(bands[0].books).toEqual([]);
    });
});

describe('insertBookInBand', () => {
    it('inserta en una banda con hueco y deja el resto igual', () => {
        const books = [book(1, 0, 0), book(2, 0, 1), book(3, 0, 2), book(4, 1, 0)];

        const next = insertBookInBand(books, books[3], 0, 1);

        expect(next.filter((item) => item.band === 0).map((item) => item.id)).toEqual([
            1,
            4,
            2,
            3,
        ]);
        expect(next.filter((item) => item.band === 1)).toHaveLength(0);
    });

    it('no sube libros de la banda siguiente al sacar uno', () => {
        const books = [
            book(1, 0, 0),
            book(2, 0, 1),
            book(3, 0, 2),
            book(4, 1, 0),
            book(5, 1, 1),
        ];

        const next = insertBookInBand(books, books[0], 1, 0);

        expect(next.filter((item) => item.band === 0).map((item) => item.id)).toEqual([2, 3]);
        expect(next.filter((item) => item.band === 1).map((item) => item.id)).toEqual([
            1,
            4,
            5,
        ]);
    });

    it('empuja el último libro a la banda siguiente al llenar una banda', () => {
        const books = [
            book(1, 0, 0),
            book(2, 0, 1),
            book(3, 0, 2),
            book(4, 0, 3),
            book(5, 0, 4),
            book(6, 1, 0),
        ];

        const next = insertBookInBand(books, books[5], 0, 0);

        expect(next.filter((item) => item.band === 0).map((item) => item.id)).toEqual([
            6,
            1,
            2,
            3,
            4,
        ]);
        expect(next.filter((item) => item.band === 1).map((item) => item.id)).toEqual([5]);
    });

    it('al soltar al final de una banda llena baja el último libro anterior', () => {
        const books = [
            book(1, 0, 0),
            book(2, 0, 1),
            book(3, 0, 2),
            book(4, 0, 3),
            book(5, 0, 4),
            book(6, 1, 0),
        ];

        const next = insertBookInBand(books, books[5], 0, 5);

        expect(next.filter((item) => item.band === 0).map((item) => item.id)).toEqual([
            1,
            2,
            3,
            4,
            6,
        ]);
        expect(next.filter((item) => item.band === 1).map((item) => item.id)).toEqual([5]);
    });

    it('encadena la cascada por varias bandas', () => {
        const books = [
            book(1, 0, 0),
            book(2, 0, 1),
            book(3, 0, 2),
            book(4, 0, 3),
            book(5, 0, 4),
            book(6, 1, 0),
            book(7, 1, 1),
            book(8, 1, 2),
            book(9, 1, 3),
            book(10, 1, 4),
            book(11, 2, 0),
        ];

        const next = insertBookInBand(books, books[10], 0, 0);

        expect(next.filter((item) => item.band === 0).map((item) => item.id)).toEqual([
            11,
            1,
            2,
            3,
            4,
        ]);
        expect(next.filter((item) => item.band === 1).map((item) => item.id)).toEqual([
            5,
            6,
            7,
            8,
            9,
        ]);
        expect(next.filter((item) => item.band === 2).map((item) => item.id)).toEqual([10]);
    });
});
