import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import PasswordInput from '../PasswordInput.vue';

describe('PasswordInput', () => {
    it('alterna entre contraseña visible y oculta sin perder el valor', async () => {
        const wrapper = mount(PasswordInput, {
            props: { modelValue: 'secreto', id: 'password' },
        });

        expect(wrapper.get('input').attributes('type')).toBe('password');
        expect(wrapper.get('input').element.value).toBe('secreto');

        await wrapper.get('button').trigger('click');

        expect(wrapper.get('input').attributes('type')).toBe('text');
        expect(wrapper.get('input').element.value).toBe('secreto');
        expect(wrapper.get('button').attributes('aria-label')).toBe('Ocultar contraseña');
    });
});
