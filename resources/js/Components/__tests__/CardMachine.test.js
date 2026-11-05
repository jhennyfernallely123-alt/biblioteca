import { enableAutoUnmount, mount } from '@vue/test-utils';
import { h, reactive } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ page: null }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mocks.page,
}));

import CardMachine from '../CardMachine.vue';

enableAutoUnmount(afterEach);

const offers = [
    { index: 0, family: 'empireo', familyLabel: 'Empíreo', familyEmblem: 'dragon' },
    { index: 1, family: 'empireo', familyLabel: 'Empíreo', familyEmblem: 'dragon' },
    { index: 2, family: 'empireo', familyLabel: 'Empíreo', familyEmblem: 'dragon' },
];

const result = {
    card: {
        key: 'violet',
        family: 'empireo',
        familyLabel: 'Empíreo',
        familyEmblem: 'dragon',
        character: 'Violet Sorrengail',
        monogram: 'VS',
        emblem: 'dragon',
    },
    duplicate: false,
    copies: 1,
    inkEarned: 0,
};

function mountMachine(props = {}) {
    return mount(CardMachine, {
        props: {
            offers,
            drawing: false,
            result: null,
            resetAt: '2026-11-04T05:00:00Z',
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
        expect(wrapper.text()).toContain('Gira la manivela para sacar una carta.');
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
        expect(wrapper.text()).toContain('Girando…');
    });

    it('muestra el sobre con el resultado y emite open', async () => {
        const wrapper = mountMachine({ result });

        expect(wrapper.get('.card-envelope').exists()).toBe(true);
        expect(wrapper.text()).toContain('Carta nueva');

        await wrapper.get('.card-envelope').trigger('click');

        expect(wrapper.emitted('open')).toEqual([[]]);
    });

    it('avisa de una carta repetida con tinta', () => {
        const wrapper = mountMachine({
            result: { ...result, duplicate: true, copies: 2, inkEarned: 1 },
        });

        expect(wrapper.text()).toContain('Ya tenías esta carta: +1 tinta');
    });
});
