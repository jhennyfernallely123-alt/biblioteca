import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/vue3';
import Shelf from '../Shelf.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal();

    return { ...original, router: { patch: vi.fn(), delete: vi.fn() } };
});

function makeShelf(overrides = {}) {
    return {
        id: 7,
        name: 'Favoritos',
        books: [
            { id: 3, title: 'Rayuela', author: 'Julio Cortázar', cover_path: null, shelf_id: 7, view_mode: 'covers' },
        ],
        decorations: [],
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

    it('mezcla portadas y lomos en la misma balda', () => {
        const wrapper = mount(Shelf, {
            props: {
                shelf: makeShelf({
                    books: [
                        { id: 3, title: 'Rayuela', author: 'Julio Cortázar', cover_path: null, shelf_id: 7, view_mode: 'covers' },
                        { id: 4, title: 'Ficciones', author: 'Jorge Luis Borges', cover_path: null, shelf_id: 7, view_mode: 'spines' },
                    ],
                }),
            },
        });

        expect(wrapper.find('[data-book-id="3"]').classes()).not.toContain('h-40');
        expect(wrapper.find('[data-book-id="4"]').classes()).toContain('h-40');
    });

    it('cambia el modo de vista del libro con el botón rápido', async () => {
        const wrapper = mount(Shelf, {
            props: { shelf: makeShelf() },
        });

        const button = wrapper.find('button[aria-label="Cambiar vista del libro"]');

        expect(button.exists()).toBe(true);

        await button.trigger('click');

        expect(router.patch).toHaveBeenCalledWith(
            '/books/3/view-mode',
            { view_mode: 'spines' },
            { preserveScroll: true, preserveState: true }
        );
    });

    it('intercala los objetos con los libros según su ancla', () => {
        const wrapper = mount(Shelf, {
            props: {
                shelf: makeShelf({
                    books: [
                        { id: 3, title: 'Rayuela', author: 'Julio Cortázar', cover_path: null, shelf_id: 7, view_mode: 'covers' },
                        { id: 4, title: 'Ficciones', author: 'Jorge Luis Borges', cover_path: null, shelf_id: 7, view_mode: 'spines' },
                    ],
                    decorations: [
                        { id: 9, kind: 'cup', color: 'rose', shelf_id: 7, after_book_id: 3, sort: 0 },
                        { id: 10, kind: 'roses', color: 'green', shelf_id: 7, after_book_id: null, sort: 0 },
                    ],
                }),
            },
        });

        const order = wrapper
            .findAll('[data-book-id], [data-decoration-id]')
            .map((element) => element.attributes('data-book-id') ?? `d${element.attributes('data-decoration-id')}`);

        expect(order).toEqual(['d10', '3', 'd9', '4']);
    });

    it('marca la línea de inserción en un objeto', () => {
        const wrapper = mount(Shelf, {
            props: {
                shelf: makeShelf({
                    decorations: [
                        { id: 9, kind: 'cup', color: 'rose', shelf_id: 7, after_book_id: 3, sort: 0 },
                    ],
                }),
                dragActive: true,
                insertion: { shelfId: 7, decorationId: 9, position: 'before' },
                draggingDecorationId: 9,
            },
        });

        expect(wrapper.find('[data-decoration-id="9"]').classes()).toContain('book-drop-before');
    });
});
