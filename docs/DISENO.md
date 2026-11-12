# DISEÑO — Biblioteca

**Última actualización:** 2026-11-11
**Estado:** vigente
**Alcance:** identidad visual, componentes, estructura de interfaz y convenciones de diseño de la aplicación Biblioteca.

> Este documento es la fuente única de verdad visual del proyecto. Cualquier agente o persona que modifique colores, tipografías, componentes, espaciados, texturas o estructura de las pantallas debe actualizar este archivo y registrar el cambio en la tabla **Registro de cambios** antes de considerar el trabajo terminado. No se permiten estilos nuevos fuera de estos tokens sin registrarlos aquí.

---

## 1. Concepto

Una biblioteca personal con aire de biblioteca antigua. Por defecto se vive de día, en tonos pastel: papel crema, tarjetas blancas y acentos rosas que dejan todo el protagonismo a la madera. Quien lo prefiera puede cambiar a la **noche arcana** —violeta profundo, oro y turquesa mágico— desde **Ajustes → Apariencia**. La sensación debe ser íntima, ordenada y un poco arcana, no recargada. La estantería sigue siendo la protagonista; la interfaz la acompaña sin competir con las portadas.

Principios:

1. **Las portadas mandan.** El mueble conserva su madera y sus colores cálidos; el entorno es sobrio para que los libros destaquen.
2. **Antiguo, no viejo.** Texturas sutiles, buena legibilidad y contrastes modernos.
3. **Ligero.** Nada de imágenes pesadas; texturas con CSS/SVG. El hosting es compartido.
4. **Cálido y literario.** Mensajes breves, en español, con tono literario pero directo.
5. **Consistencia.** Todo componente nuevo reutiliza los tokens de este documento.
6. **El mueble no se toca.** Madera, espaldar, balda, portadas y lomos conservan su paleta y sus reglas.

---

## 2. Paleta de colores

La interfaz usa **roles semánticos**: los componentes no nombran colores concretos. El
**tema claro pastel** es el valor por defecto; el **tema oscuro** recupera la paleta de
noche arcana. La persona elige en **Ajustes → Apariencia** y la elección se guarda en su
cuenta (columna `users.theme`).

### Tema claro (por defecto)

| Token | Hex | Uso |
|---|---|---|
| `page` | `#FDF7F0` | Fondo de página y cielo arcano |
| `surface` | `#FFFFFF` | Tarjetas, paneles, modales e inputs |
| `surface-raised` | `#FFF8F1` | Navegación, cabeceras, hover suave |
| `surface-sunken` | `#F6EDE3` | Zonas hundidas, pistas y previsualizaciones |
| `ink` | `#3B3630` | Texto principal |
| `ink-muted` | `#7A7068` | Texto secundario, metadatos y placeholders |
| `ink-soft` | `#9A9088` | Texto terciario y ayudas |
| `heading` | `#3B3630` | Títulos `h1`–`h3` |
| `on-accent` | `#FFFFFF` | Texto sobre fondos saturados |
| `edge` | `#E8D5C4` | Bordes, separadores y anillos suaves |
| `edge-strong` | `#D6BEA8` | Bordes destacados y hover |
| `accent` | `#DB2777` | Acento principal: títulos, estrellas, logo y detalles |
| `accent-strong` | `#BE185D` | Hover del acento |
| `accent-soft` | `#FDE2E4` | Fondos de insignias y selección |
| `secondary` | `#0F766E` | Enlaces, foco, borde activo y acentos mágicos |
| `secondary-strong` | `#115E59` | Hover de acentos turquesa |
| `tertiary` | `#7C3AED` | Acción principal y pestaña activa |
| `tertiary-strong` | `#6D28D9` | Hover y estado activo de la acción principal |
| `danger` | `#E11D48` | Errores, texto y bordes destructivos |
| `danger-strong` | `#BE123C` | Fondo de botones destructivos |
| `success` | `#047857` | Mensajes de éxito |
| `warning` | `#B45309` | Avisos |
| `ring` | `#7C3AED` | Anillo de foco |
| `scrim` | `#1C1A24` | Velos de modales (siempre oscuro) |

### Tema oscuro

Mismos roles, sobrescritos en `:root[data-theme='dark']`:

| Token | Hex |
|---|---|
| `page` | `#12101F` |
| `surface` | `#211D3A` |
| `surface-raised` | `#1A1730` |
| `surface-sunken` | `#191632` |
| `ink` | `#F1ECFF` |
| `ink-muted` | `#B7AED6` |
| `ink-soft` | `#8C82B8` |
| `heading` | `#E8C887` |
| `on-accent` | `#FFFFFF` |
| `edge` | `#3A3363` |
| `edge-strong` | `#4A4278` |
| `accent` | `#E8C887` |
| `accent-strong` | `#D4B271` |
| `accent-soft` | `#3A3350` |
| `secondary` | `#74D8C9` |
| `secondary-strong` | `#5FC7B8` |
| `tertiary` | `#7C5CE0` |
| `tertiary-strong` | `#6A4AC9` |
| `danger` | `#F2A0B0` |
| `danger-strong` | `#A8455C` |
| `success` | `#8FE3B8` |
| `warning` | `#E8C887` |
| `ring` | `#74D8C9` |
| `scrim` | `#0A0814` |

### Paleta del mueble (estanterías y libros)

Estos tokens siguen definidos con sus valores originales y quedan reservados al mueble y a los libros. No se usan en el resto de la interfaz y no cambian entre temas.

| Token | Hex | Uso |
|---|---|---|
| `bosque` | `#2F4A3C` | Lomo sin portada, detalles del mueble |
| `salvia` | `#7A9A7E` | Lomo sin portada, detalles del mueble |
| `oliva` | `#556B4F` | Lomo sin portada |
| `oliva-hover` | `#465A41` | Reservado al mueble |
| `madera` | `#6B4F3A` | Baldas, frente de la balda, bordes de la estantería |
| `madera-clara` | `#8B6B4F` | Vetas y brillos de la madera |
| `pergamino` | `#F3E9D2` | Portada de sustitución, contraste de lomos |
| `crema` | `#FAF4E6` | Portada de sustitución, texto sobre madera y lomos |
| `tinta` | `#2B2620` | Texto sobre portadas claras y lomos |
| `terracota` | `#A9714B` | Lomo sin portada |
| `dorado` | `#C2A878` | Halo de portadas y lomos |
| `malva` | `#9E61AB` | Objetos decorativos rosados |
| `laton` | `#E8C887` | Objetos decorativos dorados |

### Reglas de contraste

- Texto `ink` sobre `page`, `surface`, `surface-raised` y `surface-sunken`: cumple AA.
- `ink-muted` se usa para secundarios; no para párrafos largos.
- Texto `on-accent` (blanco) sobre `tertiary`, `accent-strong` y `danger-strong`: cumple AA.
- `accent` se reserva a títulos, estrellas, logo y detalles; no para párrafos.
- `secondary` se usa en enlaces cortos y foco; no como color de cuerpo de texto.
- El mueble mantiene sus combinaciones propias: `crema`/`tinta` sobre madera, portadas y lomos.
- Nunca usar `dorado` ni `laton` como color de texto pequeño sobre fondos claros; pertenecen al mueble.

---

## 3. Tipografía

### Familias

| Uso | Familia | Pesos |
|---|---|---|
| Títulos, nombres de libros, cifras decorativas | **Cormorant Garamond** | 500, 600, 700 |
| Interfaz, cuerpo, formularios, botones | **Inter** | 400, 500, 600 |

Ambas se autoalojan en el build (sin CDN) mediante `@fontsource`. Se declaran con `font-display: swap`.

### Escala

| Token | Tamaño / interlineado | Familia / peso | Uso |
|---|---|---|---|
| `display` | 40 / 48 px | Cormorant 600 | Título de la biblioteca |
| `h1` | 32 / 40 px | Cormorant 600 | Título de página |
| `h2` | 24 / 32 px | Cormorant 600 | Título de sección o estantería |
| `book-title` | 18 / 24 px | Cormorant 600 | Nombre del libro en tarjeta |
| `body` | 16 / 24 px | Inter 400 | Texto general |
| `body-strong` | 16 / 24 px | Inter 600 | Énfasis, botones |
| `small` | 14 / 20 px | Inter 400 | Metadatos, ayudas |
| `caption` | 12 / 16 px | Inter 500, mayúsculas, tracking 0.04em | Etiquetas |

En móvil, `display` baja a 32/40 px y `h1` a 28/36 px.

---

## 4. Espaciado, radios y sombras

- **Base:** múltiplos de 4 px. Escala: 4, 8, 12, 16, 24, 32, 48, 64.
- **Padding de página:** 16 px en móvil, 24–32 px en tablet/escritorio.
- **Ancho máximo de contenido:** 1200 px, centrado.
- **Radios:** 4 px (inputs y botones), 6 px (tarjetas), 12 px (modales). Las baldas son rectas.
- **Sombras:** derivadas de `scrim` con `color-mix`, para que funcionen en ambos temas.
  - Suave: `0 1px 2px color-mix(in srgb, var(--color-scrim) 12%, transparent)`
  - Tarjeta: `0 2px 6px color-mix(in srgb, var(--color-scrim) 16%, transparent)`
  - Portada en hover: `0 8px 20px color-mix(in srgb, var(--color-scrim) 22%, transparent)`
  - Modal: `0 20px 50px color-mix(in srgb, var(--color-scrim) 35%, transparent)`
  - Balda: `0 6px 6px rgba(43, 38, 32, 0.22)` con frente biselado y sombra interior de contacto.

---

## 5. Texturas

- **Cielo arcano:** clase `.arcane-sky` sobre `page`, con dos resplandores radiales (`tertiary` al 30 % arriba a la izquierda y `secondary` al 16 % abajo a la derecha) y el componente decorativo `ArcaneStars` con cuatro capas de estrellas de 0.7–1.8 px en `ink` y acentos `accent`. Las capas derivan en diagonal durante 70–150 s y parpadean suavemente cada 6–14 s, con desfases entre capas. Solo se animan `transform` y `opacity`; sin canvas ni imágenes. La capa va detrás del contenido (`z-index: -1`) y no captura clics. Con `prefers-reduced-motion: reduce` las estrellas quedan fijas. En Acceso y Registro, `ArcaneStars` se reutiliza dentro del cuadro `tertiary` en posición absoluta, recortado al cuadro; la columna sigue con el cielo arcano de fondo.
- **Madera:** degradado vertical `#6B4F3A → #8B6B4F` con vetas hechas con `repeating-linear-gradient` a baja opacidad. Sin imágenes bitmap.
- **Peso:** ninguna textura debe superar 50 KB en total; preferir CSS/SVG inline.

---

## 6. Componentes

### Navegación

- **Escritorio:** barra lateral fija de 240 px, fondo `surface-raised` con borde final `edge`, texto `ink-muted`. Logo en Cormorant `accent`. Enlaces con icono y etiqueta. El botón **Cerrar sesión** va en la parte superior, bajo el logo; no se muestra el nombre real en la barra lateral.
- **Móvil:** barra inferior fija de 56 px, fondo `surface-raised` con borde superior `edge`, tres acciones: Biblioteca, Estanterías y un botón de **tres líneas** (icono de 20 px, trazo 1.6, sin texto visible; el nombre se expone con `aria-label` y `title`). Ese botón abre un panel flotante encima de la barra con Decoraciones y Configuraciones; se cierra al elegir una opción, al tocar fuera o con Escape. Añadir libro se abre desde el botón de la cabecera de la Biblioteca. Zona segura inferior respetada.
- Enlace activo: fondo `surface`, borde inicial `secondary` y texto `ink` (escritorio); texto `accent` y subrayado `secondary` (móvil).

### Botones

- **Primario:** fondo `accent` (rosado en modo claro), texto `on-accent`, radio 4 px, altura mínima 44 px. Hover y activo `accent-strong`. Foco con anillo `accent` de 2 px y offset `page`. Variante **Entrar** (`tone="orquidea"`): fondo `tertiary`, texto `on-accent`, hover al 85 % y activo al 75 %.
- **Secundario:** fondo `surface`, borde `edge`, texto `ink`. Hover `surface-raised`.
- **Peligro:** borde y texto `danger`; en confirmaciones, fondo `danger-strong` y texto `on-accent`.
- **Icono:** cuadrado de 44 × 44 px, sin borde, hover con fondo `surface`.
- **Enlaces:** `secondary`; hover `accent`.

### Inputs

