import { mount } from '@vue/test-utils';
import { h } from 'vue';
import { describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    post: vi.fn(),
    page: { props: { flash: {} } },
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: {
        props: ['href'],
        inheritAttrs: false,
        setup(props, { attrs, slots }) {
            return () => h('a', { href: props.href, ...attrs }, slots.default?.());
        },
    },
    usePage: () => mocks.page,
    router: { post: vi.fn() },
    useForm: () => ({
        nickname: '',
        processing: false,
        errors: {},
        post: mocks.post,
    }),
}));

import CompleteNickname from '../CompleteNickname.vue';

describe('CompleteNickname', () => {
    it('muestra el campo de apodo y el botón continuar', () => {
        const wrapper = mount(CompleteNickname);

        expect(wrapper.text()).toContain('Elige tu apodo');
        expect(wrapper.find('#nickname').exists()).toBe(true);
        expect(wrapper.text()).toContain('Continuar');
    });
});
