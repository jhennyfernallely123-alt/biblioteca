import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ post: vi.fn(), delete: vi.fn() }));

vi.mock('@inertiajs/vue3', () => ({ router: { post: mocks.post, delete: mocks.delete } }));

import BookImages from '../BookImages.vue';

describe('BookImages', () => {
    beforeEach(() => {
        mocks.post.mockClear();
        mocks.delete.mockClear();
    });

    it('muestra tres ranuras vacías', () => {
        const wrapper = mount(BookImages, { props: { bookId: 1 } });

        expect(
            wrapper.findAll('button[aria-label="Añadir imagen del libro"]')
        ).toHaveLength(3);
    });

    it('muestra la imagen guardada y la quita', async () => {
        const wrapper = mount(BookImages, {
            props: { bookId: 1, images: [{ id: 5, slot: 1 }] },
        });

        expect(wrapper.find('img').attributes('src')).toBe('/book-images/5');

        await wrapper.find('button[aria-label="Quitar imagen"]').trigger('click');

        expect(mocks.delete).toHaveBeenCalledWith('/book-images/5', {
            preserveScroll: true,
        });
    });
});
