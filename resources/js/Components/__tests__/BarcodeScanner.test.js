import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@zxing/browser', () => ({
    BrowserMultiFormatReader: class {
        async decodeFromVideoDevice(_device, _video, callback) {
            callback({ getText: () => '9788437604947' });
            return { stop: vi.fn() };
        }
    },
}));

import BarcodeScanner from '../BarcodeScanner.vue';

describe('BarcodeScanner', () => {
    it('emite el código detectado', async () => {
        const wrapper = mount(BarcodeScanner, { attachTo: document.body });
        await new Promise((resolve) => setTimeout(resolve, 0));
        expect(wrapper.emitted('detected')[0]).toEqual(['9788437604947']);
        wrapper.unmount();
    });
});
