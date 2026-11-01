import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import DecorationArt from '../DecorationArt.vue';

const KINDS = [
    'cup', 'roses', 'plant', 'candle', 'hourglass', 'crystal_ball', 'lamp',
    'cat', 'skull', 'cage', 'astrolabe', 'tea_set', 'music_box', 'quill',
    'photo_frame',
];

describe('DecorationArt', () => {
    it('dibuja cada tipo de objeto', () => {
        for (const kind of KINDS) {
            const wrapper = mount(DecorationArt, { props: { kind, color: 'rose' } });

            expect(wrapper.find('svg').exists()).toBe(true);
        }
    });

    it('pinta el color elegido con los tokens existentes', () => {
        const wrapper = mount(DecorationArt, { props: { kind: 'cup', color: 'gold' } });

        expect(wrapper.find('svg').attributes('style')).toContain('--color-gold');
    });

    it('usa un color de reserva si el color no existe', () => {
        const wrapper = mount(DecorationArt, { props: { kind: 'cup', color: 'neon' } });

        expect(wrapper.find('svg').attributes('style')).toContain('--color-crema');
    });
});
