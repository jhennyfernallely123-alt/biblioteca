import { describe, expect, it } from 'vitest';
import { buildBands, insertBookInBand, packBands, visualDropTarget } from './bookcaseBands';

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

describe('packBands', () => {
    it('llena una sola banda cuando los libros caben en el ancho', () => {
        const books = [book(1, 0, 0), book(2, 0, 1), book(3, 0, 2)];

        const bands = packBands(books, [], 1000);

        expect(bands).toHaveLength(1);
        expect(bands[0].books.map((item) => item.id)).toEqual([1, 2, 3]);
    });

    it('reparte en varias bandas cuando se supera el ancho disponible', () => {
        const books = [
            book(1, 0, 0),
            book(2, 0, 1),
            book(3, 0, 2),
            book(4, 0, 3),
            book(5, 0, 4),
        ];

        const bands = packBands(books, [], 500);

        expect(bands.map((band) => band.books.map((item) => item.id))).toEqual([
            [1, 2, 3],
            [4, 5],
        ]);
    });

    it('respeta el orden guardado al empaquetar', () => {
        const books = [book(1, 1, 1), book(2, 0, 1), book(3, 0, 0), book(4, 1, 0)];

        const bands = packBands(books, [], 1000);

        expect(bands[0].books.map((item) => item.id)).toEqual([3, 2, 4, 1]);
    });

    it('coloca la decoración en la banda visual de su libro ancla', () => {
        const books = [book(1, 0, 0), book(2, 0, 1), book(3, 1, 0)];

        const bands = packBands(books, [decoration(9, 2)], 300);

        expect(bands[0].decorations).toHaveLength(0);
        expect(bands[1].decorations.map((item) => item.id)).toEqual([9]);
    });

    it('reparte las decoraciones sobrantes en bandas siguientes', () => {
        const books = [book(1, 0, 0), book(2, 0, 1)];

        const bands = packBands(books, [decoration(9, 1), decoration(10, 1)], 1000);

        expect(bands[0].decorations.map((item) => item.id)).toEqual([9]);
        expect(bands[1].decorations.map((item) => item.id)).toEqual([10]);
    });
});

describe('visualDropTarget', () => {
    const ordered = [
        book(1, 0, 0),
        book(2, 0, 1),
        book(3, 0, 2),
    ];

    it('inserta antes y después de un libro intermedio', () => {
        const band = { id: 0, books: ordered };

        expect(visualDropTarget(band, { bookId: 2, position: 'before' }, ordered)).toEqual({
            band: 0,
            position: 1,
        });
        expect(visualDropTarget(band, { bookId: 2, position: 'after' }, ordered)).toEqual({
            band: 0,
            position: 2,
        });
    });

    it('inserta al final de una banda con hueco', () => {
        const band = { id: 0, books: ordered };

        expect(visualDropTarget(band, { bookId: 3, position: 'after' }, ordered)).toEqual({
            band: 0,
            position: 3,
        });
    });

    it('pasa a la banda siguiente al soltar después del último libro de una banda llena', () => {
        const full = [
            book(1, 0, 0),
            book(2, 0, 1),
            book(3, 0, 2),
            book(4, 0, 3),
            book(5, 0, 4),
        ];

        expect(visualDropTarget({ id: 0, books: full }, { bookId: 5, position: 'after' }, full)).toEqual({
            band: 1,
            position: 0,
        });
    });

    it('ignora el libro arrastrado al calcular el ancla', () => {
        const band = { id: 0, books: ordered };

        expect(visualDropTarget(band, { bookId: 3, position: 'after' }, ordered, 3)).toEqual({
            band: 0,
            position: 2,
        });
    });

    it('usa el primer o último libro cuando la banda visual no tiene libros', () => {
        const band = { id: 1, books: [], decorations: [decoration(9, 1)] };

        expect(visualDropTarget(band, { position: 'before' }, ordered)).toEqual({
            band: 0,
            position: 0,
        });
        expect(visualDropTarget(band, { position: 'after' }, ordered)).toEqual({
            band: 0,
            position: 3,
        });
    });
});
