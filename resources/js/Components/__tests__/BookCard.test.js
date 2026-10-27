import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import BookCard from '../BookCard.vue';

describe('BookCard', () => {
    it('muestra título y autor cuando no hay portada', () => {
        const wrapper = mount(BookCard, {
            props: { book: { id: 1, title: 'Rayuela', author: 'Julio Cortázar', cover_path: null } },
        });

        expect(wrapper.text()).toContain('Rayuela');
        expect(wrapper.text()).toContain('Julio Cortázar');
    });
});
