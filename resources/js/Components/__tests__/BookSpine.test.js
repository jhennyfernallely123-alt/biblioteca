import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import BookSpine from '../BookSpine.vue';

describe('BookSpine', () => {
    it('incluye título y autor en el aria-label', () => {
        const wrapper = mount(BookSpine, {
            props: { book: { id: 1, title: 'Rayuela', author: 'Julio Cortázar' } },
        });

        expect(wrapper.attributes('aria-label')).toBe('Rayuela, Julio Cortázar');
    });
});
