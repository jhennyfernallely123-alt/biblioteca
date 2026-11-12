import { enableAutoUnmount, mount } from '@vue/test-utils';
import { h, reactive } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ page: null }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mocks.page,
}));

import CardMachine from '../CardMachine.vue';

enableAutoUnmount(afterEach);

const offers = [{ index: 0 }, { index: 1 }, { index: 2 }];

const cardResult = {
    kind: 'card',
    card: {
        key: 'violet',
        family: 'empireo',
        familyLabel: 'Empíreo',
        familyEmblem: 'dragon',
        character: 'Violet Sorrengail',
        monogram: 'VS',
        emblem: 'dragon',
    },
    sticker: null,
};

const stickerResult = {
    kind: 'sticker',
    card: null,
    sticker: {
        key: 'maquina_libro',
        name: 'Libro abierto',
    },
};

function mountMachine(props = {}) {
    return mount(CardMachine, {
        props: {
            offers,
            drawing: false,
            result: null,
            resetAt: '2026-11-04T05:00:00Z',
            complete: false,
            ...props,
        },
    });
}

describe('CardMachine', () => {
    beforeEach(() => {
        mocks.page = reactive({ props: { locale: 'es', translations: {} } });
    });

    it('monta la escena, la ventana y la trampilla', () => {
        const wrapper = mountMachine();

        expect(wrapper.find('.card-machine__scene').exists()).toBe(true);
        expect(wrapper.find('.card-machine__window').exists()).toBe(true);
        expect(wrapper.find('.card-machine__dome').exists()).toBe(true);
        expect(wrapper.find('.card-machine__lever').exists()).toBe(true);
        expect(wrapper.find('.card-machine__slot').exists()).toBe(true);
    });

    it('deja la manivela lista para girar sin elegir perla', () => {
        const wrapper = mountMachine();

        const lever = wrapper.get('.card-machine__lever');

        expect(lever.attributes('disabled')).toBeUndefined();
        expect(wrapper.text()).toContain('Gira la manivela para sacar una sorpresa.');
    });

    it('emite crank con un índice de perla válido al pulsar', async () => {
        const wrapper = mountMachine();

        await wrapper.get('.card-machine__lever').trigger('click');

        const emitted = wrapper.emitted('crank');

        expect(emitted).toHaveLength(1);
        expect(emitted[0][0]).toBeGreaterThanOrEqual(0);
        expect(emitted[0][0]).toBeLessThan(offers.length);
    });

    it('bloquea la manivela mientras dibuja', () => {
        const wrapper = mountMachine({ drawing: true });

        const lever = wrapper.get('.card-machine__lever');

        expect(lever.attributes('disabled')).toBeDefined();
        expect(lever.classes()).toContain('card-machine__crank--turning');
        expect(wrapper.text()).toContain('Girando');
    });

    it('muestra el sobre cuando el resultado es una carta', async () => {
        const wrapper = mountMachine({ result: cardResult });

        expect(wrapper.get('.card-envelope').exists()).toBe(true);
        expect(wrapper.text()).toContain('Carta nueva');

        await wrapper.get('.card-envelope').trigger('click');

        expect(wrapper.emitted('open')).toEqual([[]]);
    });

    it('muestra el sticker y no emite open', async () => {
        const wrapper = mountMachine({ result: stickerResult });

        expect(wrapper.find('.library-sticker').exists()).toBe(true);
        expect(wrapper.text()).toContain('Libro abierto');
        expect(wrapper.text()).toContain('¡Sticker nuevo!');

        await wrapper.get('.library-sticker').trigger('click');

        expect(wrapper.emitted('open')).toBeUndefined();
    });

    it('muestra la colección completa sin manivela', () => {
        const wrapper = mountMachine({ complete: true });

        expect(wrapper.text()).toContain('¡Colección completa!');
        expect(wrapper.find('.card-machine__lever').exists()).toBe(false);
    });
});
