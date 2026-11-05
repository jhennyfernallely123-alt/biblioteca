<?php

return [
    'timezone' => env('CARDS_TIMEZONE', 'America/Bogota'),
    'daily_balls' => 8,
    'ink_per_duplicate' => 1,
    'allow_multiple_draws' => env('CARDS_ALLOW_MULTIPLE_DRAWS', false),

    'families' => [
        'empireo' => [
            'label' => 'Empíreo',
            'emblem' => 'dragon',
            'titles' => ['Alas de sangre', 'Alas de hierro', 'Alas de ónix', 'El día de la Trilla'],
            'authors' => ['Rebecca Yarros'],
        ],
        'mapa' => [
            'label' => 'El mapa de los anhelos',
            'emblem' => 'map',
            'titles' => ['El mapa de los anhelos'],
            'authors' => ['Alice Kellen'],
        ],
        'biblioteca' => [
            'label' => 'La biblioteca de la medianoche',
            'emblem' => 'moon',
            'titles' => ['La biblioteca de la medianoche'],
            'authors' => ['Matt Haig'],
        ],
        'perfectos' => [
            'label' => 'Perfectos mentirosos',
            'emblem' => 'mask',
            'titles' => ['Mentiras y secretos', 'Perfectos mentirosos'],
            'authors' => ['Álex Ramírez', 'Álex Mírez'],
        ],
    ],

    'cards' => [
        'violet' => ['family' => 'empireo', 'character' => 'Violet Sorrengail', 'monogram' => 'VS', 'emblem' => 'dragon'],
        'xaden' => ['family' => 'empireo', 'character' => 'Xaden Riorson', 'monogram' => 'XR', 'emblem' => 'shadow'],
        'tairn' => ['family' => 'empireo', 'character' => 'Tairn', 'monogram' => 'T', 'emblem' => 'wing'],
        'andarna' => ['family' => 'empireo', 'character' => 'Andarna', 'monogram' => 'A', 'emblem' => 'feather'],
        'rhiannon' => ['family' => 'empireo', 'character' => 'Rhiannon Matthias', 'monogram' => 'RM', 'emblem' => 'shield'],
        'liam' => ['family' => 'empireo', 'character' => 'Liam Mairi', 'monogram' => 'LM', 'emblem' => 'sword'],
        'dain' => ['family' => 'empireo', 'character' => 'Dain Aetos', 'monogram' => 'DA', 'emblem' => 'compass'],
        'imogen' => ['family' => 'empireo', 'character' => 'Imogen Cardulo', 'monogram' => 'IC', 'emblem' => 'flame'],
        'bodhi' => ['family' => 'empireo', 'character' => 'Bodhi Durran', 'monogram' => 'BD', 'emblem' => 'knot'],
        'grace' => ['family' => 'mapa', 'character' => 'Grace Peterson', 'monogram' => 'GP', 'emblem' => 'map'],
        'will' => ['family' => 'mapa', 'character' => 'Will Tucker', 'monogram' => 'WT', 'emblem' => 'lantern'],
        'lucy' => ['family' => 'mapa', 'character' => 'Lucy', 'monogram' => 'L', 'emblem' => 'star'],
        'nora' => ['family' => 'biblioteca', 'character' => 'Nora Seed', 'monogram' => 'NS', 'emblem' => 'moon'],
        'mrs_elm' => ['family' => 'biblioteca', 'character' => 'Mrs. Elm', 'monogram' => 'ME', 'emblem' => 'chess'],
        'voltaire' => ['family' => 'biblioteca', 'character' => 'Voltaire', 'monogram' => 'V', 'emblem' => 'cat'],
        'ash' => ['family' => 'biblioteca', 'character' => 'Ash', 'monogram' => 'A', 'emblem' => 'music'],
        'jude' => ['family' => 'perfectos', 'character' => 'Jude Derry', 'monogram' => 'JD', 'emblem' => 'mask'],
        'aegan' => ['family' => 'perfectos', 'character' => 'Aegan Cash', 'monogram' => 'AG', 'emblem' => 'crown'],
        'adrik' => ['family' => 'perfectos', 'character' => 'Adrik Cash', 'monogram' => 'AD', 'emblem' => 'key'],
        'alex' => ['family' => 'perfectos', 'character' => 'Alex Cash', 'monogram' => 'AL', 'emblem' => 'rose'],
    ],
];
