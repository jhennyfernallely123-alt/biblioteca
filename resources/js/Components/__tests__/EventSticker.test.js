import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import EventSticker from '../EventSticker.vue';

const keys = [
    'feria_pluma',
    'feria_lampara',
    'dragones_escama',
    'dragones_alas',
    'invierno_copo',
    'invierno_campana',
    'enamorados_corazon',
    'enamorados_rosa',
    'brujas_calavera',
    'brujas_pocion',
    'flores_ramo',
    'flores_planta',
];

describe('EventSticker', () => {
    it('dibuja las 12 pegatinas', () => {
        for (const key of keys) {
            const wrapper = mount(EventSticker, { props: { itemKey: key } });
            const art = wrapper.find('.event-sticker__art');

            expect(art.exists()).toBe(true);
            expect(art.element.innerHTML.length).toBeGreaterThan(0);

            wrapper.unmount();
        }
    });

    it('la versión bloqueada no muestra la ilustración', () => {
        const wrapper = mount(EventSticker, {
            props: { itemKey: 'feria_pluma', locked: true },
        });

        expect(wrapper.find('.event-sticker__art').exists()).toBe(false);
        expect(wrapper.find('.event-sticker__locked').exists()).toBe(true);
        expect(wrapper.text()).toContain('?');
    });

    it('una clave desconocida no rompe el componente', () => {
        const wrapper = mount(EventSticker, { props: { itemKey: 'no_existe' } });

        expect(wrapper.find('.event-sticker__art').exists()).toBe(true);
        expect(wrapper.find('.event-sticker__art').element.innerHTML.length).toBeGreaterThan(0);
    });
});
