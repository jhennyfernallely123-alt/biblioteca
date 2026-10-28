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
        email: '',
        password: '',
        remember: false,
        processing: false,
        errors: {},
        post: mocks.post,
        reset: mocks.reset,
    }),
}));

import Login from '../Login.vue';

describe('Login', () => {
    it('muestra el formulario, el ojo de contraseña y los proveedores deshabilitados', () => {
        const wrapper = mount(Login, { props: { canResetPassword: true } });

        expect(wrapper.text()).toContain('Iniciar sesión');
        expect(wrapper.find('#email').exists()).toBe(true);
        expect(wrapper.get('#password').attributes('type')).toBe('password');
        expect(wrapper.text()).toContain('o continúa con');
        expect(wrapper.text()).toContain('Próximamente');
    });

    it('envía el formulario a /login', async () => {
        const wrapper = mount(Login, { props: { canResetPassword: true } });

        await wrapper.get('form').trigger('submit');

        expect(mocks.post).toHaveBeenCalledWith('/login', expect.any(Object));
    });
});
