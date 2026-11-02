import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import SeasonalDecor from '../SeasonalDecor.vue';

describe('SeasonalDecor', () => {
    it('no usa adornos en movimiento', () => {
        const wrapper = mount(SeasonalDecor, { props: { season: 'autumn' } });

        expect(wrapper.findAll('.seasonal-particle')).toHaveLength(0);
    });

    it('cuelga una guirnalda en la parte superior y una enredadera lateral', () => {
        const wrapper = mount(SeasonalDecor, { props: { season: 'autumn' } });

        expect(wrapper.attributes('data-season-decor')).toBe('autumn');
        expect(wrapper.findAll('.seasonal-trim__hang')).toHaveLength(8);
        expect(wrapper.findAll('.seasonal-vine__leaf')).toHaveLength(10);
    });

    it('dibuja bolas de Navidad en la guirnalda', () => {
        const wrapper = mount(SeasonalDecor, { props: { season: 'christmas' } });

        expect(
            wrapper.findAll('.seasonal-trim__item.seasonal-particle--bauble')
        ).toHaveLength(8);
    });

    it('dibuja telarañas, arañas y murciélagos en Halloween', () => {
        const wrapper = mount(SeasonalDecor, { props: { season: 'halloween' } });

        expect(wrapper.findAll('.seasonal-web')).toHaveLength(2);
        expect(wrapper.findAll('.seasonal-spider')).toHaveLength(2);
        expect(wrapper.findAll('.seasonal-trim__bat')).toHaveLength(8);
    });

    it('coloca cada adorno con su color de temporada', () => {
        const wrapper = mount(SeasonalDecor, { props: { season: 'winter' } });

        const first = wrapper.find('.seasonal-trim__item');

        expect(first.attributes('style')).toContain('--item-color');
    });
});
