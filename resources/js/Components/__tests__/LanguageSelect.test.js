import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import LanguageSelect from '../LanguageSelect.vue';

describe('LanguageSelect', () => {
    it('muestra los idiomas revisados y emite la selección', async () => {
        const wrapper = mount(LanguageSelect, {
            props: {
                modelValue: 'es',
                languages: ['es', 'en', 'af'],
                curated: ['es', 'en'],
                locale: 'en',
            },
        });

        expect(wrapper.find('input').exists()).toBe(false);
        expect(wrapper.text()).toContain('Revisado');
        expect(wrapper.text()).toContain('Automático');

        await wrapper.findAll('button')[0].trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([['en']]);
    });
});
