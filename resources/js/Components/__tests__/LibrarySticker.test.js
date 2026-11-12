import { enableAutoUnmount, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import LibrarySticker from '../LibrarySticker.vue';
import { stickers } from '../stickerArt.js';

enableAutoUnmount(afterEach);

const keys = Object.keys(stickers);

describe('LibrarySticker', () => {
    it('renderiza los 20 artes y siluetas de la colección', () => {
        for (const key of keys) {
            const wrapper = mount(LibrarySticker, { props: { sticker: key } });

            expect(wrapper.find('.library-sticker__art').exists()).toBe(true);
            expect(wrapper.find('.library-sticker__cut').exists()).toBe(true);
            expect(wrapper.find('.library-sticker__cut').attributes('d')).toBe(stickers[key].cut);
        }
    });

    it('tiene una silueta propia para cada sticker', () => {
        const cuts = new Set(keys.map((key) => stickers[key].cut));

        expect(cuts.size).toBe(keys.length);
    });

    it('el estado bloqueado usa la misma silueta y muestra el interrogante', () => {
        const wrapper = mount(LibrarySticker, {
            props: { sticker: 'maquina_libro', locked: true },
        });

        expect(wrapper.find('.library-sticker__locked').exists()).toBe(true);
        expect(wrapper.find('.library-sticker__art').exists()).toBe(false);
        expect(wrapper.find('.library-sticker__locked path').attributes('d')).toBe(stickers.maquina_libro.cut);
    });
});
