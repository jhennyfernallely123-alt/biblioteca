import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ page: null }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mocks.page,
}));

import AlbumSlot from '../AlbumSlot.vue';

describe('AlbumSlot', () => {
    beforeEach(() => {
        mocks.page = { props: { locale: 'es', translations: {} } };
    });

    it('renderiza una carta desbloqueada como sobre cerrado con nombre', () => {
        const wrapper = mount(AlbumSlot, {
            props: {
                item: {
                    type: 'card',
                    key: 'violet',
                    name: 'Violet Sorrengail',
                    category: 'Empíreo',
                    rarity: 'comun',
                    unlocked: true,
                },
            },
        });

        expect(wrapper.find('svg').exists()).toBe(true);
        expect(wrapper.attributes('aria-label')).toBe('Violet Sorrengail');
    });

    it('renderiza un sticker desbloqueado como sobre cerrado con nombre', () => {
        const wrapper = mount(AlbumSlot, {
            props: {
                item: {
                    type: 'sticker',
                    key: 'maquina_libro',
                    name: 'Libro',
                    category: 'Stickers de la máquina',
                    rarity: 'comun',
                    unlocked: true,
                },
            },
        });

        expect(wrapper.find('svg').exists()).toBe(true);
        expect(wrapper.attributes('aria-label')).toBe('Libro');
    });

    it('muestra estado bloqueado como sobre cerrado con ???', () => {
        const wrapper = mount(AlbumSlot, {
            props: {
                item: {
                    type: 'card',
                    key: 'xaden',
                    name: 'Xaden Riorson',
                    category: 'Empíreo',
                    rarity: 'comun',
                    unlocked: false,
                },
            },
        });

        expect(wrapper.find('svg').exists()).toBe(true);
        expect(wrapper.attributes('aria-label')).toContain('Bloqueado');
    });
});
