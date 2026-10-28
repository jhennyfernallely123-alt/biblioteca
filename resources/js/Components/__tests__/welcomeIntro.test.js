import { beforeEach, describe, expect, it } from 'vitest';
import { resetWelcome, shouldShowWelcome } from '../welcomeIntro';

describe('welcomeIntro', () => {
    beforeEach(() => {
        resetWelcome();
    });

    it('muestra la intro solo una vez por carga de la aplicación', () => {
        expect(shouldShowWelcome()).toBe(true);
        expect(shouldShowWelcome()).toBe(false);
    });

    it('vuelve a mostrarla después de reiniciar el estado', () => {
        expect(shouldShowWelcome()).toBe(true);

        resetWelcome();

        expect(shouldShowWelcome()).toBe(true);
    });
});
