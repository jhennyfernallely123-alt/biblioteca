import { enableAutoUnmount, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import CardAlbum from '../CardAlbum.vue';

enableAutoUnmount(afterEach);

const cards = [
    {
        key: 'violet',
        family: 'empireo',
        familyLabel: 'Empíreo',
        familyEmblem: 'dragon',
        character: 'Violet Sorrengail',
        monogram: 'VS',
        emblem: 'dragon',
        unlocked: true,
        copies: 2,
    },
    {
        key: 'xaden',
        family: 'empireo',
        familyLabel: 'Empíreo',
        familyEmblem: 'dragon',
        character: 'Xaden Riorson',
        monogram: 'XR',
        emblem: 'shadow',
        unlocked: true,
        copies: 1,
    },
    {
        key: 'grace',
        family: 'mapa',
        familyLabel: 'El mapa de los anhelos',
        familyEmblem: 'map',
        character: 'Grace Peterson',
        monogram: 'GP',
        emblem: 'map',
        unlocked: false,
        copies: 0,
    },
];

describe('CardAlbum', () => {
    it('muestra una ranura por carta con su progreso', () => {
        const wrapper = mount(CardAlbum, {
            props: {
                cards,
                progress: { unlocked: 2, total: 3 },
            },
        });

        expect(wrapper.findAll('.card-slot')).toHaveLength(3);
        expect(wrapper.text()).toContain('2 de 3 cartas');
    });

    it('deja las cartas bloqueadas con interrogante y sin botón', () => {
        const wrapper = mount(CardAlbum, {
            props: {
                cards,
                progress: { unlocked: 2, total: 3 },
            },
        });

        const locked = wrapper.findAll('.card-slot')[2];

        expect(locked.element.tagName).toBe('DIV');
        expect(locked.text()).toContain('?');
        expect(locked.text()).toContain('Aún no la tienes');
    });

    it('abre la carta desbloqueada con su clave', async () => {
        const wrapper = mount(CardAlbum, {
            props: {
                cards,
                progress: { unlocked: 2, total: 3 },
            },
        });

        const unlocked = wrapper.findAll('.card-slot');

        expect(unlocked[0].element.tagName).toBe('BUTTON');

        await unlocked[0].trigger('click');
        await unlocked[1].trigger('click');

        expect(wrapper.emitted('open')).toEqual([['violet'], ['xaden']]);
    });

    it('muestra el número de copias repetidas', () => {
        const wrapper = mount(CardAlbum, {
            props: {
                cards,
                progress: { unlocked: 2, total: 3 },
            },
        });

        expect(wrapper.text()).toContain('×2');
    });
});
