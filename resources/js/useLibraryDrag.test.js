import { mount } from '@vue/test-utils';
import { defineComponent, h, nextTick } from 'vue';
import { describe, expect, it, vi } from 'vitest';
import { useLibraryDrag } from './useLibraryDrag';

function fire(type, props, target = window) {
    const event = new Event(type, { bubbles: true, cancelable: true });

    Object.entries(props).forEach(([key, value]) => {
        Object.defineProperty(event, key, { value, configurable: true });
    });

    target.dispatchEvent(event);
}

function mountDrag(onDrop) {
    const component = defineComponent({
        setup() {
            const drag = useLibraryDrag(
                () => [
                    {
                        id: 0,
                        books: [{ id: 1, title: 'Rayuela', view_mode: 'spines' }],
                        decorations: [],
                    },
                ],
                onDrop,
                { virtual: true }
            );

            return () =>
                h('div', { 'data-shelf-id': 0, onPointerdown: drag.onPointerDown }, [
                    h('a', {
                        'data-book-id': 1,
                        'data-test': 'book',
                        style: 'display: block; width: 40px; height: 40px',
                    }),
                ]);
        },
    });

    return mount(component, { attachTo: document.body });
}

describe('useLibraryDrag', () => {
    it('permite soltar en la banda 0', async () => {
        const onDrop = vi.fn();
        const wrapper = mountDrag(onDrop);

        document.elementFromPoint = vi.fn(() => wrapper.element);

        await nextTick();

        fire(
            'pointerdown',
            {
                button: 0,
                pointerType: 'mouse',
                pointerId: 1,
                clientX: 10,
                clientY: 10,
            },
            wrapper.get('[data-test="book"]').element
        );

        fire('pointermove', { pointerId: 1, clientX: 10, clientY: 35 });
        fire('pointerup', { pointerId: 1, clientX: 10, clientY: 35 });
        await nextTick();

        expect(onDrop).toHaveBeenCalled();
        expect(onDrop.mock.calls[0][1]).toBe(0);
        expect(onDrop.mock.calls[0][2]).toMatchObject({ shelfId: 0 });
    });
});
