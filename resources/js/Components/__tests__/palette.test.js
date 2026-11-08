import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const css = readFileSync(resolve('resources/css/app.css'), 'utf8');
const textures = readFileSync(resolve('resources/css/textures.css'), 'utf8');

const block = (selector) => {
    const start = css.indexOf(selector);

    return css.slice(start, css.indexOf('}', start));
};

const token = (source, name) =>
    source.match(new RegExp(`--color-${name}:\\s*(#[0-9A-Fa-f]{6})`))?.[1];

const light = block('@theme');
const dark = block(":root[data-theme='dark']");

const expectedLight = {
    page: '#FDF7F0',
    surface: '#FFFFFF',
    'surface-raised': '#FFF8F1',
    'surface-sunken': '#F6EDE3',
    ink: '#3B3630',
    'ink-muted': '#7A7068',
    'ink-soft': '#9A9088',
    heading: '#3B3630',
    'on-accent': '#FFFFFF',
    edge: '#E8D5C4',
    'edge-strong': '#D6BEA8',
    accent: '#DB2777',
    'accent-strong': '#BE185D',
    'accent-soft': '#FDE2E4',
    secondary: '#0F766E',
    'secondary-strong': '#115E59',
    tertiary: '#7C3AED',
    'tertiary-strong': '#6D28D9',
    danger: '#E11D48',
    'danger-strong': '#BE123C',
    success: '#047857',
    warning: '#B45309',
    ring: '#7C3AED',
    scrim: '#1C1A24',
};

const expectedDark = {
    page: '#12101F',
    surface: '#211D3A',
    'surface-raised': '#1A1730',
    'surface-sunken': '#191632',
    ink: '#F1ECFF',
    'ink-muted': '#B7AED6',
    'ink-soft': '#8C82B8',
    heading: '#E8C887',
    'on-accent': '#FFFFFF',
    edge: '#3A3363',
    'edge-strong': '#4A4278',
    accent: '#E8C887',
    'accent-strong': '#D4B271',
    'accent-soft': '#3A3350',
    secondary: '#74D8C9',
    'secondary-strong': '#5FC7B8',
    tertiary: '#7C5CE0',
    'tertiary-strong': '#6A4AC9',
    danger: '#F2A0B0',
    'danger-strong': '#A8455C',
    success: '#8FE3B8',
    warning: '#E8C887',
    ring: '#74D8C9',
    scrim: '#0A0814',
};

const physical = {
    bosque: '#2F4A3C',
    salvia: '#7A9A7E',
    oliva: '#556B4F',
    'oliva-hover': '#465A41',
    madera: '#6B4F3A',
    'madera-clara': '#8B6B4F',
    pergamino: '#F3E9D2',
    crema: '#FAF4E6',
    tinta: '#2B2620',
    terracota: '#A9714B',
    dorado: '#C2A878',
    malva: '#9E61AB',
    laton: '#E8C887',
};

describe('paleta de la aplicación', () => {
    it('define el tema claro por defecto', () => {
        for (const [name, value] of Object.entries(expectedLight)) {
            expect(token(light, name), name).toBe(value);
        }
    });

    it('sobrescribe la paleta en el tema oscuro', () => {
        for (const [name, value] of Object.entries(expectedDark)) {
            expect(token(dark, name), name).toBe(value);
        }
    });

    it('conserva los colores físicos del mueble', () => {
        for (const [name, value] of Object.entries(physical)) {
            expect(token(light, name), name).toBe(value);
        }
    });

    it('deriva las sombras del token scrim', () => {
        expect(css).toMatch(/--shadow-soft:[^;]*var\(--color-scrim\)/);
        expect(css).toMatch(/--shadow-card:[^;]*var\(--color-scrim\)/);
        expect(css).toMatch(/--shadow-cover:[^;]*var\(--color-scrim\)/);
        expect(css).toMatch(/--shadow-modal:[^;]*var\(--color-scrim\)/);
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
