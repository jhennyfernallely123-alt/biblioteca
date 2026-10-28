import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import EmptyShelf from '../EmptyShelf.vue';

describe('EmptyShelf', () => {
    it('anuncia que la estantería está vacía', () => {
        const wrapper = mount(EmptyShelf);

        expect(wrapper.text()).toContain('Aún no hay libros en esta estantería.');
    });
});
