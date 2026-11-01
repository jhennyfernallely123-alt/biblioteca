import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    post: vi.fn(),
    delete: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    router: { post: mocks.post, delete: mocks.delete },
    usePage: () => ({ props: {} }),
}));

import DecorationCatalog from '../DecorationCatalog.vue';

describe('DecorationCatalog', () => {
    beforeEach(() => {
        mocks.post.mockClear();
        mocks.delete.mockClear();
    });

    it('muestra la barra de categorías y los quince objetos en «Todos»', () => {
        const wrapper = mount(DecorationCatalog, { props: { decorations: [] } });

        const filters = wrapper.findAll('[data-category-filter]');

        expect(filters.map((item) => item.attributes('data-category-filter'))).toEqual([
            'all',
            'table',
            'plants',
            'light',
            'curios',
            'keepsakes',
        ]);
        expect(filters[0].attributes('aria-pressed')).toBe('true');
        expect(wrapper.findAll('[data-category-filter] svg')).toHaveLength(6);
        expect(wrapper.findAll('[data-decoration-card]')).toHaveLength(15);
    });

    it('filtra el catálogo al elegir una categoría', async () => {
        const wrapper = mount(DecorationCatalog, { props: { decorations: [] } });

        await wrapper.find('[data-category-filter="plants"]').trigger('click');

        const cards = wrapper.findAll('[data-decoration-card]');

        expect(cards.map((card) => card.attributes('data-decoration-card'))).toEqual([
            'roses',
            'plant',
        ]);
        expect(wrapper.find('[data-category-filter="plants"]').attributes('aria-pressed')).toBe('true');

        await wrapper.find('[data-category-filter="all"]').trigger('click');

        expect(wrapper.findAll('[data-decoration-card]')).toHaveLength(15);
    });

    it('muestra el catálogo y añade con el color por defecto', async () => {
        const wrapper = mount(DecorationCatalog, { props: { decorations: [] } });

        expect(wrapper.find('[data-decoration-card="cup"]').exists()).toBe(true);

        await wrapper.find('[data-decoration-add="cup"]').trigger('click');

        expect(mocks.post).toHaveBeenCalledWith(
            '/decorations',
            { kind: 'cup', color: 'cream' },
            { preserveScroll: true }
        );
    });

    it('añade con el color elegido', async () => {
        const wrapper = mount(DecorationCatalog, { props: { decorations: [] } });

        expect(wrapper.find('[data-color-palette="cup"]').exists()).toBe(false);

        await wrapper.find('[data-color-toggle="cup"]').trigger('click');

        expect(wrapper.find('[data-color-palette="cup"]').exists()).toBe(true);

        await wrapper.find('[data-color="cup"][data-color-key="gold"]').trigger('click');

        expect(wrapper.find('[data-color-palette="cup"]').exists()).toBe(false);
        expect(wrapper.find('[data-color-toggle="cup"]').attributes('aria-expanded')).toBe('false');

        await wrapper.find('[data-decoration-add="cup"]').trigger('click');

        expect(mocks.post).toHaveBeenCalledWith(
            '/decorations',
            { kind: 'cup', color: 'gold' },
            { preserveScroll: true }
        );
    });

    it('no muestra el listado de objetos colocados', () => {
        const wrapper = mount(DecorationCatalog, {
            props: {
                decorations: [
                    { id: 5, kind: 'cup', color: 'rose', shelf: { id: 1, name: 'En biblioteca' } },
                ],
            },
        });

        expect(wrapper.find('[data-decoration-row]').exists()).toBe(false);
        expect(wrapper.find('[data-decorations-clear]').exists()).toBe(false);
    });

    it('convierte el botón en quitar y elimina el último objeto de ese tipo', async () => {
        const wrapper = mount(DecorationCatalog, {
            props: {
                decorations: [
                    { id: 5, kind: 'cup', color: 'rose', shelf: { id: 1, name: 'En biblioteca' } },
                    { id: 9, kind: 'cup', color: 'gold', shelf: { id: 1, name: 'En biblioteca' } },
                ],
            },
        });

        expect(wrapper.find('[data-decoration-add="cup"]').exists()).toBe(false);
        expect(wrapper.find('[data-decoration-remove="cup"]').text()).toBe('Quitar');
        expect(wrapper.find('[data-decoration-add="roses"]').exists()).toBe(true);

        await wrapper.find('[data-decoration-remove="cup"]').trigger('click');

        expect(mocks.delete).toHaveBeenCalledWith('/decorations/9', { preserveScroll: true });
    });
});
