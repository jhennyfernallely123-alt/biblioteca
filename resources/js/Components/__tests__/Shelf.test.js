import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Shelf from '../Shelf.vue';

function makeShelf(overrides = {}) {
    return {
        id: 7,
        name: 'Favoritos',
        view_mode: 'covers',
        books: [
            { id: 3, title: 'Rayuela', author: 'Julio Cortázar', cover_path: null, shelf_id: 7 },
        ],
        ...overrides,
    };
}

describe('Shelf', () => {
    it('marca la estantería como destino y muestra la indicación al arrastrar encima', () => {
        const wrapper = mount(Shelf, {
            props: {
                shelf: makeShelf(),
                dragActive: true,
                dropTargetId: 7,
                draggingBookId: 3,
            },
        });

        const section = wrapper.find('section');

        expect(section.attributes('data-shelf-id')).toBe('7');
        expect(section.classes()).toContain('shelf-drag-active');
        expect(section.classes()).toContain('shelf-drop-target');
        expect(wrapper.text()).toContain('Suelta aquí');
        expect(wrapper.find('[data-book-id="3"]').classes()).toContain('opacity-30');
    });

    it('no marca el destino cuando se arrastra sobre otra estantería', () => {
        const wrapper = mount(Shelf, {
            props: {
                shelf: makeShelf(),
                dragActive: true,
                dropTargetId: 12,
            },
        });

        expect(wrapper.find('section').classes()).not.toContain('shelf-drop-target');
        expect(wrapper.text()).not.toContain('Suelta aquí');
    });

    it('marca la línea de inserción antes o después del libro indicado', () => {
        const wrapper = mount(Shelf, {
            props: {
                shelf: makeShelf(),
                dragActive: true,
                insertion: { shelfId: 7, bookId: 3, position: 'before' },
                draggingBookId: 3,
            },
        });

        expect(wrapper.find('[data-book-id="3"]').classes()).toContain('book-drop-before');

        const after = mount(Shelf, {
            props: {
                shelf: makeShelf(),
                dragActive: true,
                insertion: { shelfId: 7, bookId: 3, position: 'after' },
                draggingBookId: 3,
            },
        });

        expect(after.find('[data-book-id="3"]').classes()).toContain('book-drop-after');
    });
});
