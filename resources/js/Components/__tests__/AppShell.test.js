import { enableAutoUnmount, mount } from '@vue/test-utils';
import { h, nextTick, reactive } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { resetWelcome } from '../welcomeIntro';

const mocks = vi.hoisted(() => ({ page: null }));

vi.mock('@inertiajs/vue3', () => ({
    Link: {
        props: ['href'],
        inheritAttrs: false,
        setup(props, { attrs, slots }) {
            return () => h('a', { href: props.href, ...attrs }, slots.default?.());
        },
    },
    router: { post: vi.fn() },
    usePage: () => mocks.page,
}));

import AppShell from '../AppShell.vue';

enableAutoUnmount(afterEach);

describe('AppShell', () => {
    beforeEach(() => {
        resetWelcome();
        mocks.page = reactive({
            props: {
                auth: { user: { name: 'Ana', nickname: 'Jhenny' } },
                flash: { success: null },
                translations: {},
                locale: 'es',
                direction: 'ltr',
            },
            url: '/',
        });
    });

    it('muestra el aviso de éxito enviado por el servidor', async () => {
        const wrapper = mount(AppShell, {
            slots: { default: '<p>Contenido</p>' },
        });

        mocks.page.props.flash = { success: 'Libro actualizado.' };
        await nextTick();

        expect(wrapper.text()).toContain('Libro actualizado.');
    });

    it('muestra la intro en la primera carga y la cierra con Entrar', async () => {
        const wrapper = mount(AppShell, {
            slots: { default: '<p>Contenido</p>' },
        });
        await nextTick();

        expect(wrapper.find('[role="dialog"]').exists()).toBe(true);
        expect(wrapper.text()).toContain('Bienvenida, Jhenny');

        await wrapper.get('[data-welcome-enter]').trigger('click');

        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
    });

    it('usa el nombre si la cuenta no tiene apodo', async () => {
        mocks.page.props.auth.user = { name: 'Ana' };

        const wrapper = mount(AppShell, {
            slots: { default: '<p>Contenido</p>' },
        });
        await nextTick();

        expect(wrapper.text()).toContain('Bienvenida, Ana');
    });

    it('no muestra el nombre real en la barra lateral y deja Cerrar sesión arriba', async () => {
        const fullName = 'Jhennyfer Nallely Arevalo Naranjo';
        mocks.page.props.auth.user = { name: fullName, nickname: 'Jhenny' };

        const wrapper = mount(AppShell, {
            slots: { default: '<p>Contenido</p>' },
        });
        await nextTick();
        await wrapper.get('[data-welcome-enter]').trigger('click');

        const aside = wrapper.get('aside');
        expect(aside.text()).not.toContain(fullName);
        expect(aside.element.firstElementChild.textContent).toContain('Cerrar sesión');
    });

    it('no repite la intro al navegar dentro de la aplicación', async () => {
        const first = mount(AppShell, {
            slots: { default: '<p>Contenido</p>' },
        });
        await nextTick();

        await first.get('[data-welcome-enter]').trigger('click');
        first.unmount();

        const second = mount(AppShell, {
            slots: { default: '<p>Contenido</p>' },
        });
        await nextTick();

        expect(second.find('[role="dialog"]').exists()).toBe(false);
    });
});
