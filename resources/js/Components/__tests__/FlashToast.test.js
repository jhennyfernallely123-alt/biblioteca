import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import FlashToast from '../FlashToast.vue';

describe('FlashToast', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('muestra el aviso y lo oculta después de cuatro segundos', async () => {
        const wrapper = mount(FlashToast, {
            props: { text: 'Libro actualizado.' },
        });

        const notice = wrapper.get('[role="status"]');

        expect(notice.text()).toContain('Libro actualizado.');
        expect(notice.attributes('aria-live')).toBe('polite');

        vi.advanceTimersByTime(4000);
        await nextTick();

        expect(wrapper.find('[role="status"]').exists()).toBe(false);
    });
});
