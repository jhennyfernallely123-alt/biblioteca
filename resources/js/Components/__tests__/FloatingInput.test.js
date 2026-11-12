import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import FloatingInput from '../FloatingInput.vue';

describe('FloatingInput', () => {
    it('muestra la etiqueta flotante y el placeholder', () => {
        const wrapper = mount(FloatingInput, {
            props: { label: 'Nombre', placeholder: 'Escribe aquí...' },
        });

        expect(wrapper.get('label').text()).toBe('Nombre');
        expect(wrapper.get('input').attributes('placeholder')).toBe('Escribe aquí...');
        expect(wrapper.get('label').attributes('for')).toBe(wrapper.get('input').attributes('id'));
    });

    it('actualiza el modelo al escribir', async () => {
        const wrapper = mount(FloatingInput, {
            props: { label: 'Nombre', modelValue: '' },
        });

        await wrapper.get('input').setValue('Jhenny');

        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['Jhenny']);
    });

    it('aplica el estado de error', () => {
        const wrapper = mount(FloatingInput, {
            props: { label: 'Nombre', error: 'El nombre es obligatorio.' },
        });

        expect(wrapper.get('input').classes()).toContain('border-danger');
        expect(wrapper.get('label').classes()).toContain('text-danger');
    });
});
