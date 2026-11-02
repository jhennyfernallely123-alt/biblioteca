import { describe, expect, it } from 'vitest';
import { currentSeason, seasonForMonth } from './seasons';

describe('seasons', () => {
    it('asigna una temática a cada mes', () => {
        const months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

        expect(months.map(seasonForMonth)).toEqual([
            'winter',
            'valentine',
            'spring',
            'rain',
            'flowers',
            'summer',
            'sun',
            'sunflower',
            'harvest',
            'halloween',
            'autumn',
            'christmas',
        ]);
    });

    it('usa Halloween en octubre, otoño en noviembre y Navidad en diciembre', () => {
        expect(seasonForMonth(10)).toBe('halloween');
        expect(seasonForMonth(11)).toBe('autumn');
        expect(seasonForMonth(12)).toBe('christmas');
    });

    it('calcula la temática desde una fecha', () => {
        expect(currentSeason(new Date(2026, 9, 31))).toBe('halloween');
        expect(currentSeason(new Date(2026, 10, 2))).toBe('autumn');
        expect(currentSeason(new Date(2026, 11, 1))).toBe('christmas');
    });
});
