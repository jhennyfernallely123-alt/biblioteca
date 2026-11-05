# Diseño: el bombo de las cartas (fase 1)

**Fecha:** 2026-11-03
**Estado:** aprobado por la usuaria (20 cartas, 8 idiomas)
**Fases:** 1 de 3. Esta fase entrega el bombo, el álbum y la tinta. Los eventos
rotativos y los mini-juegos se diseñan en documentos aparte.

## Objetivo

Añadir un módulo **Cartas** a la biblioteca: una vez al día, la usuaria elige una
balota misteriosa de un bombo, gira la manivela y recibe una carta larga escrita en
primera persona por un personaje real de uno de sus libros. Las cartas se guardan en
un álbum; las repetidas se convierten en tinta, que en la fase 2 servirá para las
cartas de evento.

## Decisiones de la usuaria

- Los autores de las cartas son **personajes reales de libros conocidos**.
- Solo entran libros que tengan personajes en el catálogo; si no hay ninguno, se
  muestra un estado vacío que invita a añadir libros.
- Mecánica diaria de **máquina de balotas**: una tirada por día.
- Las 8 balotas son **misteriosas**: muestran el emblema de la familia de libros,
  pero no el personaje. La sorpresa se revela al abrir la carta.
- Colección en **álbum** + eventos de temporada (fase 2).
- Estética de **sobre, lacre, monograma y emblema**; sin retratos ni imágenes
  externas.
- Nuevo destino **Cartas** en el menú de escritorio y en el menú móvil.
- Catálogo limitado a los libros que la usuaria ya tiene.
- Los repetidos dan **1 de tinta**.
- Cartas **escritas a mano en los 8 idiomas revisados**, no traducidas
  automáticamente.
- Cartas **cortas**: unos 120 palabras (rango verificado 120–160), carta completa.
- **20 cartas** en la primera entrega.
- Eventos: cadena continua de eventos cortos (1–4 días), con actividad diaria y
  coleccionables distintos (cartas, pegatinas, álbum). Fase 2.
- Actividades de evento: mezcla de acciones de la app y mini-juegos. Fase 3.

## Alcance

- Nueva pantalla `/cards` (`Cards/Index`) con el bombo, el sobre, la carta y el
  álbum.
- Nueva entrada **Cartas** en la navegación.
- Catálogo de 20 cartas en 4 familias, con 8 traducciones largas por carta.
- Tirada diaria determinista, una por usuaria y día, validada en el servidor.
- Álbum por usuaria, copias y contador de tinta.
- Migraciones, modelos, servicios, controladores, rutas, textos de interfaz,
  pruebas y documentación visual.

## Fuera de alcance (fase 1)

- Eventos rotativos, pegatinas, actividades diarias y gasto de tinta.
- Mini-juegos.
- Intercambio de cartas entre usuarias.
- Cartas de libros que no estén en la biblioteca.
- Retratos, imágenes externas o subida de ilustraciones.
- Notificaciones push o correo recordatorio.
- Cambiar el idioma de una carta ya abierta sin recargar la página.
- Historial visible de tiradas.

## Familias y catálogo

Cada familia agrupa uno o varios libros y comparte un pool de personajes. Tener
cualquier libro de la familia desbloquea todas sus cartas.

| Familia | Libros que la activan | Cartas | Emblema de familia |
|---|---|---|---|
| `empireo` | Alas de sangre, Alas de hierro, Alas de ónix / onix, El día de la Trilla | 9 | `dragon` |
| `mapa` | El mapa de los anhelos | 3 | `map` |
| `biblioteca` | La biblioteca de la medianoche | 4 | `moon` |
| `perfectos` | Mentiras y secretos, Perfectos mentirosos | 4 | `mask` |

El emblema de familia es el que llevan las balotas y el sobre cerrado; no revela
el personaje. El emblema de la carta es el del personaje y solo se ve al abrirla
y en el álbum.

### Las 20 cartas

