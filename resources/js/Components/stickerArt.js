export const stickers = {
    maquina_libro: {
        cut: 'M26 32c15-6 28-6 40 2 12-8 26-8 40-2v52c-15-6-28-6-40 2-12-8-26-8-40-2V32Z',
        art: `
            <ellipse cx="60" cy="98" rx="34" ry="6" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M28 34c14-6 26-6 38 2 12-8 24-8 38-2v50c-14-6-26-6-38 2-12-8-24-8-38-2V34Z" fill="var(--color-terracota)" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linejoin="round"/>
            <path d="M32 38c12-5 22-5 32 1 10-6 20-6 32-1v44c-12-5-22-5-32 1-10-6-20-6-32-1V38Z" fill="var(--color-pergamino)" stroke="var(--color-tinta)" stroke-width="2" stroke-linejoin="round"/>
            <path d="M60 39v44" stroke="var(--color-tinta)" stroke-width="2"/>
            <path d="M38 44c8-2 14-2 20 1M38 52c8-2 14-2 20 1M38 60c8-2 14-2 20 1M66 44c8-2 14-2 20 1M66 52c8-2 14-2 20 1M66 60c8-2 14-2 20 1" fill="none" stroke="var(--color-dorado)" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M88 24l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        `,
    },
    maquina_pluma: {
        cut: 'M30 94c15-23 32-46 55-60 5-3 9 0 8 6-8 25-25 45-47 57',
        art: `
            <ellipse cx="60" cy="98" rx="30" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M34 92c14-22 30-44 52-58 4-3 8 0 7 5-7 24-24 43-45 55" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linejoin="round"/>
            <path d="M86 36c-12 16-26 30-43 41" fill="none" stroke="var(--color-dorado)" stroke-width="2" stroke-linecap="round"/>
            <path d="M48 74l-8 18M58 64l-9 9M66 54l-9 8M74 46l-8 7" stroke="var(--color-tinta)" stroke-width="1.6" fill="none" stroke-linecap="round"/>
            <path d="M34 92c4 3 8 3 11 0" fill="none" stroke="var(--color-tinta)" stroke-width="2" stroke-linecap="round"/>
            <circle cx="30" cy="38" r="2.2" fill="var(--color-accent)"/>
            <path d="M88 24l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        `,
    },
    maquina_tintero: {
        cut: 'M38 50h44l-6 32a12 12 0 0 1-12 10H56a12 12 0 0 1-12-10l-6-32Z',
        art: `
            <ellipse cx="60" cy="98" rx="28" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M40 52h40l-5 30a10 10 0 0 1-10 9H55a10 10 0 0 1-10-9l-5-30Z" fill="var(--color-tertiary)" opacity=".7" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linejoin="round"/>
            <path d="M44 60h32l-4 22a8 8 0 0 1-8 7h-16a8 8 0 0 1-8-7l-4-22Z" fill="var(--color-tinta)" opacity=".8"/>
            <rect x="42" y="44" width="36" height="9" rx="3" fill="var(--color-madera)" stroke="var(--color-tinta)" stroke-width="2"/>
            <path d="M50 40c0-8 4-12 10-12s10 4 10 12" fill="none" stroke="var(--color-tinta)" stroke-width="2"/>
            <path d="M48 58c4-2 8-2 12 0" fill="none" stroke="var(--color-crema)" stroke-width="2" opacity=".6" stroke-linecap="round"/>
            <circle cx="60" cy="66" r="5" fill="var(--color-crema)" opacity=".5"/>
            <path d="M84 30l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        `,
    },
    maquina_taza: {
        cut: 'M32 46h42v18a16 16 0 0 1-16 16h-10a16 16 0 0 1-16-16V46Z M76 54h8a8 8 0 0 1 0 16h-8a8 8 0 0 1 0-16Z',
        art: `
            <ellipse cx="60" cy="98" rx="32" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M34 48h42v18a16 16 0 0 1-16 16H50a16 16 0 0 1-16-16V48Z" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linejoin="round"/>
            <path d="M76 54h7a8 8 0 0 1 0 16h-7" fill="none" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linecap="round"/>
            <path d="M34 48h42" stroke="var(--color-dorado)" stroke-width="3" stroke-linecap="round"/>
            <path d="M40 58h30v10a10 10 0 0 1-10 10H50a10 10 0 0 1-10-10v-10Z" fill="var(--color-terracota)" opacity=".5"/>
            <path d="M46 40c2-4 2-7 0-10M56 40c2-4 2-7 0-10M66 40c2-4 2-7 0-10" fill="none" stroke="var(--color-ink-muted)" stroke-width="1.8" opacity=".6" stroke-linecap="round"/>
            <path d="M28 88h58" stroke="var(--color-madera)" stroke-width="3" stroke-linecap="round"/>
        `,
    },
    maquina_gato: {
        cut: 'M36 52l-5-20 18 9c3-1 7-1 10 0l18-9-5 20c4 5 6 11 6 18 0 15-11 24-25 24S30 86 30 71c0-7 2-13 6-18Z',
        art: `
            <ellipse cx="60" cy="98" rx="30" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M38 54l-4-18 16 8c3-1 7-1 10 0l16-8-4 18c4 5 6 11 6 17 0 14-10 22-23 22S32 85 32 71c0-6 2-12 6-17Z" fill="var(--color-madera-clara)" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linejoin="round"/>
            <path d="M40 60c4-2 8-2 12 0M68 60c4-2 8-2 12 0" fill="none" stroke="var(--color-dorado)" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M48 66c2 2 5 2 7 0M65 66c2 2 5 2 7 0" fill="none" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linecap="round"/>
            <path d="M58 74l-3 5h6l-3-5Z" fill="var(--color-accent)" stroke="var(--color-tinta)" stroke-width="1.4" stroke-linejoin="round"/>
            <path d="M44 76l-10-3M46 80l-11 2M76 76l10-3M74 80l11 2" stroke="var(--color-tinta)" stroke-width="1.4" stroke-linecap="round"/>
            <path d="M44 54c4-2 8-2 12 0M64 54c4-2 8-2 12 0" fill="none" stroke="var(--color-dorado)" stroke-width="1.6" stroke-linecap="round"/>
        `,
    },
    maquina_luna: {
        cut: 'M72 16c-19 4-32 21-29 40 3 20 21 34 41 31-10 11-26 15-41 11C21 92 8 71 14 50 20 30 41 16 62 16c4 0 7 0 10 1Z',
        art: `
            <ellipse cx="60" cy="98" rx="28" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M70 18c-18 4-30 20-27 38 3 19 20 32 39 29-10 10-25 14-39 10C23 90 11 70 16 50 21 31 41 18 61 18c3 0 6 0 9 1Z" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linejoin="round"/>
            <path d="M62 30c-8 2-14 8-16 16 4-2 8-2 12 0" fill="var(--color-crema)" opacity=".5"/>
            <circle cx="42" cy="44" r="2" fill="var(--color-accent)"/>
            <circle cx="50" cy="58" r="1.6" fill="var(--color-accent)"/>
            <path d="M86 26l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        `,
    },
    maquina_vela: {
        cut: 'M46 44h28v40H46z M60 12c-6 5-6 13 0 22 6-9 6-17 0-22Z M40 84h40v12H40z',
        art: `
            <ellipse cx="60" cy="98" rx="28" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M48 46h24v38H48z" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linejoin="round"/>
            <path d="M48 52c4 3 8 3 12 0s8-3 12 0" fill="none" stroke="var(--color-dorado)" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M60 46c-6-5-6-13 0-22 6 9 6 17 0 22Z" fill="var(--color-accent)" stroke="var(--color-tinta)" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M60 34c-3-2-3-6 0-9 3 3 3 7 0 9Z" fill="var(--color-terracota)"/>
            <path d="M44 84h32v10H44z" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="2" stroke-linejoin="round"/>
            <path d="M52 54c2 2 4 4 4 6a4 4 0 0 1-8 0c0-2 2-4 4-6Z" fill="var(--color-terracota)"/>
            <path d="M84 34l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        `,
    },
    maquina_planta: {
        cut: 'M60 12c-12 4-20 14-20 26 0 10 8 18 20 18s20-8 20-18c0-12-8-22-20-26Z M56 50h8v30h-8z M34 76h52l-8 32H42l-8-32Z',
        art: `
            <ellipse cx="60" cy="98" rx="30" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M36 78h48l-7 28H43l-7-28Z" fill="var(--color-terracota)" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linejoin="round"/>
            <rect x="32" y="72" width="56" height="9" rx="4" fill="var(--color-madera-clara)" stroke="var(--color-tinta)" stroke-width="2" stroke-linejoin="round"/>
            <path d="M60 73V38" stroke="var(--color-bosque)" stroke-width="3" stroke-linecap="round"/>
            <path d="M60 58c-11-2-17-9-18-19 10 0 18 6 20 16" fill="var(--color-oliva)" stroke="var(--color-tinta)" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M60 50c11-2 17-9 18-19-10 0-18 6-20 16" fill="var(--color-salvia)" stroke="var(--color-tinta)" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M60 38c-6-2-10-7-11-13 7 0 12 4 14 10" fill="var(--color-oliva)" stroke="var(--color-tinta)" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M60 44c6-2 10-7 11-13-7 0-12 4-14 10" fill="var(--color-salvia)" stroke="var(--color-tinta)" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M40 82h40" stroke="var(--color-dorado)" stroke-width="2" stroke-linecap="round"/>
        `,
    },
    maquina_lupa: {
        cut: 'M52 24a28 28 0 1 0 0 56 28 28 0 0 0 0-56Z M70 70l20 20',
        art: `
            <ellipse cx="60" cy="98" rx="28" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <circle cx="52" cy="52" r="24" fill="var(--color-secondary)" opacity=".25" stroke="var(--color-dorado)" stroke-width="4"/>
            <circle cx="52" cy="52" r="24" fill="none" stroke="var(--color-tinta)" stroke-width="1.6"/>
            <path d="M70 70l20 20" stroke="var(--color-madera)" stroke-width="8" stroke-linecap="round"/>
            <path d="M70 70l20 20" stroke="var(--color-dorado)" stroke-width="3" stroke-linecap="round"/>
            <path d="M38 52c2-8 8-14 16-16" fill="none" stroke="var(--color-crema)" stroke-width="2" opacity=".7" stroke-linecap="round"/>
            <circle cx="46" cy="46" r="2" fill="var(--color-crema)" opacity=".6"/>
            <path d="M88 30l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        `,
    },
    maquina_llave: {
        cut: 'M26 60a16 16 0 1 0 32 0 16 16 0 0 0-32 0Z M56 60h32 M78 60v10 M88 60v8',
        art: `
            <ellipse cx="60" cy="98" rx="30" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <circle cx="42" cy="60" r="16" fill="none" stroke="var(--color-dorado)" stroke-width="6"/>
            <circle cx="42" cy="60" r="7" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2"/>
            <path d="M56 60h32M78 60v10M88 60v8" stroke="var(--color-dorado)" stroke-width="6" stroke-linecap="round"/>
            <path d="M56 60h32M78 60v10M88 60v8" stroke="var(--color-tinta)" stroke-width="2" stroke-linecap="round"/>
            <path d="M36 52c2-2 5-2 7 0" fill="none" stroke="var(--color-crema)" stroke-width="2" opacity=".7" stroke-linecap="round"/>
            <path d="M84 30l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        `,
    },
    maquina_gafas: {
        cut: 'M25 58a15 15 0 1 0 30 0 15 15 0 0 0-30 0Z M63 58a15 15 0 1 0 30 0 15 15 0 0 0-30 0Z M55 56c4-3 8-3 12 0',
        art: `
            <ellipse cx="60" cy="98" rx="32" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <circle cx="40" cy="58" r="15" fill="var(--color-secondary)" opacity=".22" stroke="var(--color-tinta)" stroke-width="3"/>
            <circle cx="78" cy="58" r="15" fill="var(--color-secondary)" opacity=".22" stroke="var(--color-tinta)" stroke-width="3"/>
            <path d="M55 56c4-3 8-3 12 0" fill="none" stroke="var(--color-tinta)" stroke-width="3" stroke-linecap="round"/>
            <path d="M25 54l-8-10M93 54l8-10" stroke="var(--color-tinta)" stroke-width="3" stroke-linecap="round"/>
            <path d="M34 52c3-3 8-3 11 0M72 52c3-3 8-3 11 0" fill="none" stroke="var(--color-crema)" stroke-width="2" opacity=".7" stroke-linecap="round"/>
            <path d="M88 30l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        `,
    },
    maquina_marcapaginas: {
        cut: 'M42 22h36v70l-18-12-18 12V22Z',
        art: `
            <ellipse cx="60" cy="98" rx="26" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M42 22h36v70l-18-12-18 12V22Z" fill="var(--color-terracota)" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linejoin="round"/>
            <path d="M42 22h36v8H42z" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M48 34h24M48 42h24M48 50h16" stroke="var(--color-crema)" stroke-width="2" opacity=".8" stroke-linecap="round"/>
            <path d="M54 62c4-4 8-4 12 0M54 72c4-4 8-4 12 0" fill="none" stroke="var(--color-crema)" stroke-width="2" opacity=".8" stroke-linecap="round"/>
            <path d="M36 34l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        `,
    },
    maquina_reloj: {
        cut: 'M60 30a34 34 0 1 0 0 68 34 34 0 0 0 0-68Z M60 26v-8',
        art: `
            <ellipse cx="60" cy="98" rx="28" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <circle cx="60" cy="64" r="28" fill="var(--color-crema)" stroke="var(--color-dorado)" stroke-width="4"/>
            <circle cx="60" cy="64" r="22" fill="none" stroke="var(--color-tinta)" stroke-width="1.6"/>
            <path d="M60 64V48M60 64l10 7" stroke="var(--color-tinta)" stroke-width="2.4" stroke-linecap="round"/>
            <circle cx="60" cy="64" r="2.4" fill="var(--color-tinta)"/>
            <circle cx="60" cy="26" r="6" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="1.6"/>
            <path d="M60 32v6" stroke="var(--color-tinta)" stroke-width="2" stroke-linecap="round"/>
            <path d="M84 34l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        `,
    },
    maquina_buho: {
        cut: 'M36 42c0-14 10-22 24-22s24 8 24 22v20c0 16-10 26-24 26S36 78 36 62V42Z',
        art: `
            <ellipse cx="60" cy="98" rx="30" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M36 42c0-14 10-22 24-22s24 8 24 22v20c0 16-10 26-24 26S36 78 36 62V42Z" fill="var(--color-oliva)" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linejoin="round"/>
            <circle cx="48" cy="50" r="8" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2"/>
            <circle cx="72" cy="50" r="8" fill="var(--color-crema)" stroke="var(--color-tinta)" stroke-width="2"/>
            <circle cx="48" cy="50" r="3" fill="var(--color-tinta)"/>
            <circle cx="72" cy="50" r="3" fill="var(--color-tinta)"/>
            <path d="M60 58l-6 8h12l-6-8Z" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M45 70c4 3 9 4 15 4s11-1 15-4" fill="none" stroke="var(--color-dorado)" stroke-width="2" stroke-linecap="round"/>
            <path d="M40 40c4-2 8-2 12 0M68 40c4-2 8-2 12 0" fill="none" stroke="var(--color-dorado)" stroke-width="1.6" stroke-linecap="round"/>
        `,
    },
    maquina_pocion: {
        cut: 'M48 30h24v12l10 16a20 20 0 0 1-17 30H55A20 20 0 0 1 38 58l10-16V30Z',
        art: `
            <ellipse cx="60" cy="98" rx="28" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M48 30h24v12l10 16a20 20 0 0 1-17 30H55A20 20 0 0 1 38 58l10-16V30Z" fill="var(--color-secondary)" opacity=".45" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linejoin="round"/>
            <path d="M42 62a18 18 0 0 0 36 0c0-7-8-9-18-9s-18 2-18 9Z" fill="var(--color-tertiary)" opacity=".75" stroke="var(--color-tinta)" stroke-width="2" stroke-linejoin="round"/>
            <rect x="46" y="20" width="28" height="10" rx="3" fill="var(--color-madera)" stroke="var(--color-tinta)" stroke-width="2" stroke-linejoin="round"/>
            <circle cx="52" cy="64" r="3" fill="var(--color-secondary)"/>
            <circle cx="66" cy="70" r="2.4" fill="var(--color-secondary)"/>
            <circle cx="60" cy="58" r="2" fill="var(--color-crema)" opacity=".7"/>
            <path d="M86 34l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        `,
    },
    maquina_pergamino: {
        cut: 'M36 28h48v56c0 6-5 10-10 10H36V28Z',
        art: `
            <ellipse cx="60" cy="98" rx="30" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M36 28h48v56c0 6-5 10-10 10H36V28Z" fill="var(--color-pergamino)" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linejoin="round"/>
            <path d="M36 28c-6 0-10 4-10 10v46h10" fill="none" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linejoin="round"/>
            <path d="M44 44h32M44 54h32M44 64h24" stroke="var(--color-dorado)" stroke-width="2" stroke-linecap="round"/>
            <path d="M82 84c6 0 10-4 10-10" fill="none" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linecap="round"/>
            <circle cx="72" cy="82" r="6" fill="var(--color-danger-strong)" stroke="var(--color-tinta)" stroke-width="1.6"/>
            <path d="M88 32l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        `,
    },
    maquina_maquina_escribir: {
        cut: 'M28 56h64v30a6 6 0 0 1-6 6H34a6 6 0 0 1-6-6V56Z M44 18h32v38H44z',
        art: `
            <ellipse cx="60" cy="98" rx="32" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <rect x="28" y="56" width="64" height="30" rx="6" fill="var(--color-tinta)" stroke="var(--color-tinta)" stroke-width="2" stroke-linejoin="round"/>
            <rect x="36" y="62" width="48" height="8" rx="2" fill="var(--color-crema)" opacity=".8"/>
            <path d="M38 86h44v10H38z" fill="var(--color-madera)" stroke="var(--color-tinta)" stroke-width="2" stroke-linejoin="round"/>
            <path d="M28 56h64" stroke="var(--color-dorado)" stroke-width="3" stroke-linecap="round"/>
            <path d="M60 56V30" stroke="var(--color-tinta)" stroke-width="2.4" stroke-linecap="round"/>
            <path d="M60 30c-8 0-14 4-16 10M60 30c8 0 14 4 16 10" fill="none" stroke="var(--color-dorado)" stroke-width="2" stroke-linecap="round"/>
            <rect x="44" y="18" width="32" height="14" rx="2" fill="var(--color-pergamino)" stroke="var(--color-tinta)" stroke-width="2"/>
            <path d="M84 36l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        `,
    },
    maquina_rosa: {
        cut: 'M60 30c11-8 23-1 21 10 9 3 10 14 2 19 3 10-8 16-16 11-6 9-19 5-20-5-12 0-16-12-9-19-5-9 5-19 15-17 2 1 4 1 7 1Z M60 56v36 M58 74c-10-2-16-8-18-16 9 0 16 5 20 13 M62 68c10-2 16-8 18-16-9 0-16 5-20 13',
        art: `
            <ellipse cx="60" cy="98" rx="28" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M42 92c8-4 16-4 24 0 6-3 12-3 18 0" fill="none" stroke="var(--color-bosque)" stroke-width="3" stroke-linecap="round"/>
            <path d="M60 86V56" stroke="var(--color-bosque)" stroke-width="4" stroke-linecap="round"/>
            <path d="M58 74c-10-2-16-8-18-16 9 0 16 5 20 13" fill="var(--color-salvia)" stroke="var(--color-tinta)" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M62 68c10-2 16-8 18-16-9 0-16 5-20 13" fill="var(--color-oliva)" stroke="var(--color-tinta)" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M60 30c11-8 23-1 21 10 9 3 10 14 2 19 3 10-8 16-16 11-6 9-19 5-20-5-12 0-16-12-9-19-5-9 5-19 15-17 2 1 4 1 7 1Z" fill="var(--color-danger)" stroke="var(--color-tinta)" stroke-width="2" stroke-linejoin="round"/>
            <path d="M50 38c4-6 12-7 17-3M72 42c5 3 6 9 2 13M64 56c-4 5-11 5-15 1M48 50c-4-3-4-8-1-12" fill="none" stroke="var(--color-danger-strong)" stroke-width="2" stroke-linecap="round"/>
            <circle cx="60" cy="46" r="7" fill="var(--color-danger-strong)" stroke="var(--color-tinta)" stroke-width="1.4"/>
            <path d="M60 42c2 2 4 4 4 6a4 4 0 0 1-8 0c0-2 2-4 4-6Z" fill="var(--color-danger)"/>
        `,
    },
    maquina_cuervo: {
        cut: 'M36 62c0-18 11-30 26-30 12 0 21 7 24 18 8 2 12 8 12 16-8-3-14-2-19 3-5 9-13 14-23 14-12 0-20-8-20-21Z',
        art: `
            <ellipse cx="60" cy="98" rx="32" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M36 62c0-18 11-30 26-30 12 0 21 7 24 18 8 2 12 8 12 16-8-3-14-2-19 3-5 9-13 14-23 14-12 0-20-8-20-21Z" fill="var(--color-tinta)" stroke="var(--color-tinta)" stroke-width="2" stroke-linejoin="round"/>
            <path d="M44 52c4-2 8-2 12 0" fill="none" stroke="var(--color-crema)" stroke-width="2" stroke-linecap="round"/>
            <circle cx="70" cy="44" r="2.2" fill="var(--color-crema)"/>
            <path d="M88 54l10 4-10 4v-8Z" fill="var(--color-dorado)" stroke="var(--color-tinta)" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M50 78l-8 10M58 80l-4 12M68 80l4 12M76 78l8 10" stroke="var(--color-tinta)" stroke-width="2" fill="none" stroke-linecap="round"/>
            <path d="M40 60c4 2 8 2 12 0M48 68c4 2 8-2 12 0" fill="none" stroke="var(--color-crema)" stroke-width="1.6" opacity=".6" stroke-linecap="round"/>
            <path d="M34 44l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
        `,
    },
    maquina_cristal: {
        cut: 'M60 18l22 18-8 48-14 16-14-16-8-48 22-18Z',
        art: `
            <ellipse cx="60" cy="98" rx="28" ry="5" fill="var(--color-ink-muted)" opacity=".12"/>
            <path d="M60 18l22 18-8 48-14 16-14-16-8-48 22-18Z" fill="var(--color-tertiary)" opacity=".55" stroke="var(--color-tinta)" stroke-width="2.2" stroke-linejoin="round"/>
            <path d="M60 18v82M38 36h44M42 54h36M46 68h28" stroke="var(--color-crema)" stroke-width="1.8" opacity=".6" stroke-linecap="round"/>
            <path d="M60 26l10 10-10 10-10-10 10-10Z" fill="var(--color-crema)" opacity=".7" stroke="var(--color-tinta)" stroke-width="1.4" stroke-linejoin="round"/>
            <path d="M86 30l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="var(--color-accent)"/>
            <circle cx="36" cy="40" r="2" fill="var(--color-accent)"/>
        `,
    },
};