- Fondo `surface`, borde 1 px `edge`, radio 4 px, altura 44 px.
- Texto `ink`; placeholder `ink-muted`.
- Foco: borde `secondary` + anillo `secondary`.
- Error: mensaje `small` en `danger` debajo.
- Archivos: botón nativo en `surface-raised` con texto `ink`.
- **Campo con etiqueta flotante (`FloatingInput`):** variante para los formularios de Perfil y Contraseña de Configuraciones. Caja `surface` con borde 1 px `edge`, radio 6 px (`rounded-card`) y relleno superior de 20 px. La etiqueta va apoyada sobre el borde superior, centrada en el borde y con fondo `surface` y `padding` horizontal de 4 px para abrir el hueco; texto `text-xs` en `accent`. El placeholder `ui.write_here` queda debajo en `ink-soft`. En foco el borde pasa a 2 px `accent`; en error, borde y etiqueta `danger`. El resto de formularios (ficha de libro, acceso) conserva el estilo plano anterior.

### Tarjeta de libro (`BookCard`)

- Proporción de portada 2:3, radio 4 px, sombra suave.
- Hover: halo `dorado` de 2 px alrededor de la portada con resplandor suave, 160 ms. Sin desplazamiento ni cambio de sombra.
- La tarjeta muestra solo la portada: sin título ni autor debajo. Título y autor aparecen en el tooltip nativo y en el texto alternativo de la imagen.
- Sin portada: fondo `crema`, borde `madera` al 35 %, título y autor centrados en Cormorant como portada de sustitución.
- Portada con `alt="Portada de {título}"`.
- En la estantería **Leyendo**, la portada lleva una insignia circular arriba a la izquierda (`page` al 85 %, anillo y triángulo de reproducción `accent`) y una barra de progreso `accent` sobre una franja `page` al 70 % en la base. El clic abre el diálogo de lectura en lugar de la ficha.

### Lomo (`BookSpine`)

- Rectángulo vertical de 160 px de alto y 18–88 px de ancho. El ancho crece con la longitud del título.
- El color de fondo se toma del color dominante de la portada (similar, no idéntico): se muestrea la imagen en un canvas pequeño y se usa el tono más frecuente. Sin portada, el color sale de un hash estable del título dentro de la paleta (oliva, bosque, madera, terracota, salvia).
- El título se muestra en vertical, centrado en el lomo. Si es largo, el lomo se engrosa y el texto se reparte en varias columnas (con el cuerpo de 10 a 8 px si hace falta) para que no se corte; aun así el tooltip y el `aria-label` llevan el título completo.
- El color del texto se elige automáticamente entre `crema` y `tinta` según el brillo del fondo.
- Al pasar el cursor: halo `dorado` alrededor del lomo, sin desplazamiento, y tooltip con título y autor.
- En la estantería **Leyendo**, el lomo muestra la insignia de reproducción `accent` centrada arriba y una barra de progreso `accent` en la base; el clic abre el diálogo de lectura en lugar de la ficha.

### Objeto decorativo (`DecorationArt` + `BookcaseDecoration`)

- Quince dibujos SVG planos en un lienzo de 64 × 80: taza, rosas, planta, vela, reloj de arena, bola de cristal, lámpara, gato, calavera, jaula, astrolabio, set de té, caja de música, pluma y tintero, marco de fotos.
- La pieza principal usa el color elegido entre seis tokens existentes: `tertiary` (rosa), `crema`, `salvia` (verde), `bosque`, `accent` (dorado) y `terracota`. Los neutros salen de `madera`, `tinta` y `pergamino`; no se añaden colores nuevos.
- En la balda ocupa ~56 px de ancho (64 px desde `sm`) y se apoya en el fondo como los libros, con halo `dorado` al pasar el cursor y sombra suave.
- Al pasar el ratón aparece una **×** circular arriba a la derecha para quitarlo; en **Biblioteca** se ve siempre en **móvil** y aparece al pasar el ratón en escritorio. En **Estanterías** los objetos decorativos no se dibujan; solo se gestionan desde el módulo Decoraciones.
- `title` y `aria-label` con el nombre traducido del objeto.

### Estantería (`Shelf` + `ShelfBoard`)

- Contenedor con título `h2` y contador de libros. Cada libro decide por sí mismo si se ve como portada o como lomo; no hay control de modo por estantería.
- **Espaldar:** panel de madera oscura detrás de los libros, con veta vertical sutil y sombras interiores arriba y a los lados que simulan profundidad. Junto con la balda forma un perfil en L.
- **Balda mixta:** todos los libros de la estantería se dibujan en una sola fila respetando su orden, con portadas y lomos intercalados y alineados por la base. Las portadas ocupan ~100 px en móvil y ~110 px en escritorio; los lomos conservan su ancho calculado. En móvil la fila se desliza en horizontal (`overflow-x-auto`) sin barra de desplazamiento visible (utilidad `scrollbar-hidden`, igual que en los filtros de Decoraciones); en escritorio hace saltos de línea (`flex-wrap`). Máximo orientativo de 7 columnas de portada en escritorio.
- **Cambio rápido (escritorio y móvil):** cada libro muestra en su esquina superior derecha un botón circular con icono (portada o lomo) que alterna ese libro al instante. En escritorio aparece al pasar el ratón; en móvil queda visible siempre para poder usarlo con el dedo. Funciona igual en Estanterías y en las bandas de Biblioteca.
- **Solo libros:** la balda muestra únicamente los libros de la estantería, con portadas y lomos intercalados y alineados por la base. Los objetos decorativos se ven únicamente en la Biblioteca.
- **Balda:** volumen de 20–24 px con dos caras: superficie superior en perspectiva (`rotateX` suave, de `madera-clara` a `madera`) y frente más oscuro con bisel claro arriba, veta horizontal sutil, sombra interior en los extremos y abajo, y sombra proyectada. La cara superior lleva una sombra interior que simula el contacto de los libros. Debe parecer que sostiene los libros.
- Estado vacío: `EmptyShelf` con borde discontinuo `madera` al 35 %, frase breve en `crema` y enlace para añadir.

### Lectura en curso (`ReadingLightbox`)

- Se abre al pulsar un libro de la estantería virtual **Leyendo** en Estanterías o el botón **Registrar lectura** de la ficha.
- Modal `surface` de hasta 512 px: portada de 64 px, título en Cormorant `ink`, autor en `ink-muted` y estado de lectura en `accent` (Sin empezar, Leyendo, Terminado).
- Barra de progreso `accent` sobre `surface-raised` con el porcentaje en `accent`; debajo, «Página X de Y» y el tiempo total leído (`X h Y min`). Sin páginas totales se muestra «Sin páginas totales» y el porcentaje queda en 0.
- Cronómetro `font-mono` de 30 px con un único botón circular `tertiary` que alterna **Empezar** (triángulo) y **Pausar y guardar** (dos barras). Al pausar se guarda la sesión del día con los minutos cronometrados y la página actual si han pasado al menos 10 s; si no, se muestra el aviso «La sesión es demasiado corta; no se guardó.» en `accent`.
- Campos numéricos de página actual y páginas totales con el botón **Guardar progreso** (borde `edge`, hover `secondary`/`accent`).
- Historial en lista: fecha en `ink`, minutos y página en `ink-muted`, y una × `ink-muted` que pasa a `danger` al pasar el cursor; antes de borrar pide confirmación. Vacío: «Todavía no hay sesiones.»
- Cierre con el botón de la esquina, Escape o pulsando el fondo; el desplazamiento del fondo queda bloqueado. En móvil se ancla abajo con las esquinas superiores redondeadas; en escritorio se centra.

### Biblioteca completa (`Bookcase` + `BookcaseBand`)

- `Bookcase` envuelve las bandas con dos laterales de `madera`, cornisa clara arriba y un zócalo con dos puertas; cada banda reutiliza `shelf-case` y `ShelfBoard`, así que las baldas mantienen el respaldo de madera y la tabla con perspectiva.
- `BookcaseBand` agrupa los libros que le llegan y coloca las decoraciones ancladas a esos libros. En **móvil** se respetan las bandas guardadas de como máximo cinco libros y una decoración, con desplazamiento horizontal. En **escritorio** la página vuelve a empaquetar los libros según el ancho real disponible (`packBands`), de modo que las baldas se llenan y pueden reunir varias bandas guardadas; la regla de cinco libros por banda deja de aplicarse visualmente. Cada libro se dibuja como `BookSpine` o `BookCard` según su `view_mode`; ambos conservan el enlace a la ficha, el asa de arrastre y el marcador de inserción. La página usa `useLibraryDrag` en modo `virtual`: las bandas son los destinos y la página traduce la caída a `PATCH /bookcase/move` (banda y posición guardadas, con cascada en el servidor) mediante `insertBookInBand`, que aplica la misma cascada de forma optimista. Las decoraciones usan `BookcaseDecoration`, que lleva una **×** en la esquina superior derecha para quitar el objeto: en **escritorio** aparece al pasar el ratón y en **móvil** se ve siempre, con el mismo comportamiento que el botón de cambiar a lomo/portada. Al pulsarla borra la decoración sin confirmación y sin activar el arrastre.
- `visualDropTarget` traduce la posición visual (antes o después de un libro) a la banda y posición guardadas. Si el ancla es el último libro de una banda llena, el libro nuevo pasa a la banda siguiente para conservar el orden visual.
- La banda es la unidad de destino del arrastre (`data-shelf-id`), igual que una estantería en `Shelves/Index`.

### Temáticas estacionales (`SeasonalDecor`)

- La app se decora sola según el mes, con **doce temas distintos** (tabla más abajo). No hay selector; el mes manda y el tema se resuelve en `resources/js/seasons.js`.
- La raíz de `AppShell` expone `data-season` y monta `SeasonalDecor`, una capa fija `aria-hidden` con `pointer-events: none` por encima del contenido. La barra lateral y el contenido van con `relative z-10` y la barra móvil con `z-20`.
- La capa incluye una **guirnalda fija** en el borde superior en escritorio (a partir de los 240 px del menú lateral) con 8 adornos, y una **enredadera** vertical (en el borde derecho de la pantalla en móvil y en el borde derecho del menú en escritorio) con 10 hojas. En móvil la guirnalda cuelga de la propia cabecera y se desplaza con ella al hacer scroll, para no quedar flotando sobre el título. En Halloween las piezas de la guirnalda son murciélagos.
- Las **guirnaldas y la enredadera son fijas**; además, las hojas de otoño, los copos, los pétalos o las motas caen lentamente **por detrás del contenido** (capa `seasonal-particles`, `z-index: 0`, opacidad baja), de modo que se ven en el fondo sin tapar libros ni textos. En Halloween no hay partículas.
- Los adornos son **fijos**, sin movimiento: nada cae sobre el contenido. Cada banda de la Biblioteca cuelga además su propia **guirnalda compacta** del borde superior, con 8 piezas pequeñas que cambian según la temporada.
- Todos los meses usan la misma estructura (guirnalda superior, enredadera, guirnalda por banda y partículas de fondo) y cambian la forma y la paleta:

| Mes | Tema | Partículas | Adornos y paleta |
|---|---|---|---|
| Enero | Invierno | Copos | Copos; `ink`, `ink-muted`, `crema`, `accent` |
| Febrero | San Valentín | Corazones | Corazones; `danger`, `tertiary`, `crema`, `accent` |
| Marzo | Primavera | Pétalos | Pétalos; `salvia`, `tertiary`, `crema`, `accent` |
| Abril | Lluvia | Gotas | Gotas; `ink-muted`, `salvia`, `ink`, `crema` |
| Mayo | Flores | Pétalos | Pétalos; `tertiary`, `accent`, `salvia`, `crema` |
| Junio | Verano | Motas | Motas; `accent`, `dorado`, `crema`, `salvia` |
| Julio | Sol | Estrellas | Estrellas; `accent`, `terracota`, `dorado`, `crema` |
| Agosto | Girasoles | Motas | Motas; `dorado`, `oliva`, `accent`, `crema` |
| Septiembre | Vendimia | Hojas | Hojas; `terracota`, `oliva`, `madera-clara`, `accent` |
| Octubre | Halloween | — | Murciélagos, telarañas y arañas; `tertiary`, `terracota`, `crema`, `accent` |
| Noviembre | Otoño | Hojas | Hojas; `terracota`, `oliva`, `accent`, `madera-clara` |
| Diciembre | Navidad | Copos | Bolas; `crema`, `danger`, `success`, `accent` |

- Cada tema define además su resplandor de fondo en `.arcane-sky[data-season="…"]::after` con los mismos tokens. Cambiar de mes no requiere tocar componentes: basta con que `seasons.js` devuelva la clave del mes.

### Arrastrar libros entre estanterías