| Clave | Personaje | Familia | Monograma | Emblema |
|---|---|---|---|---|
| `violet` | Violet Sorrengail | `empireo` | VS | `dragon` |
| `xaden` | Xaden Riorson | `empireo` | XR | `shadow` |
| `tairn` | Tairn | `empireo` | T | `wing` |
| `andarna` | Andarna | `empireo` | A | `feather` |
| `rhiannon` | Rhiannon Matthias | `empireo` | RM | `shield` |
| `liam` | Liam Mairi | `empireo` | LM | `sword` |
| `dain` | Dain Aetos | `empireo` | DA | `compass` |
| `imogen` | Imogen Cardulo | `empireo` | IC | `flame` |
| `bodhi` | Bodhi Durran | `empireo` | BD | `knot` |
| `grace` | Grace Peterson | `mapa` | GP | `map` |
| `will` | Will Tucker | `mapa` | WT | `lantern` |
| `lucy` | Lucy | `mapa` | L | `star` |
| `nora` | Nora Seed | `biblioteca` | NS | `moon` |
| `mrs_elm` | Mrs. Elm | `biblioteca` | ME | `chess` |
| `voltaire` | Voltaire | `biblioteca` | V | `cat` |
| `ash` | Ash | `biblioteca` | A | `music` |
| `jude` | Jude Derry | `perfectos` | JD | `mask` |
| `aegan` | Aegan Cash | `perfectos` | AG | `crown` |
| `adrik` | Adrik Cash | `perfectos` | AD | `key` |
| `alex` | Alex Cash | `perfectos` | AL | `rose` |

Los nombres de personaje son nombres propios y no se traducen. El nombre de la
familia que se muestra en el álbum es el título canónico en español
(`Empíreo`, `El mapa de los anhelos`, `La biblioteca de la medianoche`,
`Perfectos mentirosos`), igual que los títulos guardados en la biblioteca.

### Emparejamiento con los libros

`CardCatalogService::normalize()`:

1. Quita el contenido entre paréntesis (`(Empíreo 2)`).
2. Aplica `Str::slug($value, ' ')`, que pasa a minúsculas, quita acentos y
   sustituye signos por espacios.
3. Colapsa espacios y recorta.

Una familia coincide con un libro cuando:

- El título normalizado del libro está en la lista `titles` de la familia.
- Y, si el libro tiene autor y la familia define `authors`, al menos un autor
  normalizado de la familia está **contenido** en el autor normalizado del libro.
  La contención permite «Matt Haig, Miguel Marqués Muñoz» → «matt haig».
- Si el libro no tiene autor, basta el título.

Alias necesarios:

- `Alas de onix` (sin tilde en la base) coincide con `Alas de ónix` por la
  normalización.
- `Mentiras y secretos` y `Perfectos mentirosos` comparten la familia
  `perfectos`; el autor de la base puede ser «Álex Ramírez» o «Álex Mírez».
- `El día de la Trilla` comparte la familia `empireo` aunque sea una antología.

El pool de la usuaria es la unión, sin duplicados, de las cartas de las familias
activadas por sus libros, en el orden del catálogo. Si no hay coincidencias, el
pool está vacío.

## Contenido de las cartas

Cada carta vive en `resources/cards/{clave}/{locale}.md`, en UTF-8 y texto plano.
No se usa Markdown con enlaces ni HTML. Estructura:

```
Querida lectora:

[Cuerpo de unos 120 palabras, en primera persona, con la voz del personaje.
Puede mencionar su mundo y sus vínculos, pero no cita texto literal del libro.]

Siempre,
[Firma]

P.D. [Posdata opcional]
```

Reglas:

- Originales: son cartas de fans «en la voz de», no fragmentos del libro.
- Cada archivo es una traducción escrita a mano, no pasada por
  `TranslationService`.
- Los 8 idiomas revisados son `config('languages.curated')`:
  `es`, `en`, `pt`, `fr`, `de`, `it`, `ca`, `gl`.
- Si el idioma activo no es revisado o falta el archivo, se sirve `es`.
- El servidor devuelve el texto tal cual; Vue lo escapa y separa párrafos por
  líneas en blanco. No se interpreta HTML.
- El pie del álbum incluye el aviso `ui.cards_disclaimer`: «Cartas inspiradas en
  personajes de ficción. Uso personal.»

### Carga de trabajo

Decisión de la usuaria (2026-11-04): la fase 1 entrega las **20 cartas en
español** (`es`); las traducciones a los otros 7 idiomas revisados quedan como
trabajo posterior por lotes. Mientras falte un idioma, `letter()` sirve la
versión `es` gracias al respaldo ya definido. La prueba de contenido verifica
las 20 cartas en `es` y se ampliará a los 8 idiomas cuando se completen las
traducciones.

## Modelo de datos

