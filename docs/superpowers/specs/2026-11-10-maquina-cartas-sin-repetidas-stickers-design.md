# Diseño: máquina sin cartas repetidas y stickers de biblioteca

**Fecha:** 2026-11-10
**Estado:** aprobado por la usuaria
**Relacionado:** `2026-11-03-cartas-bombo-design.md`, `2026-11-05-eventos.md`

## Objetivo

Cambiar la máquina expendedora de `/cards` para que nunca entregue cartas
repetidas y para que las bolitas también puedan contener stickers de una colección
nueva de objetos de biblioteca. Cada tirada diaria entrega una sola sorpresa: una
carta nueva o un sticker nuevo. La colección se completa cuando la usuaria reúne
las 20 cartas y los 20 stickers.

## Decisiones de la usuaria

- Las bolitas son de dos tipos, con carta o con sticker, pero **todas se ven
  iguales**: el contenido es sorpresa hasta abrirlo.
- No hay repetidos: ni cartas ni stickers. Las bolitas solo se llenan con objetos
  que la usuaria todavía no tiene.
- La colección de stickers es nueva, de **20 objetos de biblioteca** con borde
  blanco troquelado y sombra, no los stickers de los eventos.
- Los stickers se ven en el **mismo álbum de cartas** (`/cards/album`), en una
  sección propia. La usuaria cambiará el diseño del álbum más adelante.
- La máquina funciona **aunque no haya libros**: en ese caso solo puede entregar
  stickers. Las cartas aparecen cuando los libros activan familias.
- Cuando ya no queda nada por obtener, la máquina muestra **“¡Colección
  completa!”** y deja de dispensar.
- Se mantiene una tirada por día y el reinicio a medianoche.

## Alcance

- Catálogo de 20 stickers de la máquina en `config/cards.php`.
- Componente Vue con 20 ilustraciones SVG troqueladas.
- Mezcla diaria de bolitas con cartas no obtenidas y stickers no obtenidos.
- Tirada sin repetidos y sin tinta por repetido.
- Persistencia del tipo de objeto en `card_draws` y de los stickers en
  `user_collectibles`.
- Resultado de tirada de carta o de sticker en `/cards`.
- Sección de stickers en `/cards/album`.
- Aislamiento de los stickers de la máquina respecto de las vistas de eventos.
- Textos en español e inglés, pruebas de backend y frontend, y registro en
  `docs/DISENO.md`.

## Fuera de alcance

- Intercambio de cartas o stickers entre usuarias.
- Reinicio o temporadas de la colección de la máquina.
- Cambios en el sistema de tinta o en la tienda de eventos.
- Retratos o stickers de personas.
- Rediseño visual del álbum de cartas.
- Animaciones nuevas de la máquina más allá de revelar el resultado.
- Historial visible de tiradas.

## Colecciones

### Cartas

Se mantienen las 20 cartas de `config/cards.php` y la activación por familias a
partir de los libros de la usuaria. La diferencia es que las bolitas solo incluyen
cartas del pool activo que la usuaria **no** tenga en `user_cards`.

### Stickers de la máquina

Nueva colección de 20 objetos con clave prefijada `maquina_` para no colisionar con
las claves de los stickers de eventos.

| Clave | Nombre | Clave de texto |
|---|---|---|
| `maquina_libro` | Libro abierto | `ui.machine_sticker_libro` |
| `maquina_pluma` | Pluma de ganso | `ui.machine_sticker_pluma` |
| `maquina_tintero` | Tintero | `ui.machine_sticker_tintero` |
| `maquina_taza` | Taza de té | `ui.machine_sticker_taza` |
| `maquina_gato` | Gato lector | `ui.machine_sticker_gato` |
| `maquina_luna` | Luna | `ui.machine_sticker_luna` |
| `maquina_vela` | Vela | `ui.machine_sticker_vela` |
| `maquina_planta` | Planta | `ui.machine_sticker_planta` |
| `maquina_lupa` | Lupa | `ui.machine_sticker_lupa` |
| `maquina_llave` | Llave antigua | `ui.machine_sticker_llave` |
| `maquina_gafas` | Gafas | `ui.machine_sticker_gafas` |
| `maquina_marcapaginas` | Marcapáginas | `ui.machine_sticker_marcapaginas` |
| `maquina_reloj` | Reloj de bolsillo | `ui.machine_sticker_reloj` |
| `maquina_buho` | Búho | `ui.machine_sticker_buho` |
| `maquina_pocion` | Poción | `ui.machine_sticker_pocion` |
| `maquina_pergamino` | Pergamino | `ui.machine_sticker_pergamino` |
| `maquina_maquina_escribir` | Máquina de escribir | `ui.machine_sticker_maquina_escribir` |
| `maquina_rosa` | Rosa | `ui.machine_sticker_rosa` |
| `maquina_cuervo` | Cuervo | `ui.machine_sticker_cuervo` |
| `maquina_cristal` | Cristal de luna | `ui.machine_sticker_cristal` |

