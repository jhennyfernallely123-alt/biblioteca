import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import RatingStars from '../RatingStars.vue';

describe('RatingStars', () => {
    it('emite la nueva valoración al hacer clic', async () => {
        const wrapper = mount(RatingStars, { props: { modelValue: 0, editable: true } });

        await wrapper.findAll('[data-rating="star"]')[3].trigger('click');

        expect(wrapper.emitted('update:modelValue')[0]).toEqual([4]);
    });

    it('dibuja cinco chiles para el picante', () => {
        const wrapper = mount(RatingStars, {
            props: { modelValue: 2, kind: 'chili', label: 'Picante' },
        });

        expect(wrapper.findAll('[data-rating="chili"]')).toHaveLength(5);
        expect(wrapper.find('[data-rating="chili"]').attributes('aria-label')).toContain(
            'Picante'
        );
    });

    it('dibuja cinco corazones', () => {
        const wrapper = mount(RatingStars, {
            props: { modelValue: 5, kind: 'heart', label: 'Corazón' },
        });

        expect(wrapper.findAll('[data-rating="heart"]')).toHaveLength(5);
    });

    it('dibuja cinco gotas y cinco bolas de cristal', () => {
        const drops = mount(RatingStars, {
            props: { modelValue: 3, kind: 'drop', label: 'Gotas' },
        });
        const crystals = mount(RatingStars, {
            props: { modelValue: 1, kind: 'crystal', label: 'Bola de cristal' },
        });

        expect(drops.findAll('[data-rating="drop"]')).toHaveLength(5);
        expect(crystals.findAll('[data-rating="crystal"]')).toHaveLength(5);
    });
});
