# Rediseño de la dispensadora de cartas (máquina expendedora)

**Fecha:** 2026-11-04 · **Estado:** aprobado por la usuaria (opción A)
**Relacionado:** `2026-11-03-cartas-bombo-design.md`, `docs/DISENO.md`

## Objetivo

La máquina de balotas se ve plana. Se rediseña como una **máquina expendedora
con volumen**, al estilo de una máquina de chucherías, manteniendo la paleta
de madera y latón del sistema (sin colores nuevos) y toda la mecánica,
accesibilidad y estados ya implementados.

## Decisiones de la usuaria

- Paleta: **madera + latón** (tokens existentes: `madera`, `madera-clara`,
  `pergamino`, `crema`, `dorado`, `night`, `panel`, `line`, `gold`).
- Estructura: **máquina completa** (ventana con balotas dentro, panel de
  control y trampilla de salida), no solo profundidad 3D.

## Diseño visual

### Cuerpo 3D

- Gabinete de madera: frente con veta y degradado `madera-clara` → `madera`,
  costado derecho en sombra (misma técnica de dos caras que el mueble de la
  Biblioteca) y esquinas redondeadas.
- Cornisa superior con filo de latón `dorado` y sombra interior; zócalo base
  más oscuro con sombra proyectada al suelo (elipse difusa).
- Perspectiva sutil del conjunto (`perspective` + `rotateY` leve) solo en
  escritorio; en móvil el frente es recto.

### Ventana de cristal

- Marco redondeado de latón `dorado` con doble filo (claro arriba, oscuro
  abajo) y fondo `night`.
- Interior: las **8 balotas sueltas en desorden** (posiciones y rotaciones
  fijas por índice, deterministas), visibles a través del cristal; siguen
  mostrando el emblema de familia y su etiqueta accesible.
- Cristal con degradado de brillo diagonal y un reflejo lateral
  (`::after` con blanco translúcido rotado), por encima de las balotas.

### Interacción (sin cambios de comportamiento)

- Balota elegida: se resalta con anillo `gold` y se desliza al surco inferior
  de la ventana.
- Girar: la **palanca** baja y gira (clase existente `--turning`), la balota
  cae hacia la trampilla con la animación `--drop` existente.

### Panel de control (columna derecha del frente)

- **Palanca de latón vertical con pomo** (sustituye a la manivela circular):
  base de latón, brazo y pomo `dorado`; activa solo con balota elegida,
  desactivada durante la tirada y con resultado; conserva `aria-label` y el
  giro `card-machine__crank--turning`.
- **Trampilla de salida** en la parte baja: ranura oscura con marco de latón
  (`.card-machine__chute` rehecha como boca de salida) donde cae el sobre.

### Responsive

- 360 px: frente recto a ancho completo; balotas de 40–44 px repartidas en la
  ventana; palanca y trampilla en fila debajo de la ventana.
- ≥768 px: perspectiva 3D, ventana a la izquierda (≈70 % del ancho) y panel
  de control a la derecha (≈30 %), como la referencia.

### Accesibilidad y movimiento

- Se conservan `aria-pressed`, `aria-label` de balotas y palanca, y la región
  `aria-live` del resultado.
- Con `prefers-reduced-motion: reduce`: sin giro de palanca, sin caída ni
  deslizamientos.

## Clases CSS

Se mantienen los nombres existentes (`card-machine`, `__base`, `__dome`,
`__ball` + `--selected/--drop`, `__crank` + `--turning`, `__chute`) y se
añaden solo modificadores/bloques nuevos de la misma familia:
`card-machine__body`, `__side`, `__window`, `__lever`, `__lever-arm`,
`__lever-knob`, `__slot`. Todo en `resources/css/textures.css`.

## Componentes

- `CardMachine.vue`: cambia el marcado interno (gabinete + ventana + panel);
  props, emits, countdown y lógica no cambian.
- Sin cambios en backend, rutas, álbum ni sobre.

## Pruebas

- `CardMachine.test.js`: se actualizan los selectores si cambia el botón de la
  manivela (palanca conserva las mismas clases `card-machine__crank`);
  expectativas de balotas, selección, giro y sobre no cambian.
- Suite completa verde: `php artisan test`, `npm run test`, `pint --test`,
  `npm run build`.
- Registro del cambio en `docs/DISENO.md` (sección Cartas + fila fechada).

## Fuera de alcance

- Álbum, sobre, carta, backend y contenido: sin cambios.
- Fases 2/3 del módulo (eventos y minijuegos): no se tocan.
