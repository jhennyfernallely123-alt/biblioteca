import { mount } from '@vue/test-utils';
import { h } from 'vue';
import { describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    post: vi.fn(),
    reset: vi.fn(),
    page: { props: { socialProviders: { google: false, facebook: false }, flash: {} } },
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: {
        props: ['href'],
        inheritAttrs: false,
        setup(props, { attrs, slots }) {
            return () => h('a', { href: props.href, ...attrs }, slots.default?.());
        },
    },
    usePage: () => mocks.page,
    useForm: () => ({
        name: '',
        nickname: '',
        email: '',
        password: '',
        password_confirmation: '',
        processing: false,
        errors: {},
        post: mocks.post,
        reset: mocks.reset,
    }),
}));

import Register from '../Register.vue';

describe('Register', () => {
    it('muestra nombre, apodo, correo y los proveedores deshabilitados', () => {
        const wrapper = mount(Register);

        expect(wrapper.text()).toContain('Crear cuenta');
        expect(wrapper.find('#name').exists()).toBe(true);
        expect(wrapper.find('#nickname').exists()).toBe(true);
        expect(wrapper.text()).toContain('Próximamente');
    });
});
