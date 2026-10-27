import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import BookSpine from '../BookSpine.vue';

describe('BookSpine', () => {
    it('muestra el título y lo incluye con el autor en el aria-label', () => {
        const wrapper = mount(BookSpine, {
            props: { book: { id: 1, title: 'Rayuela', author: 'Julio Cortázar' } },
        });

        expect(wrapper.text()).toContain('Rayuela');
        expect(wrapper.attributes('aria-label')).toBe('Rayuela, Julio Cortázar');
    });

    it('usa un color de respaldo cuando el libro no tiene portada', () => {
        const wrapper = mount(BookSpine, {
            props: { book: { id: 1, title: 'Rayuela', author: 'Julio Cortázar', cover_path: null } },
        });

        expect(wrapper.classes().some((name) => name.startsWith('bg-'))).toBe(true);
    });

    it('engrosa el lomo cuando el título es largo', () => {
        const widthOf = (title) => {
            const wrapper = mount(BookSpine, { props: { book: { id: 1, title } } });

            return Number(wrapper.attributes('style').match(/width: (\d+)px/)[1]);
        };

        expect(
            widthOf('Historia de la literatura universal desde la Antigüedad hasta nuestros días'),
        ).toBeGreaterThan(widthOf('Rayuela'));
    });

    it('expone el id del libro y se atenúa mientras se arrastra', () => {
        const wrapper = mount(BookSpine, {
            props: { book: { id: 9, title: 'Rayuela', author: 'Julio Cortázar' }, dragging: true },
        });

        expect(wrapper.attributes('data-book-id')).toBe('9');
        expect(wrapper.attributes('draggable')).toBe('false');
        expect(wrapper.classes()).toContain('opacity-30');
    });

    it('muestra el marcador de destino antes o después', () => {
        const before = mount(BookSpine, {
            props: {
                book: { id: 1, title: 'Rayuela' },
                dropPosition: 'before',
            },
        });
        const after = mount(BookSpine, {
            props: {
                book: { id: 1, title: 'Rayuela' },
                dropPosition: 'after',
            },
        });

        expect(before.classes()).toContain('book-drop-before');
        expect(after.classes()).toContain('book-drop-after');
    });
});
