import { describe, expect, it } from 'vitest';
import { countdownParts } from './cards';

describe('countdownParts', () => {
    it('separa horas y minutos', () => {
        expect(countdownParts('2026-11-04T05:00:00Z', new Date('2026-11-03T20:00:00Z')))
            .toEqual({ hours: 9, minutes: 0 });
    });

    it('devuelve solo minutos si falta menos de una hora', () => {
        expect(countdownParts('2026-11-04T05:00:00Z', new Date('2026-11-04T04:30:00Z')))
            .toEqual({ hours: 0, minutes: 30 });
    });

    it('redondea hacia arriba al minuto siguiente', () => {
        expect(countdownParts('2026-11-04T05:00:00Z', new Date('2026-11-04T04:59:30Z')))
            .toEqual({ hours: 0, minutes: 1 });
    });

    it('devuelve cero si ya pasó', () => {
        expect(countdownParts('2026-11-04T05:00:00Z', new Date('2026-11-04T06:00:00Z')))
            .toEqual({ hours: 0, minutes: 0 });
    });
});