### `card_draws`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | FK `users` | `cascadeOnDelete` |
| `draw_date` | date | día en la zona de `cards.timezone` |
| `card_key` | string(64) | clave del catálogo |
| `ball_index` | unsignedTinyInteger | índice de la balota elegida |
| `duplicate` | boolean, default false | |
| `ink_earned` | unsignedTinyInteger, default 0 | 0 o 1 |
| `timestamps` | | |

Restricción única `(user_id, draw_date)`. Índice por `card_key`.

### `user_cards`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | FK `users` | `cascadeOnDelete` |
| `card_key` | string(64) | |
| `copies` | unsignedInteger, default 1 | incluye la primera |
| `first_unlocked_at` | timestamp | |
| `timestamps` | | |

Restricción única `(user_id, card_key)`.

### `user_wallets`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | FK `users` | `cascadeOnDelete`, único |
| `ink` | unsignedInteger, default 0 | |
| `timestamps` | | |

No hay tabla de cartas: el catálogo es configuración. `card_key` se valida contra
`config/cards.php`.

### Modelos y relaciones

- `App\Models\CardDraw`, `App\Models\UserCard`, `App\Models\UserWallet`.
- `User::cardDraws(): HasMany`, `User::userCards(): HasMany`,
  `User::wallet(): HasOne`.
- `CardDraw` castea `draw_date` a `date` y `duplicate` a `boolean`.
- `UserCard` castea `first_unlocked_at` a `datetime`.
- `UserWallet` castea `ink` a `integer`.

### Migraciones

- `2026_11_03_000002_create_card_draws_table.php`
- `2026_11_03_000003_create_user_cards_table.php`
- `2026_11_03_000004_create_user_wallets_table.php`

## Configuración

`config/cards.php`:

```php
return [
    'timezone' => env('CARDS_TIMEZONE', 'America/Bogota'),
    'daily_balls' => 8,
    'ink_per_duplicate' => 1,
    'families' => [
        'empireo' => [
            'label' => 'Empíreo',
            'emblem' => 'dragon',
            'titles' => ['Alas de sangre', 'Alas de hierro', 'Alas de ónix', 'El día de la Trilla'],
            'authors' => ['Rebecca Yarros'],
        ],
        // mapa, biblioteca, perfectos
    ],
    'cards' => [
        'violet' => [
            'family' => 'empireo',
            'character' => 'Violet Sorrengail',
            'monogram' => 'VS',
            'emblem' => 'dragon',
        ],
        // ...
    ],
];
```

- `CARDS_TIMEZONE` se puede cambiar en `.env` sin tocar código. Por defecto
  `America/Bogota`; el resto de la app sigue en UTC.
- `daily_balls` es el máximo de balotas; si el pool tiene menos, se muestran
  todas.
- `ink_per_duplicate` es 1 en la fase 1.

## Servicios y reglas

### `CardCatalogService`

- `families()`, `cards()`, `exists(string $key)`.
- `normalize(string $value): string`.
- `familyForBook(string $title, ?string $author): ?string`.
- `poolFor(User $user): array` — claves en orden de catálogo.
- `metadata(string $key): array` — clave, familia, etiqueta de familia,
  emblema de familia, personaje, monograma y emblema de la carta. No incluye la
  carta.
- `letter(string $key, string $locale): ?string` — lee el archivo, cae a `es`.
- `isUnlocked(User $user, string $key): bool` — consulta `user_cards`.
- `inPool(User $user, string $key): bool`.

El servicio no cachea: el catálogo es configuración y la carta se lee solo al
abrirla.

### `CardDeckService`

- `today(): CarbonImmutable` — `now(config('cards.timezone'))->startOfDay()`.
- `nextResetAt(): CarbonImmutable` — `today()->addDay()` en UTC para el cliente.
- `offersFor(User $user, CarbonImmutable $day): array` — claves de las balotas.
- `draw(User $user, int $ballIndex): array` — transacción completa.
- `drawnToday(User $user): ?CardDraw`.

Balotas deterministas y cambiantes:

```php
$seed = crc32($user->id.'|'.config('app.key'));
$randomizer = new Random\Randomizer(new Random\Engine\Mt19937($seed));
$keys = $pool;
$randomizer->shuffleArray($keys);

$offset = intdiv($day->startOfDay()->getTimestamp(), 86400) % count($keys);
$keys = array_merge(array_slice($keys, $offset), array_slice($keys, 0, $offset));

$offers = array_slice($keys, 0, min(config('cards.daily_balls'), count($keys)));
```

