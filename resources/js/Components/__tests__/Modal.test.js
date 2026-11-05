import { enableAutoUnmount, mount } from '@vue/test-utils';
import { afterEach, beforeAll, describe, expect, it } from 'vitest';
import Modal from '../Modal.vue';

beforeAll(() => {
    HTMLDialogElement.prototype.showModal = function () {
        this.setAttribute('open', '');
    };
    HTMLDialogElement.prototype.close = function () {
        this.removeAttribute('open');
    };
});

enableAutoUnmount(afterEach);

describe('Modal', () => {
    it('abre el diálogo nativo y muestra el contenido', async () => {
        const wrapper = mount(Modal, {
            props: { show: false },
            slots: { default: '<p>Contenido</p>' },
        });

        expect(wrapper.find('p').exists()).toBe(false);

        await wrapper.setProps({ show: true });

        expect(wrapper.get('dialog').attributes('open')).toBeDefined();
        expect(wrapper.get('p').text()).toBe('Contenido');
    });

    it('pinta el panel por encima del velo oscuro', async () => {
        const wrapper = mount(Modal, {
            props: { show: false },
            slots: { default: '<p>Contenido</p>' },
        });

        await wrapper.setProps({ show: true });

        const panel = wrapper.get('.relative.z-10');

        expect(panel.classes()).toContain('relative');
        expect(panel.classes()).toContain('z-10');
        expect(panel.text()).toBe('Contenido');
    });

    it('cierra con Escape cuando está abierto', async () => {
        const wrapper = mount(Modal, {
            props: { show: true },
            slots: { default: '<p>Contenido</p>' },
        });

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));

        expect(wrapper.emitted('close')).toEqual([[]]);
    });
});
