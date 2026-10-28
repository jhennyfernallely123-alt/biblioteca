import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const css = readFileSync(resolve('resources/css/app.css'), 'utf8');
const textures = readFileSync(resolve('resources/css/textures.css'), 'utf8');

const token = (name) => css.match(new RegExp(`--color-${name}:\\s*(#[0-9A-Fa-f]{6})`))?.[1];

describe('paleta noche arcana', () => {
    it('define los tokens nuevos', () => {
        expect(token('night')).toBe('#12101F');
        expect(token('night-raised')).toBe('#1A1730');
        expect(token('panel')).toBe('#211D3A');
        expect(token('panel-raised')).toBe('#2B2550');
        expect(token('line')).toBe('#3A3363');
        expect(token('moon')).toBe('#F1ECFF');
        expect(token('mist')).toBe('#B7AED6');
        expect(token('arcane')).toBe('#7C5CE0');
        expect(token('arcane-strong')).toBe('#6A4AC9');
        expect(token('magic')).toBe('#74D8C9');
        expect(token('gold')).toBe('#E8C887');
    });

    it('conserva los tokens del mueble', () => {
        expect(token('bosque')).toBe('#2F4A3C');
        expect(token('oliva')).toBe('#556B4F');
        expect(token('madera')).toBe('#6B4F3A');
        expect(token('madera-clara')).toBe('#8B6B4F');
        expect(token('crema')).toBe('#FAF4E6');
        expect(token('tinta')).toBe('#2B2620');
        expect(token('terracota')).toBe('#A9714B');
        expect(token('dorado')).toBe('#C2A878');
        expect(token('pergamino')).toBe('#F3E9D2');
    });

    it('mantiene la madera en la balda y añade el cielo arcano', () => {
        expect(textures).toContain('var(--color-madera-clara)');
        expect(textures).toContain('.arcane-sky');
    });

    it('anima las estrellas y respeta el movimiento reducido', () => {
        expect(textures).toContain('.arcane-stars');
        expect(textures).toContain('@keyframes arcane-drift-a');
        expect(textures).toContain('@keyframes arcane-twinkle-a');
        expect(textures).toMatch(/prefers-reduced-motion: reduce/);
    });
});
