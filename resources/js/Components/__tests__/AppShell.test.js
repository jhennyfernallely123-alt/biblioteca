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
    router: { post: vi.fn(), push: vi.fn() },
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

    it('enlaza las estanterías y las decoraciones entre la biblioteca y los ajustes', async () => {
        const wrapper = mount(AppShell, {
            slots: { default: '<p>Contenido</p>' },
        });
        await nextTick();
        await wrapper.get('[data-welcome-enter]').trigger('click');

        const tabs = wrapper.get('.bottom-nav').findAll('.tab');
        await tabs[2].trigger('click');
        await nextTick();

        const menu = wrapper.find('.album-menu');
        const links = menu.findAll('.album-menu__item');

        expect(menu.exists()).toBe(true);
        expect(links).toHaveLength(6);
        expect(menu.text()).toContain('Biblioteca');
        expect(menu.text()).toContain('Estanterías');
        expect(menu.text()).toContain('Álbum');
        expect(menu.text()).toContain('Cerrar sesión');
    });

    it('deja Biblioteca y Estanterías en la barra móvil y el resto en el menú álbum', async () => {
        const wrapper = mount(AppShell, {
            slots: { default: '<p>Contenido</p>' },
        });
        await nextTick();
        await wrapper.get('[data-welcome-enter]').trigger('click');
        await nextTick();

        const nav = wrapper.get('.bottom-nav');
        const tabs = nav.findAll('.tab');
        expect(tabs).toHaveLength(3);

        // Hacer clic en el tab de menú para abrir el álbum
        await tabs[2].trigger('click');

        expect(wrapper.find('.album-menu').exists()).toBe(true);
        const menu = wrapper.find('.album-menu');
        expect(menu.findAll('.album-menu__item')).toHaveLength(6);
        expect(menu.text()).toContain('Biblioteca');
        expect(menu.text()).toContain('Decoraciones');
        expect(menu.text()).toContain('Álbum');
        expect(menu.text()).toContain('Cerrar sesión');

        // Cerrar el álbum haciendo clic de nuevo en el menú
        await tabs[2].trigger('click');
        expect(wrapper.find('.album-menu').exists()).toBe(false);
    });

    it('aplica la temática del mes actual', async () => {
        const wrapper = mount(AppShell, {
            slots: { default: '<p>Contenido</p>' },
        });
        await nextTick();

        const season = wrapper.attributes('data-season');

        expect([
            'winter',
            'valentine',
            'spring',
            'rain',
            'flowers',
            'summer',
            'sun',
            'sunflower',
            'harvest',
            'halloween',
            'autumn',
            'christmas',
        ]).toContain(season);
        expect(wrapper.find(`[data-season-decor="${season}"]`).exists()).toBe(true);
        expect(wrapper.find('[data-season-particles]').exists()).toBe(true);
    });

    it('activa la Navidad en diciembre', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date(2026, 11, 1, 12));

        try {
            const wrapper = mount(AppShell, {
                slots: { default: '<p>Contenido</p>' },
            });
            await nextTick();

            expect(wrapper.attributes('data-season')).toBe('christmas');
            expect(wrapper.find('[data-season-decor="christmas"]').exists()).toBe(true);
        } finally {
            vi.useRealTimers();
        }
    });

    it('cuelga la guirnalda de la cabecera en móvil', async () => {
        const wrapper = mount(AppShell, {
            slots: { default: '<p>Contenido</p>' },
        });
        await nextTick();

        expect(wrapper.find('header [data-season-garland]').exists()).toBe(true);
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

    it('muestra el catálogo en la barra lateral solo para la cuenta master', async () => {
        mocks.page.props.auth.user = { name: 'Ana', nickname: 'Jhenny', is_master: true };

        const wrapper = mount(AppShell, {
            slots: { default: '<p>Contenido</p>' },
        });
        await nextTick();
        await wrapper.get('[data-welcome-enter]').trigger('click');

        const aside = wrapper.get('aside');
        expect(aside.text()).toContain('Catálogo');
        expect(aside.find('a[href="/catalog"]').exists()).toBe(true);
    });

    it('oculta el catálogo para una cuenta normal', async () => {
        mocks.page.props.auth.user = { name: 'Ana', nickname: 'Jhenny', is_master: false };

        const wrapper = mount(AppShell, {
            slots: { default: '<p>Contenido</p>' },
        });
        await nextTick();
        await wrapper.get('[data-welcome-enter]').trigger('click');

        const aside = wrapper.get('aside');
        expect(aside.text()).not.toContain('Catálogo');
        expect(aside.find('a[href="/catalog"]').exists()).toBe(false);
    });
});
