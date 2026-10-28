import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import RatingStars from '../RatingStars.vue';

describe('RatingStars', () => {
    it('emite la nueva valoración al hacer clic', async () => {
        const wrapper = mount(RatingStars, { props: { modelValue: 0, editable: true } });
        await wrapper.findAll('[data-star]')[3].trigger('click');
        expect(wrapper.emitted('update:modelValue')[0]).toEqual([4]);
    });
});
