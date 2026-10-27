import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ArcaneStars from '../ArcaneStars.vue';

describe('ArcaneStars', () => {
    it('es decorativo y contiene cuatro capas', () => {
        const wrapper = mount(ArcaneStars);

        expect(wrapper.attributes('aria-hidden')).toBe('true');
        expect(wrapper.findAll('.arcane-stars__layer')).toHaveLength(4);
    });
});
