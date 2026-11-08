import { readFileSync, readdirSync, statSync } from 'node:fs';
import { extname, join, sep } from 'node:path';
import { describe, expect, it } from 'vitest';

const roots = ['resources/css', 'resources/js'];
const skipped = new Set([
    join('resources', 'js', 'Pages', 'Welcome.vue'),
    join('resources', 'js', 'app.js'),
]);

const legacy =
    '(?:night-raised|night|panel-raised|panel|line|moon|mist|magic-strong|magic|arcane-strong|arcane|gold|orquidea)';
const legacyClass = new RegExp(
    `(?<![\\w-])(?:(?:[a-z0-9-]+):)*(?:ring-offset|ring|bg|text|border|from|via|to|fill|stroke|divide|outline|decoration|placeholder|file|shadow)-${legacy}(?![\\w-])`,
);
const legacyVar = new RegExp(`--color-${legacy}(?![\\w-])`);
const hex = /#[0-9A-Fa-f]{3,8}\b/;

function walk(directory) {
    return readdirSync(directory)
        .flatMap((name) => {
            const path = join(directory, name);

            if (statSync(path).isDirectory()) {
                return path.split(sep).includes('__tests__') ? [] : walk(path);
            }

            return [path];
        })
        .filter((path) => ['.vue', '.js', '.css'].includes(extname(path)));
}

const files = roots.flatMap(walk).filter((path) => !skipped.has(path));
const vueFiles = files.filter(
    (path) => path.endsWith('.vue') && !path.endsWith('CardMachine.vue'),
);

describe('sistema de temas', () => {
    it('no conserva tokens de la paleta anterior', () => {
        const offenders = files.filter((path) => {
            const content = readFileSync(path, 'utf8');

            return legacyClass.test(content) || legacyVar.test(content);
        });

        expect(offenders).toEqual([]);
    });

    it('no usa hex sueltos en componentes Vue', () => {
        const offenders = vueFiles.filter((path) => hex.test(readFileSync(path, 'utf8')));

        expect(offenders).toEqual([]);
    });

    it('declara el override del tema oscuro', () => {
        const css = readFileSync('resources/css/app.css', 'utf8');

        expect(css).toContain(":root[data-theme='dark']");
        expect(css).toContain('color-scheme: dark');
    });
});