- Origen: cualquier `BookCard`, `BookSpine` o `ShelfDecoration`. En escritorio se arrastra con el botón principal y el gesto se activa tras 6 px. En móvil se mantiene pulsado 320 ms y después se arrastra; si hay un desplazamiento mayor de 8 px antes de ese tiempo, el gesto se cancela y la página puede hacer scroll.
- Al activarse, el libro de origen se atenúa al 30 %, el cursor pasa a `grabbing` y aparece una miniatura flotante sobre el puntero: la portada (76 px de ancho) o el lomo (28 × 148 px) según cómo se muestre ese libro. La miniatura no intercepta eventos y lleva un resplandor `secondary` suave.
- Todas las estanterías se delinean con un trazo discontinuo `secondary` al 28 %. La estantería bajo el puntero usa un trazo continuo de 2 px `secondary`, un resplandor exterior `secondary` al 24 % y la etiqueta **Suelta aquí** en `secondary` con texto `page`.
- Cerca de los bordes superior o inferior de la ventana, la página se desplaza automáticamente hasta 18 px por fotograma mientras se mantiene el arrastre.
- Soltar fuera de una estantería, en el mismo hueco o pulsar Escape cancela el movimiento sin hacer peticiones ni abrir la ficha.
- Al soltar en otra estantería, el cambio se aplica de forma optimista y se anuncia con un aviso `role="status"`; si la petición falla, el libro vuelve a su posición y se muestra el aviso en `danger`.
- Los objetos decorativos usan el mismo gesto: se pueden soltar entre dos libros, antes del primero, al final o en otra estantería. La miniatura flotante muestra el propio objeto.

### Reordenar libros dentro de una estantería

- Al arrastrar dentro de la misma estantería, aparece una línea vertical de 2 px en `secondary` con resplandor suave en el hueco donde quedará el libro.
- La línea se dibuja antes o después del libro bajo el puntero según su mitad horizontal. Si se suelta en la zona vacía, el libro va al final.
- Soltar en la misma posición no hace ninguna petición.
- El orden se guarda por libro y se mantiene al recargar. Los libros nuevos y los que llegan de otra estantería se añaden al final.
- El cambio es optimista y reversible: si falla, se restaura el orden anterior y se muestra un aviso `danger`.
- La reordenación se hace solo por arrastre; no se añade un campo de posición en la ficha.

### Valoración (`RatingStars`)

- Cinco escalas independientes por libro, cada una de 0 a 5: **estrellas** (`rating`, `accent`), **picante** (`spice_rating`, `danger`), **corazón** (`heart_rating`, `tertiary`), **gotas** (`drop_rating`, `ink-muted`) y **bola de cristal** (`crystal_rating`, `secondary`).
- Iconos SVG de 16 px con `currentColor`: estrella, chile, corazón, gota y bola de cristal. Los valores alcanzados usan el color de la escala y los vacíos el mismo color al 40 %.
- Editable con clic y accesible: cada grupo expone su nombre (`Estrellas`, `Picante`, `Corazón`) y cada botón su valor solo para lectores de pantalla; no hay textos visibles en el bloque.
- Se editan en la ficha del libro (`Books/Edit`) y en el alta (`Books/Create`), como tres filas de iconos sin etiquetas visibles.

### Modales

- Panel `surface`, borde `edge`, radio 12 px, sombra de modal, ancho máximo 560 px (640 px para el buscador).
- Fondo oscurecido `page` al 80 % con desenfoque suave.
- Título en Cormorant `h2` color `accent`; cierre con botón y tecla Escape.
- En móvil ocupan el ancho completo con márgenes de 16 px.

### Toasts

- Fondo `surface-raised`, borde `edge`, texto `ink`, radio 6 px, duración 4 s.
- Éxito con acento `success`; error con acento `danger`.
- `aria-live="polite"`.
- El aviso global de éxito se muestra flotando abajo, centrado, con ancho máximo de 384 px y sin capturar clics. Al guardar la ficha de un libro aparece «Libro actualizado.».

### Intro de bienvenida (`MagicWelcome`)

- Velo a pantalla completa `page` al 80 % con desenfoque, aura radial `tertiary`, `secondary` y `accent`, y diez destellos dorados flotando en los bordes.
- Placa de cristal centrada de hasta 480 px: fondo `surface` al 70 %, borde `secondary` al 30 %, desenfoque, sombra de modal con halo arcano y filo superior dorado.
- Sello circular con estrella `accent`, título «Bienvenida, {apodo}» en Cormorant `accent` y subtítulo «Tu mundo mágico te espera.» en `ink-muted`.
- Botón **Entrar** en `tertiary`, con resplandor suave y hover `tertiary-strong`.
- Aparece una vez por carga de la aplicación autenticada: al iniciar sesión y al abrir o recargar. No se repite al navegar entre Biblioteca y Ajustes.
- Se cierra con **Entrar** o Escape; el foco inicial está en el botón, Tab se mantiene dentro de la intro y el desplazamiento del fondo queda bloqueado.
- En móvil ocupa el ancho disponible con 16 px de margen. Con `prefers-reduced-motion: reduce`, sin animaciones.

### Emblema de acceso (`LibraryEmblem`)

- Un único lienzo SVG de 560 × 480 que compone toda la escena; el arco y el libro comparten coordenadas y no hay piezas recortadas ni placas superpuestas.
- Arco «BIBLIOTECA» en mayúsculas, Cormorant Garamond 600 a 70 px sobre `textPath` (curva `M 24 132 Q 280 4 536 132`), relleno en degradado `accent`/`dorado` y doble sombra dorada para leerse sobre el cielo.
- Libro abierto en 3D visto desde arriba y en perspectiva: tapas `page`/`surface` con canto de cartón visible, filo dorado y filigrana en la esquina delantera; bloque de páginas `crema`/`pergamino` con canto apilado de líneas finas y sombra proyectada sobre la tapa; páginas curvadas con degradado hacia el lomo, sombra interior en los bordes, brillo de canto y grano de papel.
- Lomo en valle: pliegue oscuro central, dos luces paralelas y pozo de sombra en el pie, sin sello ni ilustraciones impresas.
- Sin letras manuscritas, sellos, astrolabios, chispas ni haz de luz: sobre las páginas solo queda el papel.
- Sombra de contacto doble bajo el libro y halo `accent`/`tertiary` difuminado detrás.
- Tamaño máximo 480 px de ancho en escritorio y 214 px en la versión compacta, siempre con proporción 560:480. Ningún SVG puede quedar sin ancho definido: eso estiraba el dibujo a todo el contenedor.
- Ids SVG únicas por instancia (`useId`) para que la versión compacta y la de escritorio convivan en el mismo documento.
- Movimiento: solo respiración del halo y pulso del resplandor dorado. Con `prefers-reduced-motion: reduce` queda estático.

### Esqueletos de carga

- Bloques `ink-muted` al 20 % con animación de brillo de 1.2 s. Con `prefers-reduced-motion`, sin animación.

---

## 7. Estructura de pantallas

### Biblioteca (`Library/Index`)

1. Sin cabecera propia: la Biblioteca muestra directamente el mueble, sin título ni botón **Añadir libro**.
2. Un mueble único con todos los libros de la cuenta en bandas de cinco lomos y como máximo una decoración por banda. Cada libro guarda su banda (`band`) y su posición dentro de ella (`band_position`), así que las bandas son estables: si se saca un libro, la banda queda con menos libros y los de las bandas siguientes no suben. Cada banda es una balda de madera con su respaldo y su tabla, y el mueble crece con las bandas necesarias; los laterales, la cornisa y el zócalo de dos puertas reproducen un armario alto.
3. Al insertar un libro en una banda llena, el último libro de esa banda baja a la siguiente y esta repite la operación hacia abajo (cascada), de modo que nunca hay más de cinco libros por banda. Las bandas vacías no se dibujan.
4. Los libros mantienen el tamaño de Estanterías: lomo de 160 px con su ancho calculado y portada de 100 px en móvil y 110 px en escritorio. En móvil la fila se desliza en horizontal sin barra visible (utilidad `scrollbar-hidden`) y en escritorio se centra. Las decoraciones se muestran en la banda de su libro ancla; si esa banda ya tiene una, pasan a la siguiente banda con hueco (o a una banda nueva), de modo que nunca hay más de una por banda. Las que no tienen ancla van al principio de la primera banda.
5. Cada libro respeta su modo de vista, lomo o portada, y se pueden mezclar en la misma banda igual que en Estanterías. Al pulsar un lomo o una portada se abre la ficha.
6. Los libros se arrastran con el mismo gesto que en Estanterías (6 px con ratón, 320 ms con el dedo): al soltar antes o después de un libro, el libro se inserta en esa posición exacta de esa banda y, si la banda se llena, la cascada baja el sobrante. Se puede mover un libro hacia cualquier banda, incluida una anterior. Si se suelta sobre la banda, la mitad izquierda inserta antes del primer libro y la derecha después del último.
7. Las decoraciones también se arrastran entre bandas: se colocan antes o después del libro u objeto bajo el puntero y, si cambian de estantería, se guarda la nueva ancla. El aviso de éxito llega desde el servidor.
8. Estado vacío propio cuando no hay libros: “Aún no hay libros en tu biblioteca.”

### Estanterías (`Shelves/Index`)

1. Sin título de página: solo el botón **Añadir libro**, alineado a la derecha.
2. Sobre las estanterías reales aparece la estantería virtual **Leyendo** con los libros en curso, sin objetos, sin arrastre y sin botón de cambio de vista; al pulsar un libro se abre el diálogo de lectura. Si no hay libros en curso, la estantería no se muestra.
3. Lista de estanterías reales en orden, cada una con su balda, sus libros y sus objetos.
4. Arrastrar una portada o un lomo a otra estantería para mover el libro, o dentro de la misma estantería para cambiar su posición; en móvil, pulsación larga y arrastre.
5. Las estanterías son fijas: sus nombres y su distribución no se editan desde la interfaz.

### Máquina expendedora (`Cards/Index`)

