import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import BookcaseBand from '../BookcaseBand.vue';

function makeBand(book) {
    return { id: 0, books: [book], decorations: [] };
}

function makeBook(overrides = {}) {
    return {
        id: 7,
        title: 'Rayuela',
        author: 'Julio Cortázar',
        cover_path: null,
        shelf_id: 1,
        view_mode: 'spines',
        ...overrides,
    };
}

describe('BookcaseBand', () => {
    it('cuelga una guirnalda en la banda', () => {
        const wrapper = mount(BookcaseBand, { props: { band: makeBand(makeBook()) } });

        expect(wrapper.find('[data-season-garland]').exists()).toBe(true);
    });

    it('dibuja el lomo cuando el libro está en modo lomo', () => {
        const wrapper = mount(BookcaseBand, { props: { band: makeBand(makeBook()) } });

        const book = wrapper.find('[data-book-id="7"]');

        expect(book.classes()).toContain('h-40');
    });

    it('dibuja la portada cuando el libro está en modo portada', () => {
        const wrapper = mount(BookcaseBand, {
            props: { band: makeBand(makeBook({ view_mode: 'covers' })) },
        });

        const book = wrapper.find('[data-book-id="7"]');

        expect(book.classes()).not.toContain('h-40');
        expect(book.find('[class*="aspect-"]').exists()).toBe(true);
    });

    it('atenúa el libro que se está arrastrando', () => {
        const wrapper = mount(BookcaseBand, {
            props: { band: makeBand(makeBook()), draggingBookId: 7 },
        });

        expect(wrapper.find('[data-book-id="7"]').classes()).toContain('opacity-30');
    });

    it('mezcla lomos y portadas en la misma banda', () => {
        const wrapper = mount(BookcaseBand, {
            props: {
                band: {
                    id: 0,
                    books: [makeBook({ id: 7 }), makeBook({ id: 8, view_mode: 'covers' })],
                    decorations: [],
                },
            },
        });

        expect(wrapper.find('[data-book-id="7"]').classes()).toContain('h-40');
        expect(wrapper.find('[data-book-id="8"]').classes()).not.toContain('h-40');
    });
});
