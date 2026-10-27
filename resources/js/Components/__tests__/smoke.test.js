import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import RatingStars from '../RatingStars.vue';

describe('entorno de pruebas', () => {
    it('monta un componente Vue', () => {
        const wrapper = mount(RatingStars, { props: { modelValue: 3 } });
        expect(wrapper.findAll('[data-star]')).toHaveLength(5);
    });
});
