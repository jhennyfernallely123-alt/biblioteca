import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ page: { props: { flash: {} } } }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mocks.page,
}));

import GuestLayout from '../GuestLayout.vue';

describe('GuestLayout', () => {
    it('muestra el emblema y el contenido', () => {
        const wrapper = mount(GuestLayout, {
            slots: { default: '<p>Formulario</p>' },
        });

        expect(wrapper.find('.auth-emblem').exists()).toBe(true);
        expect(wrapper.text()).toContain('Formulario');
    });

    it('enmarca el emblema con sus estrellas dentro del cuadro morado', () => {
        const wrapper = mount(GuestLayout, {
            slots: { default: '<p>Formulario</p>' },
        });
        const frames = wrapper.findAll('.auth-book-frame');

        expect(frames).toHaveLength(2);
        frames.forEach((frame) => {
            expect(frame.find('.auth-emblem').exists()).toBe(true);
            expect(frame.find('.arcane-stars').exists()).toBe(true);
        });
    });

    it('muestra el error compartido encima del formulario', () => {
        mocks.page.props.flash = { error: 'No pudimos entrar.' };

        const wrapper = mount(GuestLayout, {
            slots: { default: '<p>Formulario</p>' },
        });

        expect(wrapper.text()).toContain('No pudimos entrar.');
        expect(wrapper.get('[role="alert"]').exists()).toBe(true);
    });
});