- El barajado es estable por usuaria; cada día se desplaza para que las balotas
  cambien de un día a otro sin perder el determinismo.
- La misma usuaria y el mismo día obtienen las mismas balotas.
- Las balotas no envían al cliente la clave ni el personaje: solo índice,
  familia, etiqueta y emblema de familia.
- Si el pool cambia el mismo día antes de tirar (por añadir un libro), las
  balotas pueden cambiar; la tirada sigue siendo única.
- Si el pool está vacío, no hay balotas y la tirada responde 422.

Tirada, dentro de `DB::transaction`:

1. Rechaza si ya existe `card_draws` para `(user_id, draw_date)`.
2. Recalcula las balotas y valida `0 <= ballIndex < count(offers)`.
3. Resuelve `card_key = offers[ballIndex]`.
4. Crea `card_draws` con `ball_index`, `duplicate` e `ink_earned`.
5. Busca `user_cards` con `lockForUpdate()`.
   - Si existe: `copies++`, `duplicate = true`, `ink_earned = 1`, y suma 1 a
     `user_wallets.ink` (creando la cartera si no existe).
   - Si no existe: crea `user_cards` con `copies = 1`,
     `first_unlocked_at = now()`, `duplicate = false`, `ink_earned = 0`.
6. Devuelve la carta, `duplicate`, `copies`, `ink` y `resetAt`.

La restricción única `(user_id, draw_date)` es la última defensa ante dos
peticiones simultáneas; si se dispara, se responde 422 con
`ui.cards_already_drawn`.

## API y rutas

Dentro del grupo `auth` + `nickname`:

| Método | Ruta | Acción |
|---|---|---|
| `GET` | `/cards` | `CardController@index` (Inertia) |
| `GET` | `/cards/{card}` | `CardController@show` (JSON) |
| `POST` | `/cards/draw` | `CardDrawController@store` (JSON) |

`GET /cards` props:

```json
{
  "cards": [
    {
      "key": "violet",
      "family": "empireo",
      "familyLabel": "Empíreo",
      "familyEmblem": "dragon",
      "character": "Violet Sorrengail",
      "monogram": "VS",
      "emblem": "dragon",
      "unlocked": true,
      "copies": 2
    }
  ],
  "offers": [
    { "index": 0, "family": "empireo", "familyLabel": "Empíreo", "familyEmblem": "dragon" }
  ],
  "drawn": null,
  "ink": 3,
  "progress": { "unlocked": 4, "total": 20 },
  "resetAt": "2026-11-04T05:00:00+00:00",
  "dailyBalls": 8
}
```

- `drawn` es `null` si aún no ha tirado hoy; si ya tiró:
  `{ "card": { ...metadata... }, "duplicate": false, "copies": 1, "inkEarned": 0 }`.
- `offers` solo lleva índice, familia, etiqueta y emblema de familia.
- `progress.total` es el tamaño del pool, no 20 si le faltan familias.

`POST /cards/draw` body `{ "ball": 0 }`:

- 200: `{ "card": {...}, "duplicate": false, "copies": 1, "ink": 1, "inkEarned": 1, "resetAt": "..." }`.
- 422: `{ "message": "..." }` con `ui.cards_already_drawn`,
  `ui.cards_invalid_ball` o `ui.cards_empty`.

`GET /cards/{card}`:

- 200: `{ "card": { ...metadata..., "letter": "..." }, "duplicate": false, "copies": 1 }`.
- 404 si la clave no existe en el catálogo.
- 403 si la carta no está en el pool de la usuaria o no está desbloqueada.
- Ruta con `where('card', '[a-z_]+')`.

Todos los accesos usan `auth()->user()`; nunca se confía en un `user_id` del
cliente. La carta se sirve en `app()->getLocale()` con caída a `es`.

## Interfaz

### Navegación

En `AppShell.vue`, orden de enlaces:

1. Biblioteca (`/`)
2. Estanterías (`/shelves`)
3. Cartas (`/cards`)
4. Decoraciones (`/decorations`)
5. Ajustes (`/settings`)

En escritorio se ven los cinco. En móvil, la barra inferior mantiene los dos
primeros y el menú desplegable incluye Cartas, Decoraciones y Ajustes.

### Pantalla `Cards/Index`

- Cabecera: título `ui.cards_title`, subtítulo `ui.cards_subtitle` y contador de
  tinta a la derecha.
