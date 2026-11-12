import { describe, expect, it } from 'vitest';
import { applyLookupResult } from './bookLookup';

function emptyForm() {
    return {
        title: '',
        author: '',
        isbn: '',
        publisher: '',
        published_year: null,
        catalog_book_id: null,
        cover_url: 'https://old.test/cover.jpg',
        cover: 'old-file',
    };
}

describe('applyLookupResult', () => {
    it('selecciona un resultado del catálogo y limpia la url externa', () => {
        const form = emptyForm();

        const preview = applyLookupResult(form, {
            title: 'Libro local',
            author: 'Autora',
            isbn: '9788437604947',
            publisher: 'Editorial',
            published_year: 2020,
            cover_url: '/catalog-covers/7',
            catalog_book_id: 7,
        });

        expect(form.catalog_book_id).toBe(7);
        expect(form.cover_url).toBeNull();
        expect(form.cover).toBeNull();
        expect(preview).toBe('/catalog-covers/7');
        expect(form.title).toBe('Libro local');
    });

    it('selecciona un resultado de la api y conserva su portada', () => {
        const form = emptyForm();

        const preview = applyLookupResult(form, {
            title: 'Libro API',
            author: 'Autor',
            cover_url: 'https://covers.test/api.jpg',
        });

        expect(form.catalog_book_id).toBeNull();
        expect(form.cover_url).toBe('https://covers.test/api.jpg');
        expect(form.cover).toBeNull();
        expect(preview).toBe('https://covers.test/api.jpg');
    });
});