- **Navegación:** tercer destino **Máquina expendedora** (`ui.nav_cards`) en la barra lateral y en el menú móvil, entre Estanterías y Decoraciones, con icono SVG de máquina expendedora (cuerpo, ventana, botones y trampilla). La página no tiene cabecera con título: empieza directamente con la máquina; el título en la pestaña del navegador sigue siendo `ui.cards_title`. No muestra botón para ir al álbum: el álbum tiene su propia entrada de navegación independiente (`/album`).
- **Bombo (`CardMachine`):** máquina expendedora vintage renderizada en **3D real con Three.js** (carga diferida, escena propia en `card-machine__scene`): **iluminación por entorno** (`RoomEnvironment` + PMREM) que da reflejos metálicos de verdad al oro (`metalness` 0.88) y a las perlas de porcelana (`MeshPhysicalMaterial` con clearcoat); cámara en ángulo (30°, ligeramente arriba a la derecha) y máquina girada −0.07 rad para volumen; **foco cálido** sobre el frontal, luz interior en la ventana y halo de fondo aditivo. Cuerpo con **bisel perimetral** (caja exterior más grande detrás), tapa, costado oscuro, ventana con **agujero real**, marco extruido, cristal y grabados por textura de lienzo (marcos, curlas, libro, destellos). Columna derecha con **moneda «!»** arriba y **palanca de varilla con pomo esférico** debajo; **compartimento inferior con trampilla bisagrada**. **Flujo de gachapon:** no se elige perla; al pulsar la manivela la máquina escoge una perla al azar (emite `crank` con el índice), la palanca desciende y regresa con rebote, el cuerpo vibra, las perlas se agitan, la perla elegida **cae por la trampilla** (se abre, espera y se cierra) y aparece el sobre con la carta nueva o el sticker nuevo. Sin WebGL (o en pruebas) el componente degrada sin escena; los controles siguen siendo botones accesibles (`aria-label`, foco visible) y `prefers-reduced-motion` amortigua las oscilaciones.
- **Sobre (`CardEnvelope`):** papel `crema` (`.card-envelope`) con solapa triangular (`.card-envelope__flap`), sello de lacre `danger-strong` (`.card-envelope__seal`) con el emblema de familia y etiqueta de apertura `card-envelope__label`. El monograma del personaje no se muestra en el sobre: se reserva para la carta y el álbum. El sobre solo aparece cuando la bolita contiene una carta nueva; no hay cartas repetidas ni tinta por duplicados.
- **Stickers de la máquina (`LibrarySticker`):** 20 objetos de biblioteca (libro, pluma, tintero, taza, gato, luna, vela, planta, lupa, llave, gafas, marcapáginas, reloj, búho, poción, pergamino, máquina de escribir, rosa, cuervo y cristal) dibujados en SVG con ilustración detallada de estilo cohesivo, sombreado suave y colores naturales. Cada sticker tiene su propia silueta troquelada (`.library-sticker__cut`) con borde blanco, sombra suave y fondo blanco limpio. La versión bloqueada reutiliza la misma silueta en `surface-raised` con un `?` (`.library-sticker__locked`). Al sacar uno, la máquina muestra el dibujo a 170 px, su nombre y el mensaje `ui.cards_sticker_new`.
- **Colección completa:** cuando ya no quedan cartas ni stickers por obtener, la máquina oculta la manivela y muestra `ui.cards_complete` y `ui.cards_complete_help` sobre una tarjeta `surface` con borde `edge`.
- **Carta (`CardLetterModal`):** hoja `pergamino` (`.card-letter`) con borde `dorado` y renglones sutiles; cabecera mínima con solo «Carta de {personaje}» y botón **Cerrar** (sin emblema, monograma ni familia); cuerpo `tinta` en Cormorant con interlineado 1.9 y capitular `dorado` (`.card-letter__dropcap`); pie con «Carta nueva» y «×N» si hay copias. Cartas de ~120 palabras (rango 120–160), tres párrafos breves con firma y posdata opcional. Modal `surface` de hasta 2xl.
- **Álbum (`CardAlbum`):** gabinete de madera (`.card-album`) con barra de progreso `accent` y texto «N de M cartas». Rejilla de 2 columnas (3 en tablet, 4–5 en escritorio) de ranuras `card-slot`: desbloqueadas como botón con emblema, monograma, personaje y familia; bloqueadas como reverso geométrico `card-back` con `?`, sin clic, con la pista en `title`. Pie con el aviso de derechos. Debajo, la sección **Stickers de la máquina** con progreso propio y rejilla de `.sticker-slot`: 3 columnas a 360 px, 4 en tablet y 5–6 en escritorio; cada ranura muestra el `LibrarySticker` a 96 px y el nombre o «Bloqueado».
- **Estados:** sin libros la máquina sigue disponible solo con stickers; el aviso `cards_empty_help` invita a añadir un libro para desbloquear cartas. Errores de tirada y apertura en aviso `danger` con `role="alert"`; región `aria-live="polite"` para el resultado; cuenta atrás «Vuelve en Xh Ymin» bajo el sobre.
- **Responsive:** 360 px bombo a ancho completo con balotas de 44 px; 768 px dos columnas (bombo y álbum) y cúpula más ancha; 1280 px álbum hasta 5 columnas.
- **Accesibilidad:** balotas y manivela con `aria-label`, balota elegida con `aria-pressed`, álbum con `progressbar`, ayuda de tinta en `sr-only`, cierre de modal con Escape. Con `prefers-reduced-motion: reduce` se desactivan la caída de la balota, el giro de la manivela, la solapa del sobre y las partículas.
- **Clases nuevas en `textures.css`:** `card-machine`, `card-machine__base`, `card-machine__dome`, `card-machine__ball` (+`--selected`, `--drop`), `card-machine__crank` (+`--turning`), `card-machine__chute`, `card-envelope` (+`__flap`, `__seal`, `__label`), `card-letter` (+`__body`, `__dropcap`), `card-album`, `card-slot`, `card-back`, `card-monogram`, `card-ink`, `library-sticker`, `library-sticker__svg`, `library-sticker__cut`, `library-sticker__art`, `library-sticker__locked`, `library-sticker--locked`, `sticker-slot`. Tokens usados: `madera`, `madera-clara`, `pergamino`, `crema`, `tinta`, `dorado`, `page`, `surface`, `surface-raised`, `edge`, `ink`, `ink-muted`, `accent`, `danger`, `danger-strong`. No se añaden colores nuevos.

### Álbum de colección (`Album/Index`)

- **Navegación:** entrada propia **Álbum** (`ui.nav_album`) en la barra lateral y en el menú móvil, separada de la Máquina expendedora; se puede abrir sin pasar por la máquina.
- **Página (`Album/Index`):** muestra las cartas desbloqueadas y los stickers de máquina obtenidos en una misma rejilla tipo álbum de estampas. Cabecera con título `ui.album_title` y ayuda `ui.album_subtitle`.
- **Barra de progreso (`AlbumProgress`):** contador "N de M" con barra `accent` y desglose por rareza (`ui.album_rarity_*`). Total 40 piezas (20 cartas + 20 stickers de máquina).
- **Filtros (`AlbumFilter`):** pestañas Todas / Cartas / Stickers (`ui.album_filter_*`).
- **Tarjetas (`AlbumSlot`):** bordes troquelados blancos (`library-sticker__cut`), sombra suave, efecto peel-off al hover; bloqueadas muestran candado y `???` sin clic.
- **Detalle en modal (`AlbumDetailModal`):** ilustración grande, nombre, categoría, rareza con color, número y fecha de obtención; cierre con Escape y clic en el velo.
- **Estados:** progreso y filtro funcionan solo con objetos del usuario autenticado; nunca mezcla datos de otras cuentas.
- **Clases nuevas en `textures.css`:** ninguna nueva obligatoria (reusa `library-sticker`, `card-slot`, tokens existentes). No se añaden colores nuevos.

### Añadir libro (`Books/Create`)

- Página centrada a `max-w-3xl`, con cabecera de título y enlace **Volver** a Estanterías, igual que la ficha del libro.
- Pestañas **Buscar** y **Manual** bajo la cabecera.
- En **Buscar**: escáner, ISBN, búsqueda por título/autor y resultados.
- En **Manual** se reutiliza el formato de la ficha del libro: portada de 96 px en móvil y 180 px en escritorio con botón circular de cámara sobre su esquina superior derecha; a su derecha, título, autor, ISBN, editorial, año y estantería destino; debajo, las cinco valoraciones; después, notas y el botón **Guardar**. No incluye imágenes adicionales, lectura ni eliminar porque el libro aún no existe.

### Ficha del libro (`Books/Edit`)

- Portada de 96 px en móvil y 180 px en escritorio, con un botón circular de cámara sobre su esquina superior derecha para cambiarla.
- A la derecha de la portada, compactos, los cinco datos: título, autor, ISBN, editorial y año.
- Debajo, la valoración en cinco filas de iconos (estrellas, chiles, corazones, gotas y bolas de cristal) y, a su derecha, **tres imágenes del libro** de 112 px de alto, que ocupan el ancho libre en móvil (unos 56–58 px) y 96 px en escritorio, ligeramente inclinadas (`-6°`, `3°` y `-2°`) para un aire de álbum y alineadas con la columna de valoración. Cada ranura admite una imagen JPG, PNG o WebP de hasta 5 MB, se puede reemplazar y quitar con la ×.
- Las imágenes se guardan en `storage/app/public/book-images` y se sirven por la ruta autenticada `/book-images/{image}`.

### Decoraciones (`Decorations/Index`)

- Módulo propio, con entrada **Decoraciones** en la barra lateral y en la barra inferior móvil, entre la Máquina expendedora y el Álbum.
- Título “Decoraciones” y una línea de ayuda. Después, una barra de filtros en la parte superior con seis botones circulares de 48 px: **Todos**, **Mesa y té**, **Flores y plantas**, **Luz y velas**, **Curiosidades** y **Mascotas y recuerdos**. Cada círculo contiene únicamente un icono de línea de 24 px trazado con `currentColor` (retícula, taza, flor, vela, destello y corazón), sin texto visible; el nombre de la categoría se expone con `aria-label` y `title`. En móvil la barra se desliza en horizontal sin barra de desplazamiento visible (utilidad `scrollbar-hidden`, que oculta el scroll nativo pero mantiene el gesto) y en escritorio pasa a varias líneas.
- El botón activo usa borde `secondary`, fondo `surface` y texto `accent`, con `aria-pressed`. Al elegir una categoría la cuadrícula muestra solo sus objetos; **Todos** muestra los quince en una sola cuadrícula, sin encabezados de sección. Hasta 3 columnas en escritorio, 2 en tablet y 1 en móvil.
- **Mesa y té:** taza, set de té y caja de música. **Flores y plantas:** rosas y planta. **Luz y velas:** vela y lámpara. **Curiosidades:** reloj de arena, bola de cristal, calavera, jaula, astrolabio, pluma y tintero. **Mascotas y recuerdos:** gato y marco de fotos.
- Cada tarjeta repite el patrón del catálogo: dibujo y botón. El nombre del objeto va dentro del recuadro, en el borde izquierdo y en vertical (mismo `writing-mode: vertical-rl` que los lomos), centrado, en Cormorant `accent` y con una pastilla `page` al 60 % detrás. El recuadro mide 128 px de alto y reserva 40 px a la izquierda para el nombre, así la etiqueta acompaña al dibujo tanto en los objetos altos como en los bajos. Dentro del recuadro, en su esquina superior derecha, hay un círculo de 24 px con el color actual; al pulsarlo se despliega un pequeño panel flotante de 3×2 con los seis colores, y al elegir uno el dibujo cambia y el panel se cierra. El color elegido se mantiene al cambiar de categoría.
- Si ese objeto ya está colocado, el botón **Añadir** pasa a **Quitar** (borde y texto `danger`) y elimina la última copia añadida de ese tipo. No hay listado de objetos colocados ni botón **Quitar todas**.
- La sección deja de mostrarse en Configuraciones, que vuelve a quedar en Perfil, Contraseña e Idioma.

### Configuraciones

- Secciones apiladas en tarjetas `surface` con borde `edge`, radio 6 px y relleno 16 px: Perfil, Contraseña e Idioma.
- **Perfil:** nombre real, apodo y correo. El apodo es obligatorio, admite hasta 50 caracteres y es el que aparece en la intro de bienvenida. La vista de portada o lomo no se configura aquí: se cambia por libro en Biblioteca o Estanterías.
- Guardado por sección con confirmación visual.
- **Idioma:** selector simple, sin buscador, con solo dos opciones revisadas: **Español** e **Inglés** (etiqueta “Revisado”). Al elegir un idioma se aplica directamente, sin progreso de preparación.

### Autenticación

- Diseño a dos columnas a altura completa en escritorio: cielo arcano con el emblema de la biblioteca a la izquierda (arco «BIBLIOTECA» sobre un libro abierto en 3D) y formulario a la derecha sobre `surface-raised`. Las dos columnas se centran verticalmente en la misma línea, sin que la página entera se desplace; si el formulario no cabe, solo esa columna tiene desplazamiento interno.
- Emblema sin placa de cristal ni rectángulos: el libro flota sobre el cielo.
- La columna del emblema mantiene el cielo arcano. Encima flota un cuadro redondeado de hasta 528 px con fondo `tertiary`, degradado suave hacia `ink` en el centro y `page` en el borde inferior, filo dorado y sombra, que contiene el título y el libro con estrellas en movimiento dentro. En móvil, la franja superior muestra el mismo cuadro en versión compacta. La columna del formulario sigue en `surface-raised`.
- Móvil: franja superior de 14rem con el emblema compacto; debajo, el formulario.
- Tarjeta interna de 420 px máximo, título en Cormorant `accent` y texto de apoyo en `ink-muted`.
- Campo de contraseña con botón de ojo para mostrar u ocultar (`PasswordInput`); el botón usa `ink-muted`/`ink` y anillo `secondary`.
- Botones sociales Google y Facebook (`SocialLoginButtons`): visibles en Login y Registro; deshabilitados con la etiqueta «Próximamente» mientras no haya credenciales. Separador «o continúa con» con líneas `edge`.
- Tras el primer acceso social, pantalla `CompleteNickname` para elegir apodo antes de entrar a la biblioteca.
- Enlaces en `secondary`; errores en `danger`. Los errores flash de OAuth se muestran en una alerta superior del layout.
- Se mantiene la paleta existente (`page`, `surface-raised`, `surface`, `edge`, `ink`, `ink-muted`, `accent`, `secondary`, `tertiary`); no se introducen azules ni blancos de las referencias externas.
- **Tema:** selector visual tipo toggle con sol (naranja, modo claro) y luna (azul, modo oscuro) mediante `ThemeSwitch`. El círculo se desplaza de un lado a otro y el fondo del switch se adapta al tema activo (`--color-theme-sun` y `--color-theme-moon`, colores físicos idénticos en ambos temas). La elección se persiste en la cuenta (`users.theme`) y se aplica al `<html>` mediante `data-theme`.

---

## 8. Responsive

| Rango | Comportamiento |
|---|---|
| < 640 px | 1–2 portadas por fila; navegación inferior; padding 16 px |
| 640–767 px | 2–3 portadas; navegación inferior |
| 768–1023 px | 3–4 portadas; barra lateral; padding 24 px |
| 1024–1279 px | 5–6 portadas |
| ≥ 1280 px | hasta 7 portadas; contenido centrado a 1200 px |

- Áreas táctiles mínimas de 44 × 44 px.
- El escáner ocupa el ancho disponible con relación 4:3 y botón de cierre visible.

---

