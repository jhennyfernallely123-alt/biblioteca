import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import SeasonalParticles from '../SeasonalParticles.vue';

describe('SeasonalParticles', () => {
    it('deja caer hojas en otoño', () => {
        const wrapper = mount(SeasonalParticles, { props: { season: 'autumn' } });

        expect(wrapper.attributes('data-season-particles')).toBeDefined();
        expect(
            wrapper.findAll('.seasonal-particle__shape.seasonal-particle--leaf')
        ).toHaveLength(14);
    });

    it('deja caer copos en Navidad', () => {
        const wrapper = mount(SeasonalParticles, { props: { season: 'christmas' } });

        expect(
            wrapper.findAll('.seasonal-particle__shape.seasonal-particle--snow')
        ).toHaveLength(20);
    });

    it('deja caer corazones en febrero', () => {
        const wrapper = mount(SeasonalParticles, { props: { season: 'valentine' } });

        expect(
            wrapper.findAll('.seasonal-particle__shape.seasonal-particle--heart')
        ).toHaveLength(12);
    });

    it('no deja partículas en Halloween', () => {
        const wrapper = mount(SeasonalParticles, { props: { season: 'halloween' } });

        expect(wrapper.findAll('.seasonal-particle')).toHaveLength(0);
    });

    it('coloca cada partícula en una posición y con su color', () => {
        const wrapper = mount(SeasonalParticles, { props: { season: 'winter' } });

        const first = wrapper.find('.seasonal-particle');

        expect(first.attributes('style')).toContain('left:');
        expect(first.attributes('style')).toContain('--item-color');
    });
});
