import { mount } from '@vue/test-utils';
import { nextTick, reactive } from 'vue';
import { describe, expect, it, vi } from 'vitest';
import Album from '../Album.vue';

const mocks = vi.hoisted(() => ({ page: null }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mocks.page,
}));

const stickers = Array.from({ length: 12 }, (_, i) => ({
    key: `sticker_${i}`,
    event_key: i < 4 ? 'feria_libro' : i < 8 ? 'noche_dragones' : 'invierno_cuento',
    theme: 'book',
    cost: 15,
    rarity: i < 4 ? 'comun' : i < 8 ? 'rara' : 'epica',
    owned: i < 2,
    acquired_at: i < 2 ? '2026-11-01T10:00:00+00:00' : null,
}));

const progress = { owned: 2, total: 12, by_rarity: { comun: 1, rara: 1, epica: 0 } };

const translations = {
    'ui.sticker_progress_label': 'Progreso de la colección',
    'ui.sticker_progress_title': 'Tu álbum de estampas',
    'ui.sticker_completed': 'Completado',
    'ui.sticker_all': 'Todas',
    'ui.sticker_locked': 'Bloqueado',
    'ui.sticker_number': '#:n',
    'ui.sticker_rarity': 'Rareza',
    'ui.sticker_acquired': 'Obtenida',
    'ui.sticker_got_it': 'Entendido',
    'ui.sticker_hint': 'Toca una pegatina para ver sus detalles',
    'ui.sticker_rarity_comun': 'Común',
    'ui.sticker_rarity_rara': 'Rara',
    'ui.sticker_rarity_epica': 'Épica',
};

const layoutStub = { template: '<div class="layout-stub"><slot /></div>' };

describe('Album', () => {
    beforeEach(() => {
        document.documentElement.lang = 'es';
        if (typeof HTMLDialogElement.prototype.showModal === 'undefined') {
            HTMLDialogElement.prototype.showModal = function () {};
            HTMLDialogElement.prototype.close = function () {};
        }
        mocks.page = reactive({ props: { locale: 'es', translations } });
    });

    function mountAlbum() {
        return mount(Album, {
            props: { stickers, progress },
            global: {
                stubs: {
                    AuthenticatedLayout: layoutStub,
                    Link: { template: '<a><slot /></a>' },
                },
            },
        });
    }

    it('renderiza 12 tarjetas; bloqueadas muestran nombre bloqueado y sin ilustración', () => {
        const wrapper = mountAlbum();
        expect(wrapper.findAll('.sticker-album__card')).toHaveLength(12);
        expect(wrapper.findAll('.sticker-album__card--locked')).toHaveLength(10);
        expect(wrapper.text()).toContain('?');
        expect(wrapper.findAll('.event-sticker__art')).toHaveLength(2);
    });

    it('filtra por evento', async () => {
        const wrapper = mountAlbum();
        wrapper.vm.selectEvent('noche_dragones');
        await nextTick();
        expect(wrapper.findAll('.sticker-album__card')).toHaveLength(4);
    });

    it('abre y cierra la ficha con rareza, número y fecha', async () => {
        const wrapper = mountAlbum();
        wrapper.findAll('.sticker-album__card')[0].trigger('click');
        await nextTick();
        expect(wrapper.findComponent({ name: 'Modal' }).props('show')).toBe(true);
        expect(wrapper.text()).toContain('Común');
        expect(wrapper.text()).toContain('#01');
        expect(wrapper.text()).toContain('2026');
        expect(wrapper.text()).toContain('nov');
        wrapper.findComponent({ name: 'Modal' }).vm.$emit('close');
        await nextTick();
        expect(wrapper.findComponent({ name: 'Modal' }).props('show')).toBe(false);
    });
});