- Escritorio (`lg`): dos columnas, bombo a la izquierda y álbum a la derecha.
- Móvil: bombo arriba y álbum debajo, a una columna.

### Componentes

- `Cards/Index.vue`: estado, peticiones y disposición.
- `CardMachine.vue`: bombo, balotas, manivela y cuenta atrás.
- `CardEnvelope.vue`: sobre cerrado.
- `CardLetterModal.vue`: carta en pergamino.
- `CardAlbum.vue`: gabinete de cartas.
- `CardEmblem.vue`: 20 emblemas SVG lineales; los de familia son `dragon`,
  `map`, `moon` y `mask`.

### Bombo (`CardMachine.vue`)

Estados:

1. **Sin tirar y sin balota elegida**: 8 balotas sobre el terciopelo; la manivela
   está desactivada y se muestra `ui.cards_choose_ball`.
2. **Balota elegida**: la balota se eleva y se marca; la manivela se activa.
3. **Girando**: la manivela gira, la balota tiembla y el bombo se atenúa; texto
   `ui.cards_cranking`.
4. **Resultado**: la balota cae por la rampa y aparece un sobre cerrado con el
   emblema de la familia. El sobre es un botón `ui.cards_open_letter`.
5. **Ya tiró hoy**: el bombo queda inerte, con el sobre del resultado y
   `ui.cards_reset_in` con la cuenta atrás.

Detalles:

- Cada balota es un `<button>` con `aria-label` = `ui.cards_ball_label`
  (`:number`, `:family`) y `aria-pressed`.
- La manivela es un `<button>` con `aria-label` = `ui.cards_crank`.
- El resultado se anuncia en una región `aria-live="polite"`.
- Si la tirada falla, se muestra `ui.cards_draw_error` y se reactiva el bombo.
- La cuenta atrás se calcula en el cliente desde `resetAt` y se actualiza cada
  minuto; se limpia al desmontar.

### Sobre (`CardEnvelope.vue`)

- Sobre de papel `crema`/`pergamino`, solapa, lacre `danger-strong` con el
  emblema de la familia y un borde `dorado`.
- No revela el personaje hasta abrir la carta; el monograma aparece dentro, en
  la carta y en el álbum.
- Al pulsarlo se pide `GET /cards/{card}` y se abre la carta.
- Si es repetida, un aviso `ui.cards_duplicate` con `+1 tinta` aparece bajo el
  sobre y en la carta.

### Carta (`CardLetterModal.vue`)

- Usa `Modal.vue` (diálogo nativo, foco atrapado, Escape y bloqueo de scroll).
- Hoja `pergamino` con texto `tinta`, `font-serif`, 17–19 px, interlineado
  amplio y capitular en el primer párrafo.
- Cabecera: emblema de la carta, monograma, personaje, familia y botón de
  cerrar.
- Cuerpo: párrafos separados por líneas en blanco, con `white-space: pre-line`.
- Pie: `ui.cards_new_card` o `ui.cards_duplicate` + `ui.cards_copies` si hay
  copias.
- En móvil ocupa el ancho disponible con padding de 16 px y scroll interno; en
  escritorio `max-w-2xl` y alto máximo del 85 % de la ventana.
- Si la petición falla, muestra `ui.cards_letter_error`.

### Álbum (`CardAlbum.vue`)

- Gabinete de madera con ranuras; rejilla de 2 columnas en móvil, 3 en `sm`, 4 en
  `lg` y 5 en `xl`.
- Carta desbloqueada: monograma, emblema de la carta, personaje y familia;
  badge `×N` si
  `copies > 1`; es un `<button>` que abre la carta.
- Carta bloqueada: reverso con patrón geométrico, emblema de la familia y «?»;
  no es interactiva y muestra `ui.cards_locked` al enfocar.
- Cabecera del álbum: `ui.cards_album`, barra `gold` y
  `ui.cards_album_progress` (`:unlocked`, `:total`).
- Pie: `ui.cards_disclaimer`.

### Tinta

- Frasco pequeño con gota y número; `ui.cards_ink` y ayuda `ui.cards_ink_help`.
- En la fase 1 solo se acumula; no hay botón de gasto.
- Al ganar tinta, el número hace una transición breve; con
  `prefers-reduced-motion` no se anima.

### Estado vacío

Si el pool está vacío: `ui.cards_empty`, `ui.cards_empty_help` y un enlace a
`/books/create`. Sin bombo ni álbum.