## 9. Movimiento

- Transiciones de 150–200 ms con `ease-out`.
- Hover de portada o lomo: halo `dorado` de 2 px con resplandor suave; sin elevación ni desplazamiento.
- Aparición de modales: opacidad + escala de 0.98 a 1.
- La miniatura de arrastre sigue al puntero sin transición; la atenuación del libro de origen y el resplandor del destino usan 140–160 ms.
- La intro de bienvenida entra con opacidad y escala suave; el aura respira y los destellos parpadean de forma lenta y desfasada.
- Respetar `prefers-reduced-motion: reduce`: sin transformaciones ni brillos animados.

---

## 10. Accesibilidad

- Contraste AA en texto y controles.
- Foco visible con anillo `secondary` de 2 px y offset `page`.
- Navegación completa con teclado; modales con trampa de foco y Escape.
- La intro de bienvenida es un diálogo modal: foco inicial en **Entrar**, cierre con Escape y bloqueo del desplazamiento del fondo.
- `aria-label` en botones de icono, estrellas y lomos con título recortado.
- Textos alternativos descriptivos en portadas.
- No usar color como único indicador de estado.
- Arrastrar es una mejora progresiva: la ficha del libro mantiene el selector **Mover a otra estantería** para teclado y lectores de pantalla. La ayuda del gesto se anuncia con un texto oculto y el resultado con `role="status"`.

---

## 11. Tono y textos

- Idioma: la interfaz se muestra en español o inglés, a elección de la usuaria (español por defecto). Los textos de acceso, registro y recuperación permanecen en español.
- Tono cálido, breve y literario, sin exagerar.
- Los catálogos de español e inglés están revisados a mano; no hay traducciones automáticas.
- Botones: “Añadir libro”, “Guardar”, “Mover a…”, “Eliminar estantería”.
- Estados vacíos: una frase corta, por ejemplo “Aún no hay libros en esta estantería.”
- Errores claros y sin tecnicismos: “No encontramos ese ISBN. Puedes añadirlo manualmente.”
- Fechas y números en formato español.

---

## 12. Convenciones técnicas de estilo

- Tokens definidos como variables CSS en `resources/css/app.css` y expuestos a Tailwind 4 con `@theme`.
- No usar colores hex sueltos en componentes; siempre tokens.
- Componentes Vue con `<script setup>` y nombres en PascalCase.
- Clases Tailwind ordenadas por utilidad y variantes; estilos compartidos en componentes, no duplicados.
- Las fuentes se autoalojan con `@fontsource/cormorant-garamond` e `@fontsource/inter`.
- Las texturas viven en `resources/css/textures.css` o como SVG inline reutilizable.
- Para idiomas de derecha a izquierda se usan utilidades lógicas (`ps-*`, `pe-*`, `ms-*`, `me-*`, `border-s-*`, `text-start`) en lugar de `left`/`right`. El atributo `dir` del documento lo fija el servidor según el idioma.

---

## 13. Registro de cambios

