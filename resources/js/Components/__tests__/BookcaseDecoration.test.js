import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/vue3';
import BookcaseDecoration from '../BookcaseDecoration.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal();

    return { ...original, router: { delete: vi.fn() } };
});

function makeDecoration(overrides = {}) {
    return { id: 9, kind: 'cup', color: 'rose', shelf_id: 7, after_book_id: 3, sort: 0, ...overrides };
}

describe('BookcaseDecoration', () => {
    it('se identifica para el arrastre y anuncia su nombre', () => {
        const wrapper = mount(BookcaseDecoration, { props: { decoration: makeDecoration() } });

        expect(wrapper.find('[data-decoration-id="9"]').exists()).toBe(true);
        expect(wrapper.find('[role="img"]').attributes('aria-label')).toBe('Taza');
    });

    it('atenúa el objeto mientras se arrastra', () => {
        const wrapper = mount(BookcaseDecoration, {
            props: { decoration: makeDecoration(), dragging: true },
        });

        expect(wrapper.classes()).toContain('opacity-30');
    });

    it('ofrece la × para quitar el objeto', () => {
        const wrapper = mount(BookcaseDecoration, { props: { decoration: makeDecoration() } });

        const button = wrapper.find('[data-decoration-remove]');

        expect(button.exists()).toBe(true);
        expect(button.attributes('aria-label')).toBe('Quitar objeto');
    });

    it('mantiene la × oculta en escritorio y visible en móvil', () => {
        const wrapper = mount(BookcaseDecoration, { props: { decoration: makeDecoration() } });

        const classes = wrapper.find('[data-decoration-remove]').classes();

        expect(classes).toContain('sm:opacity-0');
        expect(classes).toContain('sm:group-hover:opacity-100');
        expect(classes).not.toContain('hidden');
    });

    it('quita el objeto con la ×', async () => {
        const wrapper = mount(BookcaseDecoration, { props: { decoration: makeDecoration() } });

        await wrapper.find('[data-decoration-remove]').trigger('click');

        expect(router.delete).toHaveBeenCalledWith('/decorations/9', {
            preserveScroll: true,
            preserveState: true,
        });
    });

    it('no deja que la × arranque el arrastre', async () => {
        const wrapper = mount(BookcaseDecoration, { props: { decoration: makeDecoration() } });

        const onPointerDown = vi.fn();

        wrapper.element.addEventListener('pointerdown', onPointerDown);

        await wrapper.find('[role="img"]').trigger('pointerdown');

        expect(onPointerDown).toHaveBeenCalledTimes(1);

        await wrapper.find('[data-decoration-remove]').trigger('pointerdown');

        expect(onPointerDown).toHaveBeenCalledTimes(1);
    });
});