La estructura en `config/cards.php` será:

```php
'machine_stickers' => [
    ['key' => 'maquina_libro', 'name' => 'ui.machine_sticker_libro'],
    // ...
],
```

El arte se dibuja en un componente nuevo, `LibrarySticker.vue`, con el mismo
lenguaje visual que `EventSticker.vue`: silueta blanca troquelada, trazo de tinta,
colores de la paleta y sombra suave. Las claves de arte coinciden con las claves
del catálogo.

## Mezcla diaria de bolitas

Reglas:

1. Se calculan las cartas activas del pool que la usuaria no tenga.
2. Se calculan los stickers de la máquina que la usuaria no tenga.
3. Se barajan ambas listas con una semilla estable por usuaria y día, reutilizando
   el patrón determinista actual (`crc32($user->id.'|'.config('app.key'))` y
   rotación por día).
4. Se toman hasta 4 cartas y hasta 4 stickers.
5. Si una lista se queda corta, las bolitas restantes se llenan con la otra hasta
   un máximo de 8.
6. Si no hay ningún objeto pendiente, la lista queda vacía y la máquina muestra el
   estado de colección completa.

Cada bolita se representa internamente como:

```php
['kind' => 'card', 'key' => 'violet']
['kind' => 'sticker', 'key' => 'maquina_libro']
```

El frontend recibe solo el índice de cada bolita, nunca el tipo ni la clave. Así
todas las bolitas se ven iguales y la sorpresa se conserva.

## Tirada

`CardDeckService::draw()` valida la bolita contra la mezcla del día y persiste un
único resultado dentro de una transacción.

- **Carta:** crea `UserCard` con `copies = 1` si no existe. Como la mezcla solo
  incluye cartas no obtenidas, no puede haber duplicados. No se suma tinta.
- **Sticker:** crea `UserCollectible` con `kind = sticker`, `event_key = maquina`,
  `source = machine`, `acquired_at = now()` y `collectible_key` del catálogo. La
  restricción única `(user_id, collectible_key)` impide repetidos.
- `card_draws` guarda `kind`, `item_key`, `ball_index`, `duplicate = false` e
  `ink_earned = 0`.
- Se mantiene una sola tirada por usuaria y día salvo que
  `cards.allow_multiple_draws` esté activo.
- Si no hay bolitas disponibles, la tirada responde con un error de validación y
  la interfaz muestra el estado de colección completa.

Respuesta de la tirada:

```json
{
    "kind": "card",
    "card": { "...": "metadatos de la carta" },
    "sticker": null,
    "ink": 0,
    "resetAt": "2026-11-11T05:00:00Z"
}
```

```json
{
    "kind": "sticker",
    "card": null,
    "sticker": {
        "key": "maquina_libro",
        "name": "Libro abierto"
    },
    "ink": 0,
    "resetAt": "2026-11-11T05:00:00Z"
}
```

## Datos y migraciones

### `card_draws`

- Renombrar `card_key` a `item_key`.
- Añadir `kind` (`string(16)`, por defecto `card`).
- Las filas existentes quedan con `kind = card` e `item_key` con su valor anterior.
- Mantener el índice único `(user_id, draw_date)`.

### `user_cards`

Sin cambios. `copies` se conserva por compatibilidad, pero las tiradas nuevas
siempre crean `copies = 1` y no incrementan copias.

### `user_collectibles`

Sin cambios de esquema. Los stickers de la máquina usan:

- `collectible_key`: `maquina_*`
- `kind`: `sticker`
- `event_key`: `maquina`
- `source`: `machine`

### Configuración

- Añadir `machine_stickers` a `config/cards.php`.
- Eliminar `ink_per_duplicate`, que deja de usarse.

## Interfaz

### `/cards`

- Se mantienen las 8 bolitas y la manivela.
- Las bolitas no muestran familia ni tipo: todas iguales.
- Al girar, se revela:
  - **Carta:** sobre actual, botón para abrir la carta y mensaje de carta nueva.
  - **Sticker:** sticker troquelado grande, nombre traducido, mensaje “¡Sticker
    nuevo!” y progreso de stickers.
- No se muestra el aviso de carta repetida ni tinta ganada.
- Si la colección está completa, se muestra “¡Colección completa!” con un texto de
  apoyo y el enlace al álbum; no se puede girar.
