<?php

return [
    'enabled' => env('EVENTS_ENABLED', true),
    'epoch' => '2026-11-05',
    'timezone' => env('CARDS_TIMEZONE', 'America/Bogota'),

    'tasks' => [
        'draw' => ['ink' => 2],
        'read' => ['ink' => 3],
        'add_book' => ['ink' => 3],
        'album' => ['ink' => 1],
    ],

    'catalog' => [
        [
            'key' => 'feria_libro',
            'name' => 'Feria del libro antiguo',
            'theme' => 'book',
            'days' => 3,
            'tasks' => ['draw', 'read', 'album'],
            'stickers' => [
                ['key' => 'feria_pluma', 'name' => 'Pluma de la feria', 'cost' => 15, 'emblem' => 'feather', 'rarity' => 'comun'],
                ['key' => 'feria_lampara', 'name' => 'Lámpara de la feria', 'cost' => 25, 'emblem' => 'lantern', 'rarity' => 'epica'],
            ],
            'cards' => [
                ['key' => 'feria_carta', 'name' => 'Carta del bibliotecario', 'cost' => 60, 'initial' => 'F'],
            ],
        ],
        [
            'key' => 'noche_dragones',
            'name' => 'Noche de dragones',
            'theme' => 'dragon',
            'days' => 2,
            'tasks' => ['draw', 'add_book', 'read'],
            'stickers' => [
                ['key' => 'dragones_escama', 'name' => 'Escama de dragón', 'cost' => 18, 'emblem' => 'dragon', 'rarity' => 'rara'],
                ['key' => 'dragones_alas', 'name' => 'Ala nocturna', 'cost' => 22, 'emblem' => 'wing', 'rarity' => 'rara'],
            ],
            'cards' => [
                ['key' => 'dragones_carta', 'name' => 'Carta del jinete', 'cost' => 70, 'initial' => 'D'],
            ],
        ],
        [
            'key' => 'invierno_cuento',
            'name' => 'Cuentos de invierno',
            'theme' => 'christmas',
            'days' => 4,
            'tasks' => ['read', 'draw', 'album'],
            'stickers' => [
                ['key' => 'invierno_copo', 'name' => 'Copo de cuento', 'cost' => 15, 'emblem' => 'star', 'rarity' => 'comun'],
                ['key' => 'invierno_campana', 'name' => 'Campana nevada', 'cost' => 24, 'emblem' => 'bell', 'rarity' => 'rara'],
            ],
            'cards' => [
                ['key' => 'invierno_carta', 'name' => 'Carta del abuelo', 'cost' => 55, 'initial' => 'I'],
            ],
        ],
        [
            'key' => 'enamorados',
            'name' => 'Cartas de enamorados',
            'theme' => 'valentine',
            'days' => 2,
            'tasks' => ['draw', 'read', 'add_book'],
            'stickers' => [
                ['key' => 'enamorados_corazon', 'name' => 'Corazón sellado', 'cost' => 16, 'emblem' => 'heart', 'rarity' => 'comun'],
                ['key' => 'enamorados_rosa', 'name' => 'Rosa entre páginas', 'cost' => 25, 'emblem' => 'rose', 'rarity' => 'epica'],
            ],
            'cards' => [
                ['key' => 'enamorados_carta', 'name' => 'Carta sin remitente', 'cost' => 80, 'initial' => 'E'],
            ],
        ],
        [
            'key' => 'noche_brujas',
            'name' => 'La noche de las brujas',
            'theme' => 'halloween',
            'days' => 3,
            'tasks' => ['draw', 'album', 'read'],
            'stickers' => [
                ['key' => 'brujas_calavera', 'name' => 'Calavera lectora', 'cost' => 17, 'emblem' => 'skull', 'rarity' => 'rara'],
                ['key' => 'brujas_pocion', 'name' => 'Poción de tinta', 'cost' => 21, 'emblem' => 'flame', 'rarity' => 'rara'],
            ],
            'cards' => [
                ['key' => 'brujas_carta', 'name' => 'Carta embrujada', 'cost' => 65, 'initial' => 'B'],
            ],
        ],
        [
            'key' => 'flores_feria',
            'name' => 'Feria de las flores',
            'theme' => 'flowers',
            'days' => 2,
            'tasks' => ['add_book', 'read', 'draw'],
            'stickers' => [
                ['key' => 'flores_ramo', 'name' => 'Ramo prensado', 'cost' => 15, 'emblem' => 'rose', 'rarity' => 'comun'],
                ['key' => 'flores_planta', 'name' => 'Planta de biblioteca', 'cost' => 20, 'emblem' => 'leaf', 'rarity' => 'rara'],
            ],
            'cards' => [
                ['key' => 'flores_carta', 'name' => 'Carta primaveral', 'cost' => 50, 'initial' => 'P'],
            ],
        ],
    ],
];
