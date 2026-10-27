import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import LanguageSelect from '../LanguageSelect.vue';

describe('LanguageSelect', () => {
    it('filtra idiomas y muestra los revisados', async () => {
        const wrapper = mount(LanguageSelect, {
            props: {
                modelValue: 'es',
                languages: ['es', 'en', 'af'],
                curated: ['es', 'en'],
                locale: 'en',
            },
        });

        expect(wrapper.text()).toContain('Revisado');
        expect(wrapper.text()).toContain('Automático');

        await wrapper.find('input').setValue('engl');
        expect(wrapper.text()).toContain('English');
    });
});