| Fecha | Cambio | Autor |
|---|---|---|
| 2026-10-22 | Creación del documento. Paleta, tipografía, componentes, estructura responsive, accesibilidad y tono iniciales. | Diseño aprobado con la usuaria |
| 2026-10-22 | Implementación inicial de tokens, fuentes autoalojadas, texturas de papel y madera, y layout base responsive. Se añaden tokens de sombra `soft`, `card`, `cover` y `modal`. | Agente |
| 2026-10-22 | Biblioteca visual: estanterías sobre baldas de madera, tarjetas de portada con caída al pasar el cursor, lomos con ancho y color deterministas, estado vacío con borde discontinuo. La navegación de Biblioteca apunta a la portada autenticada. | Agente |
| 2026-10-22 | Añadir libro con pestañas Buscar/Manual, escáner de cámara, valoración y notas, resultados de búsqueda, edición de libro con valoración por estrellas, y Configuraciones con perfil, contraseña y gestor de estanterías. Navegación móvil de tres destinos (Biblioteca, Añadir, Ajustes) con áreas táctiles de 44 px y cierre de sesión visible. Ajustes responsive a 360 px y respaldo visual para portadas rotas. | Agente |
| 2026-10-22 | Pantallas de acceso, registro, recuperación y verificación en español, con enlace visible para crear cuenta, botones y campos con tokens del sistema. Validaciones del servidor traducidas al español. | Agente |
| 2026-10-22 | La cámara del escáner deja de abrirse automáticamente en Añadir libro: ahora es una opción que la usuaria abre y cierra cuando la necesita. | Agente |
| 2026-10-22 | Los errores de búsqueda muestran un mensaje claro en español en lugar del error técnico de cURL. Se añade un bundle de certificados CA al proyecto para las consultas HTTPS locales. | Agente |
| 2026-10-22 | Búsqueda en vivo en Añadir libro: al escribir al menos 3 letras del título o autor, los resultados aparecen solos tras una pausa breve, cancelando búsquedas anteriores. Se añade el texto de ayuda y el estado "Buscando…". | Agente |
| 2026-10-22 | Los resultados de búsqueda muestran la portada a la izquierda del título, con inicial del título como respaldo si la imagen no carga, además de autor, editorial y año para identificar la edición. | Agente |
| 2026-10-22 | Se mejora la cobertura de portadas en los resultados: Open Library aporta la portada por edición cuando falta la imagen principal y el navegador reintenta con la portada por ISBN si la primera falla. | Agente |
| 2026-10-22 | Los resultados de búsqueda muestran el título en español aunque la edición encontrada esté en otro idioma. Se traduce el título al español, se conserva el original para referencia y se evita traducir los títulos que ya están en español. | Agente |
| 2026-10-26 | Selector de idioma en Configuraciones (revisados y automáticos, con búsqueda y progreso), interfaz traducida con textos desde el catálogo compartido, nombres de estanterías traducidos solo al mostrarse y soporte RTL con utilidades lógicas. El español sigue siendo el idioma por defecto y el de las pantallas de acceso. | Agente |
| 2026-10-26 | Las tarjetas de libro muestran solo la portada: se retira el título y el autor que aparecían debajo. Ambos quedan en el tooltip nativo y en el texto alternativo. | Agente |
| 2026-10-26 | Se retira “Añadir” de la navegación lateral e inferior: el alta de libros se hace desde el botón de la cabecera de la Biblioteca. La navegación queda en Biblioteca y Configuraciones. | Agente |
| 2026-10-26 | Las baldas ganan volumen 3D: superficie superior en perspectiva, frente biselado más oscuro, sombra interior de contacto de los libros y sombra proyectada. | Agente |
| 2026-10-26 | Los lomos muestran el título en vertical y toman un color similar al dominante de la portada, con texto claro u oscuro según el brillo. Sin portada se mantiene el color determinista de la paleta. | Agente |
| 2026-10-26 | Se retira la elevación al pasar el cursor sobre portadas y lomos. En su lugar aparece un halo `dorado` con resplandor suave que indica el libro señalado. | Agente |
| 2026-10-26 | Cada estantería pasa a tener espaldar de madera oscura con veta vertical y sombras interiores; junto con la balda forma un perfil en L y da profundidad al mueble. | Agente |
| 2026-10-26 | El lomo crece de ancho según la longitud del título forzando varias columnas verticales centradas, para que el nombre no se corte. | Agente |
| 2026-10-26 | «Noche arcana»: nueva paleta violeta, oro y turquesa para toda la interfaz, con fondo de cielo estrellado, navegación nocturna, tarjetas y modales `panel`, acción principal `arcane` y foco `magic`. El mueble de las estanterías, las portadas y los lomos conservan su paleta y sus reglas; solo el texto del estado vacío pasa a `crema` para leerse sobre el espaldar oscuro. | Agente |
| 2026-10-27 | El cielo arcano gana movimiento sutil: cuatro capas de estrellas con deriva diagonal lenta y parpadeo desfasado, solo con `transform` y `opacity`, detrás del contenido y sin capturar clics. Con `prefers-reduced-motion: reduce` quedan fijas. | Agente |
| 2026-10-27 | Arrastrar y soltar libros entre estanterías: gesto con ratón o pulsación larga en móvil, miniatura de portada o lomo, resaltado de destinos con «Suelta aquí», auto-scroll en los bordes, actualización optimista con aviso `role="status"` y reversión si falla. El clic normal conserva la edición y la ficha mantiene el selector de estantería como alternativa accesible. | Agente |
| 2026-10-27 | Reordenar libros dentro de una estantería: línea de inserción `magic`, cálculo antes/después, orden persistente por posición, actualización optimista y reversión con aviso. Los libros nuevos y los movidos a otra estantería se añaden al final. | Agente |
| 2026-10-27 | Aviso global de éxito al guardar la ficha de un libro: toast flotante abajo, centrado, con borde `success`, texto «Libro actualizado.» y duración de 4 s. | Agente |
| 2026-10-27 | Intro de bienvenida mágica al abrir o recargar la aplicación autenticada: velo nocturno translúcido, aura arcana, destellos dorados y placa de cristal con «Bienvenida, {nombre}», «Tu mundo mágico te espera.» y botón **Entrar**. No se repite al navegar dentro de la app; se cierra con Entrar o Escape y respeta `prefers-reduced-motion`. | Agente |
| 2026-10-28 | Apodo de cuenta: campo obligatorio en el registro (máximo 50 caracteres), editable en Ajustes → Perfil y usado en la intro con «Bienvenida, {apodo}». Las cuentas existentes reciben su nombre actual como apodo. | Agente |
| 2026-10-28 | La barra lateral deja de mostrar el nombre real bajo la navegación; el botón **Cerrar sesión** sube a la parte superior, justo debajo del logo. En móvil se mantiene **Salir** en la cabecera. | Agente |
| 2026-10-28 | Acceso y registro a dos columnas: emblema arcano de la biblioteca sobre cielo estrellado a la izquierda y formulario a la derecha; en móvil, franja superior con el emblema. Campo de contraseña con ojo, botones Google/Facebook con «Próximamente» mientras falten credenciales y pantalla de apodo tras el primer acceso social. | Agente |
| 2026-10-29 | Emblema de acceso rehecho: arco «Biblioteca» visible sobre un libro abierto en 3D, sin placa ni rectángulo, con halo, haz mágico y chispas. Se corrigen los SVG sin ancho definido que se estiraban a todo el contenedor (creaban un rectángulo gigante y obligaban a desplazarse para llegar al formulario); ahora tienen proporción fija, ids únicos por instancia y el acceso ocupa la altura de la pantalla con las dos columnas centradas. | Agente |
| 2026-10-29 | Ajuste del emblema: el libro se dibuja boca arriba, apoyado y visto desde arriba (páginas en perspectiva con el borde cercano más ancho, lomo central y canto del bloque de páginas). El arco «Biblioteca» crece a 50 px con una curva más profunda sobre un lienzo de 440 × 130. | Agente |
| 2026-10-29 | Emblema igualado a la referencia de la usuaria: un solo lienzo de 560 × 480 con arco «Biblioteca» a 80 px, libro abierto con tapas oscuras y filigrana dorada, canto de páginas apilado, renglones y astrolabios, sello mágico central con anillo de marcas giratorio y ojo de cerradura, haz de luz, 18 destellos de cuatro puntas y polvo estrellado sobre viñeta `night`. Ancho máximo 480 px en escritorio y 214 px en compacto. | Agente |
| 2026-10-29 | El título pasa a mayúsculas («BIBLIOTECA», 70 px) y el libro se redibuja como un libro real en 3D: tapas `night`/`panel` con canto de cartón y filo dorado, bloque de páginas con canto apilado y sombra sobre la tapa, páginas curvadas con sombra interior, brillo de canto y grano de papel, y lomo en valle con pliegue y luces. Se retiran de las páginas los renglones manuscritos, los astrolabios, el sello mágico, el haz de luz, las chispas y el polvo: solo queda el libro bajo el arco. | Agente |
| 2026-10-29 | La columna del emblema en Acceso y Registro pasa a un fondo morado más claro: degradado vertical `line`/`panel` con un halo radial `line`/`arcane` detrás del libro. Solo cambia ese lado; el formulario sigue en `night-raised`. En móvil la franja superior usa el mismo fondo. | Agente |
| 2026-10-29 | El fondo de la columna del emblema pasa al nuevo token `orquidea` (`#9E61AB`), con degradado suave hacia `moon` en el centro y `night` en el borde inferior. Se añade `ArcaneStars` dentro de la columna (en absoluto y recortado) para que las estrellas derivan y parpadeen solo en ese lado; se retira la capa global de estrellas del layout de acceso porque ambas columnas son opacas. | Agente |
| 2026-10-29 | El morado `orquidea` se limita a un cuadro redondeado de hasta 528 px que enmarca el título y el libro, con filo dorado, sombra y estrellas en movimiento dentro; la columna recupera el cielo arcano oscuro con sus estrellas. El botón **Entrar** del acceso usa la nueva variante `orquidea` (fondo `orquidea`, texto `night`). | Agente |
| 2026-10-29 | En móvil, las estanterías se mantienen apiladas en vertical, pero los libros de cada una (modo portadas y lomos) se disponen en fila horizontal con scroll para ver todos los libros de la estantería sin salir de la pantalla; se fija un ancho mínimo de ~100 px por portada para que quepan unos tres libros visibles y se deslice al resto. En escritorio se mantienen en rejilla de `auto-fill`. | Agente |
| 2026-10-29 | En la ficha de editar libro, la portada en móvil se limita a ~200 px de ancho y se centra, y el título usa `text-2xl` (crece a `text-3xl` en escritorio), evitando scroll horizontal en pantallas pequeñas. | Agente |
| 2026-10-29 | En la ficha de editar libro, todos los inputs, textarea y selects usan `max-w-full` y el contenedor del formulario usa `min-w-0` y `max-w-full`, para que en celular no se desborde horizontalmente y no sea necesario hacer scroll lateral. | Agente |
| 2026-10-29 | En la ficha de editar libro, el input de portada nativo se oculta y se usa un botón personalizado que dice "Cambiar portada", eliminando el texto "Ningún archivo seleccionado" del navegador. | Agente |
| 2026-10-29 | Las estanterías se pueden marcar como fijas mediante la columna `is_fixed`. Las iniciales (Pendientes, Leyendo, Leídos) y la nueva "En biblioteca" se crean fijas; no se pueden renombrar ni eliminar. Las estanterías creadas por el usuario son editables y eliminables normalmente. | Agente |
| 2026-10-30 | La estantería "En biblioteca" pasa a la primera posición de la biblioteca y de Ajustes, por delante de "Lista de deseos". Se reordena la posición de las estanterías existentes y el alta de nuevas usuarias crea "En biblioteca" como primera estantería fija. | Agente |
| 2026-10-30 | Al mover un libro desde su ficha (sección "Mover a otra estantería"), se muestra el aviso "El libro se movió a «estantería»" en el toast de éxito, con el nombre de la estantería de destino. | Agente |
| 2026-10-30 | Lomo o portada por libro: cada libro guarda su propio `view_mode` y se muestra como portada o como lomo de forma independiente. Desaparece el botón de modo de la estantería. La balda mezcla portadas y lomos en una sola fila respetando el orden, alineados por la base (scroll horizontal en móvil, varias filas en escritorio). En escritorio aparece un botón circular al pasar el ratón para alternar el libro; en móvil se cambia desde la ficha del libro con el selector "Mostrar como". | Agente |
| 2026-10-30 | Al alternar entre portada y lomo (botón rápido o selector "Mostrar como") se muestra el aviso "«título» ahora se muestra como portada/lomo" en el toast de éxito, para confirmar el cambio. | Agente |
| 2026-10-30 | Objetos decorativos: quince dibujos (taza, rosas, planta, vela, reloj de arena, bola de cristal, lámpara, gato, calavera, jaula, astrolabio, set de té, caja de música, pluma y tintero, marco de fotos) con seis colores de la paleta existente. Se añaden desde Ajustes de uno en uno, al final de la primera estantería, se intercalan con los libros, se arrastran dentro y entre estanterías, y se quitan con la × del objeto o desde la lista de Ajustes. | Agente |
| 2026-10-31 | El catálogo de objetos decorativos pasa a un módulo propio **Decoraciones**, con entrada entre Biblioteca y Configuraciones en la barra lateral y en la barra inferior móvil. La página agrupa los quince objetos en cinco categorías (Mesa y té, Flores y plantas, Luz y velas, Curiosidades, Mascotas y recuerdos) para verlos todos sin bajar por Configuraciones, y conserva **Tus objetos** y **Quitar todas**. La sección desaparece de Configuraciones. | Agente |
| 2026-10-31 | Las categorías de Decoraciones dejan de ser secciones apiladas y pasan a una barra de filtros superior con seis círculos (Todos + cinco categorías), cada uno con un dibujo representativo y su etiqueta. Al tocar uno, la cuadrícula muestra solo esa categoría; **Todos** muestra los quince objetos juntos. Evita desplazarse para encontrar una categoría y funciona con `aria-pressed`. | Agente |
| 2026-10-31 | Los círculos de categoría dejan de mostrar el dibujo completo del objeto y pasan a un icono de línea simple que representa el grupo (retícula, taza, flor, vela, destello y corazón), con el mismo trazo fino de los SVG decorativos. | Agente |
| 2026-10-31 | La barra de filtros de Decoraciones queda solo con los iconos: se quitan las etiquetas bajo cada círculo, que pasa a 48 px. El nombre de la categoría se conserva para lectores de pantalla con `aria-label` y como `title` al pasar el cursor. | Agente |
| 2026-10-31 | Se oculta la barra de desplazamiento nativa de los filtros de Decoraciones con la utilidad `scrollbar-hidden`; el deslizamiento horizontal sigue funcionando pero sin la franja gris. | Agente |
| 2026-10-31 | Las seis muestras de color de cada tarjeta de Decoraciones se guardan en un panel flotante: en la esquina del dibujo queda solo un círculo de 24 px con el color actual que, al pulsarlo, despliega los colores en una cuadrícula 3×2. Elegir un color actualiza el dibujo y cierra el panel. | Agente |
| 2026-10-31 | El círculo de color se mueve dentro del recuadro del dibujo, separado 4 px de sus bordes, para que no parezca un elemento suelto de la tarjeta; el panel de colores se alinea con él. | Agente |
| 2026-10-31 | Desaparece el listado **Tus objetos** y el botón **Quitar todas** de Decoraciones. Cuando un objeto ya está colocado, su tarjeta cambia el botón **Añadir** por **Quitar** (borde y texto `danger`) y elimina la última copia de ese tipo al pulsarlo. | Agente |
| 2026-10-31 | El nombre de cada objeto de Decoraciones pasa del pie de la tarjeta al centro del recuadro del dibujo, superpuesto sobre la ilustración con una pastilla `night` al 60 % para mantener la legibilidad. | Agente |
| 2026-10-31 | El nombre se sube a la parte superior del recuadro (dejando libre el espacio del círculo de color) para no tapar la parte baja de la ilustración. | Agente |
| 2026-10-31 | El nombre pierde la pastilla de fondo y pasa a una franja propia en la parte alta del recuadro. El recuadro crece a 128 px (40 px para el nombre + 80 px para el dibujo) para que los objetos más altos se vean completos. | Agente |
| 2026-10-31 | El nombre baja a una franja inferior dentro del recuadro para quedar siempre junto a la base del dibujo; en los objetos bajos ya no aparece separado por un hueco grande. El dibujo gana 8 px de altura (88 px). | Agente |
| 2026-10-31 | El nombre pasa al borde izquierdo del recuadro en vertical, como los lomos, con pastilla `night` al 60 % detrás; el recuadro reserva 40 px a la izquierda para la etiqueta. | Agente |
| 2026-10-31 | La fila de libros y objetos de cada estantería oculta su barra de desplazamiento nativa en móvil con la utilidad `scrollbar-hidden`, igual que los filtros de Decoraciones: el deslizamiento sigue funcionando y desaparece la franja gris. | Agente |
| 2026-11-02 | **Biblioteca** pasa a ser un mueble alto con todos los libros en bandas de tres lomos, laterales de madera, cornisa y zócalo de dos puertas; la página anterior con estanterías reales se traslada a un módulo nuevo, **Estanterías** (`/shelves`), con su entrada en el menú entre Biblioteca y Decoraciones. | Agente |
| 2026-11-02 | Las decoraciones se ven dentro de la banda de su libro ancla en Biblioteca y se pueden arrastrar entre bandas con el mismo gesto que en Estanterías; los libros de Biblioteca son estáticos y solo abren su ficha. | Agente |
| 2026-11-02 | La Biblioteca respeta el modo de vista de cada libro: los que están en modo portada se dibujan como `BookCard` y el resto como lomo, mezclados en la misma banda. Antes se mostraban siempre como lomos. | Agente |
| 2026-11-02 | Los libros de la Biblioteca también se arrastran, con ratón o pulsación larga en el dedo: entre bandas cambian de estantería y dentro de la misma estantería se reordenan antes o después del libro bajo el puntero. | Agente |
| 2026-11-02 | Cada banda de la Biblioteca admite como máximo tres libros y una decoración. Los libros se reducen (lomo compacto de 128 px y 44 px de ancho máximo; portada de 48 px en móvil) y la fila deja de desbordarse, de modo que ningún libro aparece cortado; las decoraciones sobrantes pasan a la siguiente banda con hueco. | Agente |
| 2026-11-02 | La Biblioteca vuelve a los tamaños de libro de Estanterías (lomo de 160 px y portada de 100/110 px) y cada banda pasa a admitir como máximo **dos** libros y una decoración, para que todo quepa sin recortes. | Agente |
| 2026-11-02 | Al mover un libro a otra banda se inserta en la posición exacta bajo el puntero (antes o después de un libro), no al final de la estantería; el resto de libros y decoraciones baja a la banda siguiente para mantener el máximo de dos libros y una decoración. | Agente |
| 2026-11-02 | La Biblioteca admite hasta **cinco** libros por banda (antes dos), manteniendo una decoración como máximo. En móvil la fila se desliza en horizontal sin barra visible y en escritorio se centra. | Agente |
| 2026-11-02 | Las bandas de la Biblioteca pasan a ser estables: cada libro guarda su banda y su posición. Sacar un libro deja la banda con menos libros (no suben los de las bandas siguientes) y meter uno en una banda llena baja el sobrante a la siguiente en cascada; se puede mover un libro a cualquier banda, también hacia arriba. | Agente |
| 2026-11-02 | Al soltar un libro al final de una banda llena, el que baja a la banda siguiente es el último libro que ya estaba en ella, no el que se acaba de mover; así el libro movido permanece en la banda de destino. | Agente |
| 2026-11-02 | La barra inferior móvil se reduce a Biblioteca, Estanterías y un botón **Menú** que despliega Decoraciones y Configuraciones en un panel flotante; la barra lateral de escritorio mantiene los cuatro enlaces. | Agente |
| 2026-11-02 | El botón Menú de la barra inferior pasa a mostrar un icono de tres líneas sin la palabra «Menú»; el nombre se conserva en `aria-label` y `title`. | Agente |
| 2026-11-02 | La app estrena temáticas automáticas por mes (`SeasonalDecor`): invierno, primavera, verano, otoño, Halloween y Navidad, con partículas y resplandores sobre los tokens existentes y sin imágenes externas. Noviembre usa hojas de otoño; octubre, telarañas y arañas; diciembre, copos y acentos rojos, verdes y dorados. Con movimiento reducido la capa no se muestra. | Agente |
| 2026-11-02 | Las partículas pasan a verse por delante del contenido (opacidad baja) y se añaden adornos colgados: una guirnalda superior sobre el área de contenido y una enredadera vertical en el borde del menú lateral. En Halloween la guirnalda cuelga murciélagos. | Agente |
| 2026-11-02 | La guirnalda y la enredadera también se muestran en móvil: la guirnalda cuelga bajo la cabecera con hilos más cortos y la enredadera va en el borde derecho de la pantalla. | Agente |
| 2026-11-02 | Se quitan las hojas que caían sobre el contenido y los adornos quedan fijos; cada banda de la Biblioteca cuelga una guirnalda compacta en su borde superior para que la decoración no parezca aleatoria. | Agente |
| 2026-11-02 | En móvil la guirnalda superior pasa a colgar de la cabecera y se desplaza con ella al hacer scroll; antes estaba fija a la pantalla y quedaba flotando sobre el título. En escritorio se mantiene fija arriba. | Agente |
| 2026-11-02 | Vuelven las hojas que caen, ahora como capa de fondo por detrás del contenido (`z-index: 0`, opacidad baja), sin tapar libros ni textos; las guirnaldas y la enredadera siguen fijas. Con movimiento reducido se ocultan las partículas. | Agente |
| 2026-11-02 | La temática pasa a tener **doce variantes**, una por mes (invierno, San Valentín, primavera, lluvia, flores, verano, sol, girasoles, vendimia, Halloween, otoño y Navidad), con la misma estructura de adornos y distinta forma y paleta; la tabla queda documentada para los meses siguientes. | Agente |
| 2026-11-02 | La valoración del libro pasa a tener tres escalas independientes: estrellas (`gold`), picante con chiles (`danger`) y corazón (`orquidea`), editables en la ficha y en el alta. | Agente |
| 2026-11-02 | El bloque de valoración se queda solo con los iconos: se quitan los textos visibles «Valoración», «Estrellas», «Picante» y «Corazón»; se conservan como `aria-label` para lectores de pantalla. | Agente |
| 2026-11-02 | La valoración suma dos escalas: **gotas** (`drop_rating`, `mist`) y **bola de cristal** (`crystal_rating`, `magic`), con iconos propios, también sin textos visibles. | Agente |
| 2026-11-02 | Las tres imágenes de la ficha crecen a 112 px de alto (64 px de ancho en móvil y 96 px en escritorio) para alinearse con la columna de valoración. | Agente |
| 2026-11-02 | En móvil las imágenes vuelven a 56 px de ancho (manteniendo 112 px de alto) para que sigan en la misma fila que la valoración y no salten debajo; en escritorio quedan en 96 px. | Agente |
| 2026-11-02 | En escritorio la ficha se limita a 768 px y se centra; la valoración y las tres imágenes quedan juntas en una fila, sin estirar los campos ni separar las imágenes al extremo. En móvil no cambia. | Agente |
| 2026-11-02 | En escritorio los cinco campos de la ficha crecen (`text-base`, relleno de 12 px) para que la columna de datos llegue al mismo borde que la portada de 180 px; en móvil conservan el tamaño compacto. | Agente |
| 2026-11-02 | La Biblioteca se queda sin título ni botón **Añadir libro**; el alta de libros se hace solo desde **Estanterías**. | Agente |
| 2026-11-02 | **Estanterías** también pierde su título de página; solo conserva el botón **Añadir libro** alineado a la derecha. | Agente |
| 2026-11-02 | La ficha del libro pasa a dos columnas también en móvil: la portada se reduce a 96 px y los campos quedan a su derecha, para no perder la información al hacer scroll. En escritorio la portada mantiene 180 px. | Agente |
| 2026-11-02 | En la ficha, solo los cinco campos de datos (título, autor, ISBN, editorial y año) van a la derecha de la portada, compactados para ocupar la misma altura que el libro; la valoración, las notas y los botones pasan debajo a ancho completo. | Agente |
| 2026-11-02 | Se quita el ISBN repetido que aparecía bajo la portada en la ficha del libro; el dato sigue en su campo a la derecha. | Agente |
| 2026-11-02 | El botón rápido para alternar lomo/portada también aparece en las bandas de la Biblioteca y se muestra siempre en móvil (en escritorio sigue apareciendo al pasar el ratón); antes solo existía en Estanterías y se ocultaba en móvil. | Agente |
| 2026-11-02 | La ficha del libro deja de mostrar las secciones **Mover a otra estantería** y **Mostrar como**: el cambio de estantería se hace arrastrando en Biblioteca o Estanterías, y la vista con el botón rápido de cada libro. | Agente |
| 2026-11-02 | **Cambiar portada** deja de ser un botón grande bajo las notas y pasa a un botón circular pequeño con icono de cámara sobre la esquina superior derecha de la portada. | Agente |
| 2026-11-02 | La ficha del libro estrena tres imágenes inclinadas junto a la valoración, en ranuras fijas con subida, reemplazo y borrado (JPG/PNG/WebP hasta 5 MB), servidas por ruta autenticada. | Agente |
| 2026-11-03 | Lectura en curso: estantería virtual **Leyendo** en Estanterías con insignia de reproducción y barra de progreso `gold` en portadas y lomos, y diálogo de lectura con porcentaje (página actual y totales), cronómetro con pausa que guarda la sesión del día, e historial de sesiones con borrado confirmado. También se abre con **Registrar lectura** desde la ficha. | Agente |
| 2026-11-04 | Módulo **Cartas**: nuevo destino de navegación entre Estanterías y Decoraciones con máquina de balotas de madera y latón (cúpula, ocho balotas con emblema de familia, manivela y rampa), sobre `crema` con solapa y lacre `danger-strong`, carta en pergamino con capitular en modal `2xl`, álbum de madera con ranuras bloqueadas/desbloqueadas, progreso `gold` y frasco de tinta. Clases nuevas `card-*` en `textures.css` sobre los tokens existentes, `aria-live` y `prefers-reduced-motion` respetados. | Agente |
| 2026-11-04 | Los modales (`Modal`) vuelven a verse correctamente sobre el velo oscuro: el panel pasa de la clase `transform` (sin efecto en Tailwind 4, dejaba el panel debajo del velo `night/80` con desenfoque) a `relative z-10`. Afecta a la carta del módulo Cartas y al diálogo de eliminación de cuenta. | Agente |
| 2026-11-04 | Carta más sencilla y breve, a petición de la usuaria: la cabecera muestra solo «Carta de {personaje}» con el botón **Cerrar** (se retiran emblema, monograma y familia del modal; quedan en el álbum), y las 20 cartas se acortan a ~120 palabras (rango verificado 120–160) con saludo, tres párrafos, firma y posdata opcional. | Agente |
| 2026-11-04 | La dispensadora de cartas pasa de bombo plano a máquina expendedora con volumen, a petición de la usuaria: gabinete de madera 3D con cornisa de latón y zócalo, ventana de cristal con reflejo y las balotas sueltas dentro (posiciones deterministas), palanca de latón con pomo en panel lateral y trampilla de salida. Mismos tokens, estados, accesibilidad y clases de comportamiento; sin colores nuevos. | Agente |
| 2026-11-04 | Volumen reforzado de la dispensadora: gabinete con costado oscurecido y filo lateral iluminado, cornisa superior de latón brillante, sombra de contacto elíptica en el suelo y perspectiva mayor en escritorio; balotas convertidas en esferas con brillo especular, sombra interior y sombra de contacto sobre el cristal; interior de la ventana con viñeta profunda. | Agente |
| 2026-11-04 | La dispensadora se redibuja como **máquina expendedora en SVG isométrico** (aun se veía plana): frente, costado y tapa de madera con degradados, cornisa, patas y sombra en el suelo. Todo el movimiento es visible al tirar: la palanca de latón se hala y regresa con rebote, el gabinete tiembla, la balota cae a la trampilla y la tapa de esta se abre antes de aparecer el sobre. Los controles siguen siendo botones reales encima del SVG; misma paleta y accesibilidad. | Agente |
| 2026-11-04 | La dispensadora se iguala «tal cual» a la referencia dorada de la usuaria: máquina tipo gachapon de bronce con marco doble grabado, foco cálido, gran ventana con perlas apiladas en montón (emblema grabado en dorado), columna lateral con moneda «!», palanca de varilla con pomo perlado, libro y filigranas grabadas, trampilla oscura y patas. Las perlas escalan con la ventana (36 % relativo). | Agente |
| 2026-11-04 | Rediseño profundo de la dispensadora con SVG multicapa a petición de la usuaria: oro envejecido con gradientes metálicos de 6 paradas, marco ornamental con curlas y remaches, perlas de porcelana decorativas e interactivas recortadas con `clipPath`, sombras y reflejos con `feGaussianBlur`/`feDropShadow`, palanca mecánica de varilla roscada dibujada en SVG, perlas que se agitan con desfase al girar y trampilla que se abre, espera y se cierra (`card-flap-cycle`). Sin librerías externas ni Canvas; misma lógica, accesibilidad y paleta. | Agente |
| 2026-11-04 | Ajustes tras revisión de la usuaria: perlas apiladas de forma densa y natural (tamaños 30–36 % con solape, sin filas separadas), pomo de la palanca bajo la moneda «!» con varilla más fina y base pequeña integrada (se quita la ranura que chocaba), cornisa/tapa superior con brillo, esquinas menos redondeadas y oro más profundo, grabados de arcos bajo la ventana y trampilla oscura con filo de latón (ya no parece una pastilla clara). | Agente |
| 2026-11-04 | La dispensadora pasa a **3D real con Three.js** (petición explícita de la usuaria): escena WebGL con cuerpo metálico, ventana con agujero real, marco extruido, perlas esféricas con iluminación, palanca 3D animada por código y trampilla bisagrada; grabados con texturas de lienzo. Selección por raycasting más botones fantasma accesibles proyectados; carga diferida del módulo `three` (chunk propio, 192 KB gzip); degradación sin WebGL. Se retira el SVG anterior de la máquina; el resto del módulo no cambia. | Agente |
| 2026-11-04 | Revisión profunda de la máquina 3D: **entorno reflectante** (RoomEnvironment + PMREM) que da metales y perlas con brillo real, cámara en ángulo y máquina girada, bisel perimetral, perlas más grandes apiladas de forma natural, luz interior y halo de fondo. **Nuevo flujo de gachapon**: ya no se elige perla; la palanca escoge una al azar, la saca por la trampilla y aparece la carta (la manivela queda habilitada para repetir en modo de pruebas y el servidor responde 422 en producción). Texto de ayuda actualizado en los 8 idiomas («Gira la manivela…»). | Agente |
| 2026-11-04 | Corrección de encuadre e interior: cámara alejada (la máquina ya no se corta arriba ni abajo), **interior de la ventana hundido de verdad** — paredes oscuras de pozo con fondo al fondo y luz cálida tenue — en vez de una caja cerrada que se veía plana y clara; perlas grandes dentro del pozo con **flotación suave permanente**; al jalar la palanca la perla **sale visiblemente por la trampilla**, cae y se desvanece antes de mostrar la carta. | Agente |
| 2026-11-04 | Ajustes de lectura de la máquina: cristal casi invisible (opacidad 4 %, sin metalizado) para ver el fondo con profundidad; **fondo del cajón con degradado morado oscuro y halo cálido** fijo (no depende de luces); paredes del pozo en tono plano oscuro para que las perlas perlan contraste; **se retira la moneda «!»** de la columna (solo queda la palanca) y la zona clicable de la palanca cubre toda la columna para que siempre responda. | Agente |
| 2026-11-04 | Causa raíz de «no se ven las bolas»: el bisel perimetral era una caja sólida cuya cara frontal (z 0,54) tapaba el interior de la ventana. Se sustituye por un **marco extruido con hueco** (borde de 8 px alrededor del cuerpo) que deja ver el pozo, el fondo degradado y las perlas apiladas. | Agente |
| 2026-11-04 | Segunda causa raíz encontrada: el **cuerpo de la máquina era una caja sólida** (`RoundedBox`) sin agujero, y su cara frontal dorada era lo que se veía por la ventana. El cuerpo pasa a ser una **extrusión con el agujero de la ventana incluido** (profundidad 1,0, bisel suave), se elimina la placa frontal duplicada y se realinean marco, grabados, trampilla y cristal al nuevo frente (z 0,48). El hueco ahora atraviesa el cuerpo de verdad y deja ver el pozo con las perlas. | Agente |
| 2026-11-04 | La palanca se jala **hacia el frente**: la rotación de la manivela estaba invertida (se inclinaba hacia atrás); ahora el pomo avanza hacia el espectador (rotación positiva + avance en z del pomo) mientras desciende y regresa con rebote. | Agente |
| 2026-11-04 | El recorrido de la palanca baja hasta **casi la mitad de la varilla** (el pomo desciende 0,44 y la varilla se comprime siguiéndolo), a petición de la usuaria. | Agente |
| 2026-11-04 | La animación de la palanca se **desacopla de la petición** y pasa a tener línea de tiempo propia de 2,4 s: descenso lento con suavizado cúbico (45 % del tiempo), pequeño temblor al fondo, y regreso suave — se ve el recorrido completo aunque el servidor responda al instante. Con movimiento reducido se abrevia. | Agente |
| 2026-11-04 | Salida de la perla coordinada con la trampilla: la bola ya no se ve antes de que la puertecita se abra — primero esta se abre (0,3 s), después la perla asoma **desde detrás de la trampilla** y cae con desvanecido, y la puertecita se cierra al final. | Agente |
| 2026-11-04 | Salida realista de la perla: aparece **dentro del cajón oscuro** (detrás del marco de la ranura, se desvanece al entrar en escena), rueda hacia afuera pasando el labio de la ranura (avanza en z, gira sobre sí misma) y luego cae con aceleración desvaneciéndose — ya no se superpone sobre la puertecita cerrada. | Agente |
| 2026-11-04 | Las perlas se **apilan desde el piso** según cuántas haya (pirámide por filas, ninguna flotando) y, cuando sale una por la trampilla, **las de arriba caen a rellenar el hueco** con rebote suave — el montón se agota de forma natural con cada tirada. | Agente |
| 2026-11-04 | El **álbum se separa a página propia** (`/cards/album`, componente `Cards/Album`): a ancho completo con cabecera de título, tinta y barra de progreso. La página de la máquina queda centrada en una columna y añade un **botón «Ver el álbum de cartas»** bajo la máquina (clave `ui.cards_open_album` en los 8 idiomas); el álbum abre cartas con el mismo modal. | Agente |
| 2026-11-04 | La cabecera de la página de la máquina queda **solo con el título «Cartas»** (se retiran el subtítulo y el frasco de tinta, a petición de la usuaria); la tinta sigue visible en la página del álbum. | Agente |
| 2026-11-04 | La cabecera del **álbum** también pierde el subtítulo: queda «Álbum» + frasco de tinta. | Agente |
| 2026-11-04 | Se elimina la pieza lateral suelta del lado derecho (caja `side` que sobresalía del cuerpo y parecía despegada); el canto derecho ahora es el propio lateral del cuerpo extruido. | Agente |
| 2026-11-08 | Tema claro pastel por defecto y tema oscuro conmutables desde **Ajustes → Apariencia** (persistido en la cuenta). La interfaz pasa a tokens semánticos (`page`, `surface`, `surface-raised`, `surface-sunken`, `ink`, `ink-muted`, `ink-soft`, `heading`, `on-accent`, `edge`, `edge-strong`, `accent`, `accent-strong`, `accent-soft`, `secondary`, `secondary-strong`, `tertiary`, `tertiary-strong`, `danger`, `danger-strong`, `success`, `warning`, `ring`, `scrim`); se retiran los tokens de la paleta anterior. Sin cambios de tipografía. El mueble y las decoraciones conservan sus colores físicos (`malva` y `laton` se añaden para los objetos). | Agente |
| 2026-11-08 | Botones principales pasan de fondo `tertiary` (púrpura en modo claro) a fondo `accent` (rosado en modo claro, dorado en oscuro), con hover/activo `accent-strong` y anillo de foco `accent`. Se aplica a `PrimaryButton` (tono por defecto) y a todos los botones directos con clases `bg-tertiary`/`hover:bg-tertiary-strong` en páginas y componentes. | Agente |
| 2026-11-08 | Selector de tema visual (`ThemeSwitch`): toggle sol / luna estilo Uiverse (rishi chawda) con SVG inline — sol amarillo sobre cielo claro y luna con cráteres sobre cielo nocturno azul (`#2b4360`) — en Ajustes → Apariencia. El círculo se desplaza al alternar y el fondo del switch se adapta al tema activo. Colores físicos del diseño original; persistencia en `users.theme` y `data-theme` en `<html>`. | Agente |
| 2026-11-08 | La navegación inferior muestra solo iconos (sin títulos) para Biblioteca y Estanterías, distribuidos equitativamente con un deslizador (`glider`) que indica la pestaña activa. El botón de rayitas abre el menú álbum lateral derecho, que se desliza con la clase `album-menu--open`. Dimensiones del tabs ajustadas para 360 px (padding reducido, iconos de 18 px y glider proporcional). | Agente |
| 2026-11-08 | Correccion de BottomNav: iconos no se renderizaban porque usaba v-html en svg (interpretado como HTML). Se cambio a path :d. El glider (pildora) no se movia porque translateX(33.33%) movia solo un 11.11% del contenedor. Se corrigio a translateX(100%) por pestana. Se agregaron left y width calculados para alinear con el padding del contenedor. Todos los onClick inline (menuOpen.value = ...) se reemplazaron por funciones (toggleMenu, closeMenu, goTab, goLibrary, goShelves) para evitar Cannot create property value on boolean false. | Agente |
| 2026-11-08 | Mejora visual de BottomNav: iconos centrados vertical y horizontalmente (tab con flex center, icono 20px). Glider con sombra interior para profundidad. Borde en el contenedor de tabs. Dimensiones ajustadas para movil (padding 0.5rem, tab 32px, icono 20px, glider proporcional). BottomNav aparece en todos los modulos autenticados via AppShell. | Agente |
| 2026-11-08 | Mejora del menu lateral (album-menu): ahora abre desde el lado izquierdo con ancho del 80% (max 320px), dejando visible parte del contenido. Se agrego capa semitransparente (album-menu-overlay) que cierra el menu al hacer clic fuera. Se agrego cierre con tecla Escape. Alineacion mejorada de iconos, textos y flechas. Colores, tipografia e iconos conservados. | Agente |
| 2026-11-09 | La navegación inferior móvil (`BottomNav`), su menú álbum y su capa semitransparente se ocultan a partir de `md` (768 px) con `md:hidden`. En escritorio solo queda la barra lateral; antes la barra inferior se mostraba también en PC. Sin cambios de colores ni tipografía. | Agente |
| 2026-11-09 | Corrección del bug por el que el menú álbum seguía visible al pasar de móvil a escritorio: `.album-menu` define `display: flex` sin capa y anulaba la utilidad `md:hidden` de Tailwind (capa `utilities`). Se añade en `textures.css` `@media (min-width: 768px) { .album-menu, .album-menu-overlay { display: none } }` y en `BottomNav.vue` un listener de `matchMedia('(min-width: 768px)')` que cierra el menú al cruzar a escritorio. | Agente |
| 2026-11-09 | El módulo de navegación **Cartas** pasa a llamarse **Máquina expendedora** (`ui.nav_cards` en los 8 idiomas) y su icono en el menú móvil cambia de una pila de cartas a una máquina expendedora (cuerpo, ventana, botones y trampilla). El contenido de la página y el título de la cabecera (`ui.cards_title`) siguen siendo «Cartas». | Agente |
| 2026-11-09 | Se elimina la cabecera con el título «Cartas» de la página `Cards/Index`; la página empieza directamente con la máquina expendedora. El título en la pestaña del navegador se mantiene. | Agente |
| 2026-11-09 | El texto de ayuda de la máquina pasa de «Gira la manivela para sacar una carta.» a «Gira la manivela para sacar una sorpresa.» en los 8 idiomas (`ui.cards_choose_ball`). | Agente |
| 2026-11-09 | El botón bajo la máquina pasa de «Ver el álbum de cartas» a «Ver álbum» en los 8 idiomas (`ui.cards_open_album`). | Agente |
| 2026-11-09 | Se reducen los idiomas a **español e inglés**: se eliminan los catálogos `pt`, `it`, `gl`, `fr`, `de` y `ca`, y `config/languages.php` deja `curated` y `supported` solo con `es` y `en`. El selector de Configuraciones muestra únicamente estos dos idiomas revisados; ya no hay idiomas automáticos ni progreso de preparación. | Agente |
| 2026-11-09 | Se elimina el selector **Portadas/Lomos** de la sección Perfil de Configuraciones. La vista se cambia por libro en Biblioteca o Estanterías; el campo `default_view_mode` deja de enviarse en el formulario de perfil. | Agente |
| 2026-11-09 | Nuevo componente `FloatingInput` para los formularios de Perfil y Contraseña de Configuraciones: etiqueta flotante apoyada en el borde superior (fondo `surface`, texto `accent`), placeholder `ui.write_here`, borde `edge` que pasa a 2 px `accent` en foco y `danger` en error, radio 6 px. Nuevo texto `ui.write_here` en español e inglés. | Agente |
| 2026-11-09 | Las secciones de Configuraciones (Perfil, Contraseña e Idioma) van sobre tarjetas `surface` con borde `edge`, radio 6 px y relleno 16 px, en modo claro y oscuro. La etiqueta flotante abre el hueco con fondo `surface` para integrarse en la tarjeta. | Agente |
| 2026-11-10 | Se elimina la sección **Estanterías** de Configuraciones: los nombres y la distribución de las estanterías son fijos y ya no se renombran, reordenan ni eliminan desde la interfaz. La página queda en Perfil, Contraseña e Idioma; se retiran del catálogo `ui.shelves_heading` y `ui.delete_shelf_confirm`. | Agente |
| 2026-11-10 | Los campos de Perfil y Contraseña de Configuraciones muestran el texto de ayuda correcto (`ui.write_here`: «Escribe aquí...» / «Write here...») en lugar de la clave literal. Se aplana el bloque `"ui"` anidado por error en `lang/es.json` y `lang/en.json`, que también impedía resolver las etiquetas de las pegatinas del álbum. | Agente |
| 2026-11-10 | La sección **Idioma** de Configuraciones pierde el texto de ayuda «Elige el idioma de la aplicación.» y la barra de búsqueda. Queda el idioma actual y la lista directa de español e inglés con su etiqueta «Revisado». Se retiran del catálogo `ui.language_help`, `ui.language_search` y `ui.language_no_results`. | Agente |
| 2026-11-10 | Rediseño completo de los 20 stickers de la máquina: ilustración detallada y cohesiva con sombreado suave, colores naturales de cada objeto y silueta troquelada propia por sticker con borde blanco. El estado bloqueado reutiliza la misma silueta con `?`. Sin colores nuevos. | Agente |
| 2026-11-10 | Nuevo módulo **Álbum de colección** en `/album`, independiente de la Máquina expendedora, con entrada propia en navegación. Muestra cartas y stickers de máquina en una rejilla tipo álbum digital con barra de progreso, filtros por categoría, tarjetas troqueladas y detalle en modal. Reusa `LibrarySticker` y `CardEmblem`; sin colores nuevos. | Agente |
| 2026-11-10 | La **Máquina expendedora** ya no repite cartas: cada balota diaria mezcla cartas no obtenidas y stickers no obtenidos; al completar las 40 piezas se oculta la manivela y se muestra `ui.cards_complete`. Sin tienda, sin tinta por duplicados y sin estado de repetidas en el sobre. | Agente |
| 2026-11-10 | Se elimina por completo el módulo **Eventos**: rutas, controlador, servicio, modelo, vistas, pruebas, configuración, traducciones `ui.events_*` y tabla `event_progress`. La navegación queda Biblioteca, Estanterías, Máquina expendedora, Decoraciones, Álbum y Ajustes. | Agente |
| 2026-11-11 | El formulario **Manual** de Añadir libro (`Books/Create`) adopta el mismo formato que la ficha de edición: página centrada a `max-w-3xl`, cabecera con enlace **Volver** a Estanterías, portada de 96/180 px con botón de cámara a la izquierda y datos a la derecha, valoraciones y notas con el mismo espaciado. Se mantiene el selector de estantería, necesario para el alta. | Agente |
| 2026-11-11 | En escritorio, la **Biblioteca** deja de aplicar el límite de cinco libros por balda pensado para móvil: empaqueta los libros según el ancho disponible y reúne varias bandas guardadas, de modo que las estanterías se llenan. En móvil se mantienen las bandas de cinco libros con desplazamiento horizontal. El arrastre traduce la posición visual a la banda y posición guardadas. | Agente |
| 2026-11-11 | En **Añadir libro** se elimina el texto de ayuda «Escribe al menos 3 letras; los resultados aparecen solos.» bajo el buscador. La búsqueda en vivo y el indicador «Buscando…» se mantienen; se retira la clave `ui.search_hint` de los catálogos. | Agente |
| 2026-11-12 | Nuevo panel privado **Catálogo master** en `/catalog`, visible en la navegación solo para la cuenta master (enlace «Catálogo» en la barra lateral de escritorio y en el menú de la barra inferior en móvil). Página de listado a `max-w-3xl` con cabecera «Catálogo master» y botón «Añadir al catálogo», fichas en tarjeta con portada, autor, editorial, año e ISBN, y acciones de editar y eliminar con modal de confirmación; estado vacío centrado. Formularios de alta y de edición con el componente `CatalogBookForm`: portada de 96/180 px con botón de cámara, datos en columna y campo de URL de portada. Reusa los tokens y componentes existentes; sin colores, tipografías, sombras ni radios nuevos. | Agente |
| 2026-11-12 | La búsqueda de libros de **Añadir libro** muestra primero las fichas del catálogo local (portada por ruta autenticada `/catalog-covers/{id}`) y después los resultados de las APIs, sin deduplicar. Al elegir una ficha del catálogo se guarda su `catalog_book_id` y se limpia la URL externa; la portada del catálogo se copia al guardar el libro personal. | Agente |
| 2026-11-12 | Las decoraciones de la **Biblioteca** admiten quitarse con una **×** en la esquina superior derecha, con el mismo aspecto que el botón de cambiar a lomo/portada: en escritorio aparece al pasar el ratón y en móvil se ve siempre. Al pulsarla borra el objeto sin confirmación y sin activar el arrastre. En **Estanterías** la × no cambia (solo escritorio). Sin colores, tipografías, sombras ni radios nuevos. | Agente |
| 2026-11-12 | Las decoraciones dejan de verse en **Estanterías**: cada balda muestra solo libros, sin objetos decorativos ni arrastre de objetos. Siguen visibles en la **Biblioteca** (con su × para quitar) y en el módulo **Decoraciones**. No hay cambios de base de datos, rutas ni traducciones. | Agente |