### Responsive y accesibilidad

- 360 px: bombo a ancho completo, cúpula de ~220 px, balotas de 44 px en 4×2,
  manivela como botón de ancho completo, álbum a 2 columnas, carta a pantalla
  completa.
- 768 px: cúpula de ~280 px, balotas de 52 px, álbum a 3 columnas.
- 1280 px: dos columnas, cúpula de ~320 px, álbum a 4–5 columnas.
- Foco visible con el anillo global; balotas y manivela con etiquetas; el color
  no es el único indicador (texto «?», candado y avisos).
- `prefers-reduced-motion`: sin giro, caída ni transiciones; el resultado
  aparece de inmediato.

### Clases visuales nuevas en `resources/css/textures.css`

- `.card-machine`, `.card-machine__base`, `.card-machine__dome`,
  `.card-machine__ball`, `.card-machine__ball--selected`,
  `.card-machine__ball--drop`, `.card-machine__crank`,
  `.card-machine__crank--turning`, `.card-machine__chute`
- `.card-envelope`, `.card-envelope__flap`, `.card-envelope__seal`,
  `.card-envelope__label`
- `.card-letter`, `.card-letter__body`, `.card-letter__dropcap`
- `.card-album`, `.card-slot`, `.card-back`, `.card-monogram`, `.card-ink`

Todas usan los tokens existentes (`madera`, `madera-clara`, `pergamino`,
`crema`, `tinta`, `dorado`, `danger-strong`, `night`, `panel`, `line`, `gold`).
No se añaden colores nuevos.

## Textos de interfaz

Se añaden a los 8 idiomas revisados. La fuente en español:

| Clave | Texto |
|---|---|
| `ui.nav_cards` | Cartas |
| `ui.cards_title` | Cartas |
| `ui.cards_subtitle` | Un bombo de balotas y cartas de personajes que esperan en tu biblioteca. |
| `ui.cards_ink` | Tinta |
| `ui.cards_ink_help` | Cada carta repetida se convierte en 1 de tinta. La tinta servirá para los eventos. |
| `ui.cards_choose_ball` | Elige una balota |
| `ui.cards_ball_label` | Balota :number, :family |
| `ui.cards_crank` | Girar la manivela |
| `ui.cards_cranking` | Girando… |
| `ui.cards_open_letter` | Abrir la carta |
| `ui.cards_new_card` | Carta nueva |
| `ui.cards_duplicate` | Ya tenías esta carta: +1 tinta |
| `ui.cards_album` | Álbum |
| `ui.cards_album_progress` | :unlocked de :total cartas |
| `ui.cards_locked` | Aún no la tienes |
| `ui.cards_locked_hint` | Sigue sacando balotas para desbloquearla. |
| `ui.cards_copies` | ×:count |
| `ui.cards_empty` | Aún no hay cartas para tus libros. |
| `ui.cards_empty_help` | Añade un libro con personajes conocidos para empezar la colección. |
| `ui.cards_already_drawn` | Ya has sacado tu carta de hoy. Vuelve mañana. |
| `ui.cards_invalid_ball` | Esa balota ya no está en el bombo. |
| `ui.cards_reset_in` | Vuelve en :time |
| `ui.cards_hours_minutes` | :hours h :minutes min |
| `ui.cards_hours` | :hours h |
| `ui.cards_minutes` | :minutes min |
| `ui.cards_letter_from` | Carta de :character |
| `ui.cards_close` | Cerrar |
| `ui.cards_draw_error` | No se pudo girar el bombo. Inténtalo de nuevo. |
| `ui.cards_letter_error` | No se pudo abrir la carta. Inténtalo de nuevo. |
| `ui.cards_disclaimer` | Cartas inspiradas en personajes de ficción. Uso personal. |

`tests/Feature/CatalogTest.php` obliga a que existan en los 8 idiomas.

## Pruebas

### Backend (Pest)

`tests/Feature/CardCatalogTest.php`:

- Normalización de títulos y autores (tildes, paréntesis, coautores).
- `Alas de onix` activa `empireo`.
- `La biblioteca de la medianoche` con «Matt Haig, Miguel Marqués Muñoz»
  activa `biblioteca`.
- `Mentiras y secretos` con «Álex Ramírez» activa `perfectos`.
- El pool no tiene duplicados y respeta el orden del catálogo.
- Sin libros, el pool está vacío.
- Las 20 cartas tienen metadatos completos y emblemas conocidos.

