import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import CardEmblem from '../CardEmblem.vue';

describe('CardEmblem', () => {
    it('dibuja un svg para cada emblema del catálogo', () => {
        const names = [
            'dragon', 'shadow', 'wing', 'feather', 'shield', 'sword', 'compass',
            'flame', 'knot', 'map', 'lantern', 'star', 'moon', 'chess', 'cat',
            'music', 'mask', 'crown', 'key', 'rose',
        ];

        for (const name of names) {
            const wrapper = mount(CardEmblem, { props: { name } });
            expect(wrapper.find('svg').exists()).toBe(true);
            expect(wrapper.findAll('path').length).toBeGreaterThan(0);
        }
    });

    it('cae a la estrella si el emblema no existe', () => {
        const wrapper = mount(CardEmblem, { props: { name: 'desconocido' } });

        expect(wrapper.find('svg').exists()).toBe(true);
    });
});
