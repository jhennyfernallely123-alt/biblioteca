import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import BookCard from '../BookCard.vue';

describe('BookCard', () => {
    it('muestra título y autor dentro de la portada cuando no hay imagen', () => {
        const wrapper = mount(BookCard, {
            props: { book: { id: 1, title: 'Rayuela', author: 'Julio Cortázar', cover_path: null } },
        });

        expect(wrapper.text()).toContain('Rayuela');
        expect(wrapper.text()).toContain('Julio Cortázar');
    });

    it('no muestra texto debajo de la portada y deja título y autor en el tooltip', () => {
        const wrapper = mount(BookCard, {
            props: { book: { id: 1, title: 'Rayuela', author: 'Julio Cortázar', cover_path: 'covers/1.jpg' } },
        });

        expect(wrapper.text()).toBe('');
        expect(wrapper.find('a').attributes('title')).toBe('Rayuela — Julio Cortázar');
    });

    it('expone el id del libro y se atenúa mientras se arrastra', () => {
        const wrapper = mount(BookCard, {
            props: { book: { id: 7, title: 'Rayuela', author: 'Julio Cortázar' }, dragging: true },
        });

        expect(wrapper.attributes('data-book-id')).toBe('7');
        expect(wrapper.attributes('draggable')).toBe('false');
        expect(wrapper.classes()).toContain('opacity-30');
    });

    it('muestra el marcador de destino antes o después', () => {
        const before = mount(BookCard, {
            props: {
                book: { id: 1, title: 'Rayuela' },
                dropPosition: 'before',
            },
        });
        const after = mount(BookCard, {
            props: {
                book: { id: 1, title: 'Rayuela' },
                dropPosition: 'after',
            },
        });

        expect(before.classes()).toContain('book-drop-before');
        expect(after.classes()).toContain('book-drop-after');
    });
});