`tests/Feature/CardDrawTest.php`:

- Las balotas son estables para la misma usuaria y día, y cambian con otro día.
- El número de balotas es `min(8, pool)`.
- Una tirada por día; la segunda responde 422.
- Balota fuera de rango responde 422.
- Carta nueva: `user_cards.copies = 1`, `ink = 0`.
- Repetida: `copies++`, `ink++`, `duplicate = true`, `ink_earned = 1`.
- La cartera se crea si no existe.
- Aislamiento: la tirada de otra usuaria no afecta a la primera.
- Pool vacío: 422 y sin filas.
- Límite del día con `Carbon::setTestNow` en UTC y en `America/Bogota`.

`tests/Feature/CardPageTest.php`:

- `GET /cards` devuelve props de Inertia con el pool, las balotas, la tinta y el
  progreso.
- `offers` no expone `key` ni `character`.
- Si ya tiró, `drawn` viene relleno y el bombo queda inerte.
- `GET /cards/{key}` devuelve la carta desbloqueada y 403 si no lo está.
- 404 para una clave desconocida.
- La carta se sirve en el idioma activo y cae a español.
- No se accede a cartas de otra usuaria.

`tests/Feature/CardContentTest.php` (se activa al cerrar el contenido):

- Las 20 cartas tienen los 8 archivos de idioma.
- Cada archivo tiene entre 120 y 160 palabras.
- No hay HTML ni enlaces.

### Frontend (Vitest)

- `CardMachine.test.js`: balotas, selección, manivela desactivada/activa,
  sobre al resolver y aviso de repetida.
- `CardAlbum.test.js`: bloqueadas/desbloqueadas, copias, progreso y emisión
  `open`.
- `CardLetterModal.test.js`: párrafos, capitular, aviso de repetida y cierre.
- `AppShell.test.js`: cinco enlaces y Cartas en el menú móvil.
- `cards.test.js`: `countdownParts` (horas y minutos, menos de una hora,
  cero).

### Verificación manual

- 360 px, 768 px y 1280 px.
- Teclado: tabular por balotas, manivela, sobre, carta y álbum; Escape cierra.
- `prefers-reduced-motion`.
- Una tirada, recarga y comprobación de que el bombo sigue inerte.
- Repetida forzada en pruebas y aviso de tinta.

## Documentación visual

`docs/DISENO.md`:

- Nueva sección **Cartas** después de **Estanterías**.
- Bombo, sobre, carta, álbum, tinta, navegación y responsive.
- Clases nuevas de `textures.css` y tokens usados.
- Fila nueva en el registro de cambios con fecha 2026-11-03.

## Fases 2 y 3

### Fase 2 — eventos rotativos

- `config/events.php` con eventos de 1–4 días encadenados.
- Rotación determinista por fecha desde una época fija; sin cron.
- `event_progress` (usuaria, evento, día, tarea, completado) y
  `user_collectibles` (pegatinas y cartas de evento).
- Tareas: tirar la balota, registrar una lectura, añadir un libro, abrir el
  álbum y mini-juegos.
- La tinta se gasta en cartas de evento.
- Se diseña en un spec aparte.

### Fase 3 — mini-juegos

- Cada mini-juego es un componente Vue y un endpoint de verificación.
- Recompensas en tinta o coleccionables.
- Se diseña en un spec aparte.

## Riesgos y mitigaciones

- **Volumen de contenido**: fase 1 con las 20 cartas en español; traducciones a
  los otros 7 idiomas por lotes más adelante. El código no se bloquea por el
  contenido: `CardContentTest` exige por ahora solo `es` y se ampliará a los 8
  idiomas al completar las traducciones.
- **Derechos de autor**: cartas originales de fans, sin citas literales, con
  aviso de uso personal.
- **Zona horaria**: por defecto `America/Bogota`, configurable con
  `CARDS_TIMEZONE`; el resto de la app sigue en UTC.
- **Balotas que cambian al añadir un libro el mismo día**: aceptado; la tirada
  sigue siendo única.
- **InfinityFree**: sin cron, colas ni Node en el servidor; la rotación y el
  reinicio son deterministas y perezosos. Migraciones por el endpoint seguro.
- **SQLite en pruebas / MySQL en desarrollo**: la restricción única protege la
  tirada; `lockForUpdate` actúa en MySQL y es inocuo en SQLite.