- Si la usuaria no tiene libros, la máquina sigue disponible con bolitas de
  stickers.

### `/cards/album`

- Se mantiene la cuadrícula de cartas y su progreso.
- Debajo se añade la sección **“Stickers de la máquina”** con:
  - progreso `obtenidos / 20`;
  - cuadrícula de 20 huecos;
  - stickers obtenidos a color;
  - stickers pendientes con silueta bloqueada y signo de interrogación.
- La página sigue completando la tarea de evento `album`.

### Contrato de página

`Cards/Index` recibe además:

- `stickers`: lista de los 20 stickers con `key`, `name`, `unlocked` y
  `acquired_at`.
- `stickerProgress`: `{ unlocked, total }`.
- `complete`: booleano que indica que no quedan cartas ni stickers pendientes.

`Cards/Album` recibe las mismas props de stickers y progreso.

Al recibir una tirada de sticker, `Cards/Index` actualiza la lista local de
stickers y su progreso, igual que hoy actualiza la carta desbloqueada. Al recargar
la página, `drawn` reconstruye tanto cartas como stickers a partir de
`card_draws.kind` e `item_key`.

## Aislamiento de eventos

Los stickers de la máquina no deben aparecer en:

- la colección de `/events`;
- el álbum de `/events/album`;
- los cálculos de progreso de eventos.

Para ello:

- `EventController@index` filtra la colección a elementos que existan en el
  catálogo de eventos.
- `EventController@album` limita los colectables consultados a las claves de
  eventos del catálogo, excluyendo `event_key = maquina`.
- `EventService::ownedCollectibleKeys()` filtra por las claves del catálogo de
  eventos si se sigue usando en el futuro.

## Textos

Añadir en `lang/es.json` y `lang/en.json`, manteniendo los catálogos planos y
ordenados:

- Nombres de los 20 stickers (`ui.machine_sticker_*`).
- `ui.cards_machine_stickers`: título de la sección del álbum.
- `ui.cards_sticker_progress`: texto de progreso de stickers.
- `ui.cards_sticker_new`: “¡Sticker nuevo!”.
- `ui.cards_complete`: “¡Colección completa!”.
- `ui.cards_complete_help`: texto de apoyo del estado completo.
- Cualquier texto de error reutiliza `ui.cards_draw_error`.

## Pruebas

### Backend

- La mezcla es determinista para la misma usuaria y día.
- La mezcla excluye cartas y stickers ya obtenidos.
- La mezcla incluye ambos tipos cuando hay disponibilidad.
- Si una colección se queda corta, la otra rellena hasta 8.
- Sin libros, la mezcla contiene stickers y la tirada funciona.
- Al completar las dos colecciones, la mezcla queda vacía.
- Tirada de carta: crea una carta, no crea sticker, no da tinta y devuelve
  `kind = card`.
- Tirada de sticker: crea un colectable `maquina_*`, no crea carta y devuelve
  `kind = sticker`.
- No se puede obtener el mismo sticker dos veces.
- Se rechaza una segunda tirada el mismo día.
- Se rechaza una bolita fuera de rango.
- Se mantiene el cambio de día por zona horaria.
- El álbum devuelve cartas y stickers con sus progresos.
- Los stickers de la máquina no aparecen en las vistas de eventos.

### Frontend

- `CardMachine` muestra el sobre cuando el resultado es carta y emite `open`.
- `CardMachine` muestra el sticker cuando el resultado es sticker y no emite
  `open`.
- `CardMachine` muestra el estado de colección completa sin bolitas.
- `CardAlbum` muestra la sección de stickers, los obtenidos y los bloqueados.
- `LibrarySticker` renderiza los 20 artes sin errores y respeta `locked`.
- Se actualizan las pruebas existentes que asumían repetidos y tinta.

## Documentación visual

Actualizar `docs/DISENO.md` con:

- La nueva sección de stickers del álbum.
- El resultado de sticker en la máquina.
- El estado de colección completa.
- La fila correspondiente en el registro de cambios con fecha 2026-11-10.

## Criterios de aceptación

1. Ninguna tirada entrega una carta o un sticker que la usuaria ya tenga.
2. Las bolitas no revelan su contenido antes de la tirada.
3. La máquina puede dar stickers sin necesidad de libros.
4. Al completar las 40 piezas, la máquina se detiene y muestra el estado de
   colección completa.
5. Los stickers de la máquina se ven en `/cards/album` y no contaminan los
   eventos.
6. Las pruebas de backend y frontend pasan.
7. `docs/DISENO.md` refleja los cambios visuales.
