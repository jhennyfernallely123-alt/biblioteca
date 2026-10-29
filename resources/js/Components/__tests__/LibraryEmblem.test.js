import { enableAutoUnmount, mount } from '@vue/test-utils';
import { h } from 'vue';
import { afterEach, describe, expect, it } from 'vitest';
import LibraryEmblem from '../LibraryEmblem.vue';

enableAutoUnmount(afterEach);

const EmblemPair = {
    render: () => h('div', [h(LibraryEmblem), h(LibraryEmblem, { compact: true })]),
};

describe('LibraryEmblem', () => {
    it('dibuja el título en mayúsculas y el libro abierto en un único lienzo', () => {
        const wrapper = mount(LibraryEmblem);
        const art = wrapper.get('.auth-emblem__art');

        expect(art.attributes('viewBox')).toBe('0 0 560 480');
        expect(art.get('.auth-emblem__word').text()).toBe('BIBLIOTECA');
        expect(art.findAll('.auth-emblem__page')).toHaveLength(2);
        expect(art.findAll('.auth-emblem__cover')).toHaveLength(2);
    });

    it('no dibuja letras ni sellos sobre las páginas', () => {
        const wrapper = mount(LibraryEmblem);

        expect(wrapper.find('.auth-emblem__text').exists()).toBe(false);
        expect(wrapper.find('.auth-emblem__seal').exists()).toBe(false);
        expect(wrapper.find('.auth-emblem__sparkles').exists()).toBe(false);
    });

    it('deja el título fuera de cualquier placa recortada', () => {
        const wrapper = mount(LibraryEmblem);
        const figure = wrapper.get('.auth-emblem').element;
        const art = wrapper.get('.auth-emblem__art');

        expect(art.element.parentElement).toBe(figure);
        expect(figure.querySelector('.auth-emblem__title, .auth-emblem__book')).toBeNull();
    });

    it('no repite ids entre dos emblemas visibles a la vez', () => {
        const wrapper = mount(EmblemPair);
        const ids = wrapper.findAll('[id]').map((node) => node.attributes('id'));

        expect(wrapper.findAll('.auth-emblem')).toHaveLength(2);
        expect(ids.length).toBeGreaterThan(0);
        expect(new Set(ids).size).toBe(ids.length);
    });

    it('resuelve cada url(#...) a un id definido en el mismo emblema', () => {
        const wrapper = mount(EmblemPair);
        const emblems = wrapper.findAll('.auth-emblem');

        emblems.forEach((emblem) => {
            const ids = new Set(emblem.findAll('[id]').map((node) => node.attributes('id')));
            const references = emblem.html().match(/url\(#[^)]+\)/g) ?? [];

            expect(references.length).toBeGreaterThan(0);
            references.forEach((reference) => {
                expect(ids.has(reference.slice(5, -1))).toBe(true);
            });
        });
    });

    it('marca la versión compacta sin cambiar el marcado del libro', () => {
        const wrapper = mount(LibraryEmblem, { props: { compact: true } });
        const compact = wrapper.get('.auth-emblem--compact');

        expect(compact.get('.auth-emblem__art').exists()).toBe(true);
        expect(compact.get('.auth-emblem__word').exists()).toBe(true);
    });
});
