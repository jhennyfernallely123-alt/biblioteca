import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
}));

vi.mock('axios', () => ({ default: mocks }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { locale: 'es' } }),
}));

import ReadingLightbox from '../ReadingLightbox.vue';

const book = {
    id: 1,
    title: 'Rayuela',
    author: 'Julio Cortázar',
    cover_path: null,
    total_pages: 200,
    current_page: 84,
    progress: 42,
    minutes: 210,
    status: 'reading',
};

const sessions = [
    { id: 5, read_on: '2026-11-03', minutes: 60, page: 84 },
    { id: 4, read_on: '2026-11-01', minutes: 30, page: 50 },
];

function mountLightbox() {
    return mount(ReadingLightbox, {
        props: { book },
        global: { stubs: { teleport: true } },
    });
}

describe('ReadingLightbox', () => {
    beforeEach(() => {
        mocks.get.mockResolvedValue({ data: { book, sessions } });
        mocks.post.mockReset();
        mocks.patch.mockReset();
        mocks.delete.mockReset();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('muestra el progreso, el tiempo y el historial del libro', async () => {
        const wrapper = mountLightbox();
        await flushPromises();

        expect(wrapper.text()).toContain('42%');
        expect(wrapper.text()).toContain('Página 84 de 200');
        expect(wrapper.text()).toContain('3 h 30 min');
        expect(wrapper.findAll('li')).toHaveLength(2);
        expect(mocks.get).toHaveBeenCalledWith('/books/1/reading');
    });

    it('guarda una sesión con los minutos cronometrados al pausar', async () => {
        const now = vi.spyOn(Date, 'now').mockReturnValue(1_000_000);

        mocks.post.mockResolvedValue({
            data: {
                book: { ...book, minutes: 211 },
                session: { id: 6, read_on: '2026-11-03', minutes: 1, page: 84 },
            },
        });

        const wrapper = mountLightbox();
        await flushPromises();

        await wrapper.find('button[aria-label="Empezar"]').trigger('click');
        now.mockReturnValue(1_000_000 + 65_000);
        await wrapper.find('button[aria-label="Pausar y guardar"]').trigger('click');
        await flushPromises();

        expect(mocks.post).toHaveBeenCalledWith('/books/1/reading-sessions', {
            minutes: 1,
            page: 84,
        });
        expect(wrapper.findAll('li')).toHaveLength(3);
    });

    it('no guarda una sesión demasiado corta', async () => {
        const now = vi.spyOn(Date, 'now').mockReturnValue(1_000_000);
        const wrapper = mountLightbox();
        await flushPromises();

        await wrapper.find('button[aria-label="Empezar"]').trigger('click');
        now.mockReturnValue(1_000_000 + 4_000);
        await wrapper.find('button[aria-label="Pausar y guardar"]').trigger('click');
        await flushPromises();

        expect(mocks.post).not.toHaveBeenCalled();
        expect(wrapper.text()).toContain('demasiado corta');
    });

    it('guarda las páginas totales y la página actual', async () => {
        mocks.patch.mockResolvedValue({
            data: { book: { ...book, current_page: 100, progress: 50 } },
        });

        const wrapper = mountLightbox();
        await flushPromises();

        const inputs = wrapper.findAll('input[type="number"]');
        await inputs[0].setValue(100);
        await inputs[1].setValue(200);

        const button = wrapper.findAll('button').find((item) => item.text() === 'Guardar progreso');
        await button.trigger('click');
        await flushPromises();

        expect(mocks.patch).toHaveBeenCalledWith('/books/1/reading', {
            total_pages: 200,
            current_page: 100,
        });
    });

    it('elimina una sesión del historial', async () => {
        vi.spyOn(window, 'confirm').mockReturnValue(true);
        mocks.delete.mockResolvedValue({ data: { book: { ...book, minutes: 150 } } });

        const wrapper = mountLightbox();
        await flushPromises();

        await wrapper.findAll('button[aria-label="Eliminar sesión"]')[0].trigger('click');
        await flushPromises();

        expect(mocks.delete).toHaveBeenCalledWith('/reading-sessions/5');
        expect(wrapper.findAll('li')).toHaveLength(1);
    });

    it('cierra el diálogo al pulsar el botón de cerrar', async () => {
        const wrapper = mountLightbox();
        await flushPromises();

        await wrapper.find('button[aria-label="Cerrar"]').trigger('click');

        expect(wrapper.emitted('close')).toHaveLength(1);
    });
});
