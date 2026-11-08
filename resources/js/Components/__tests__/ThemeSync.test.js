import { enableAutoUnmount, mount } from '@vue/test-utils';
import { nextTick, reactive } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ page: null }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mocks.page,
}));

import ThemeSync from '../ThemeSync.vue';

enableAutoUnmount(afterEach);

describe('ThemeSync', () => {
    beforeEach(() => {
        mocks.page = reactive({
            props: { auth: { user: { theme: 'dark' } } },
        });
    });

    afterEach(() => {
        delete document.documentElement.dataset.theme;
    });

    it('aplica el tema guardado en la cuenta', async () => {
        mount(ThemeSync);
        await nextTick();

        expect(document.documentElement.dataset.theme).toBe('dark');
    });

    it('reacciona al cambio de tema', async () => {
        mount(ThemeSync);
        await nextTick();

        mocks.page.props.auth.user.theme = 'light';
        await nextTick();

        expect(document.documentElement.dataset.theme).toBe('light');
    });
});
