import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ page: null }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mocks.page,
}));

import CardLetterModal from '../CardLetterModal.vue';

const letter = {
    card: {
        key: 'violet',
        family: 'empireo',
        familyLabel: 'Empíreo',
        familyEmblem: 'dragon',
        character: 'Violet Sorrengail',
        monogram: 'VS',
        emblem: 'dragon',
        letter: 'Primer párrafo de la carta.\n\nSegundo párrafo con consejos.\n\nTercer párrafo de despedida.',
    },
};

function mountModal(props = {}) {
    return mount(CardLetterModal, {
        props: {
            show: true,
            letter,
            copies: 2,
            duplicate: true,
            ...props,
        },
        global: {
            stubs: {
                Modal: {
                    props: ['show', 'maxWidth'],
                    emits: ['close'],
                    template: '<div v-if="show"><slot /></div>',
                },
            },
        },
    });
}

describe('CardLetterModal', () => {
    beforeEach(() => {
        mocks.page = { props: { locale: 'es', translations: {} } };
    });

    it('muestra la carta en pergamino con sus párrafos', () => {
        const wrapper = mountModal();

        const paragraphs = wrapper.findAll('.card-letter__body p');

        expect(paragraphs).toHaveLength(3);
        expect(paragraphs[0].classes()).toContain('card-letter__dropcap');
    });

    it('presenta solo quien la escribe, sin familia ni monograma', () => {
        const wrapper = mountModal();

        expect(wrapper.text()).toContain('Violet Sorrengail');
        expect(wrapper.text()).not.toContain('Empíreo');
        expect(wrapper.text()).not.toContain('VS');
    });

    it('muestra las copias guardadas de una carta', () => {
        const wrapper = mountModal({ duplicate: false });

        expect(wrapper.text()).toContain('Carta nueva');
        expect(wrapper.text()).toContain('×2');
    });

    it('cierra con el botón de cerrar', async () => {
        const wrapper = mountModal();

        await wrapper.get('[data-letter-close]').trigger('click');

        expect(wrapper.emitted('close')).toEqual([[]]);
    });

    it('no renderiza la carta sin contenido', () => {
        const wrapper = mountModal({ letter: null });

        expect(wrapper.find('article').exists()).toBe(false);
    });
});
