import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/vue3';
import ShelfDecoration from '../ShelfDecoration.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal();

    return { ...original, router: { delete: vi.fn() } };
});

function makeDecoration(overrides = {}) {
    return { id: 9, kind: 'cup', color: 'rose', shelf_id: 7, after_book_id: 3, sort: 0, ...overrides };
}

describe('ShelfDecoration', () => {
    it('se identifica para el arrastre y anuncia su nombre', () => {
        const wrapper = mount(ShelfDecoration, { props: { decoration: makeDecoration() } });

        expect(wrapper.find('[data-decoration-id="9"]').exists()).toBe(true);
        expect(wrapper.find('[role="img"]').attributes('aria-label')).toBe('Taza');
    });

    it('atenúa el objeto mientras se arrastra', () => {
        const wrapper = mount(ShelfDecoration, {
            props: { decoration: makeDecoration(), dragging: true },
        });

        expect(wrapper.classes()).toContain('opacity-30');
    });

    it('quita el objeto con el botón ×', async () => {
        const wrapper = mount(ShelfDecoration, { props: { decoration: makeDecoration() } });

        await wrapper.find('button').trigger('click');

        expect(router.delete).toHaveBeenCalledWith('/decorations/9', {
            preserveScroll: true,
            preserveState: true,
        });
    });
});
