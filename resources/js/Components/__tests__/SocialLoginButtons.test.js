import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import SocialLoginButtons from '../SocialLoginButtons.vue';

describe('SocialLoginButtons', () => {
    it('muestra los proveedores deshabilitados sin credenciales', () => {
        const wrapper = mount(SocialLoginButtons, {
            props: { providers: { google: false, facebook: false } },
        });

        const buttons = wrapper.findAll('button');

        expect(buttons).toHaveLength(2);
        buttons.forEach((button) => {
            expect(button.attributes('disabled')).toBeDefined();
            expect(button.attributes('aria-disabled')).toBe('true');
        });
        expect(wrapper.text()).toContain('Próximamente');
    });

    it('crea enlaces reales cuando hay credenciales', () => {
        const wrapper = mount(SocialLoginButtons, {
            props: { providers: { google: true, facebook: true } },
        });

        expect(wrapper.find('a[href="/auth/google/redirect"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/auth/facebook/redirect"]').exists()).toBe(true);
        expect(wrapper.find('button[disabled]').exists()).toBe(false);
    });
});
