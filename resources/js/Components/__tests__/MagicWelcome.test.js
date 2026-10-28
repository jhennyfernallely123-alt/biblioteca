import { enableAutoUnmount, mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { afterEach, describe, expect, it } from 'vitest';
import MagicWelcome from '../MagicWelcome.vue';

enableAutoUnmount(afterEach);

function renderWelcome() {
    return mount(MagicWelcome, {
        props: { nickname: 'Ana' },
        attachTo: document.body,
    });
}

describe('MagicWelcome', () => {
    it('muestra la bienvenida personalizada y enfoca el botón Entrar', async () => {
        const wrapper = renderWelcome();
        await nextTick();

        const dialog = wrapper.get('[role="dialog"]');
        const button = wrapper.get('[data-welcome-enter]');

        expect(dialog.attributes('aria-modal')).toBe('true');
        expect(dialog.attributes('aria-labelledby')).toBe('welcome-title');
        expect(dialog.text()).toContain('Bienvenida, Ana');
        expect(dialog.text()).toContain('Tu mundo mágico te espera.');
        expect(button.text()).toBe('Entrar');
        expect(document.activeElement).toBe(button.element);
    });

    it('emite close al pulsar Entrar', async () => {
        const wrapper = renderWelcome();
        await nextTick();

        await wrapper.get('[data-welcome-enter]').trigger('click');

        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('emite close al pulsar Escape', async () => {
        const wrapper = renderWelcome();
        await nextTick();

        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));

        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('mantiene el foco en Entrar al tabular', async () => {
        const wrapper = renderWelcome();
        await nextTick();

        const event = new KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true });
        window.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(true);
        expect(document.activeElement).toBe(wrapper.get('[data-welcome-enter]').element);
    });

    it('bloquea el scroll del fondo y lo restaura al desmontarse', async () => {
        const wrapper = renderWelcome();
        await nextTick();

        expect(document.body.style.overflow).toBe('hidden');

        wrapper.unmount();

        expect(document.body.style.overflow).toBe('');
    });
});
