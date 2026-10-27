# DISEÑO — Biblioteca

**Última actualización:** 2026-10-27
**Estado:** vigente
**Alcance:** identidad visual, componentes, estructura de interfaz y convenciones de diseño de la aplicación Biblioteca.

> Este documento es la fuente única de verdad visual del proyecto. Cualquier agente o persona que modifique colores, tipografías, componentes, espaciados, texturas o estructura de las pantallas debe actualizar este archivo y registrar el cambio en la tabla **Registro de cambios** antes de considerar el trabajo terminado. No se permiten estilos nuevos fuera de estos tokens sin registrarlos aquí.

---

## 1. Concepto

Una biblioteca personal con aire de biblioteca antigua, ahora vivida de noche: una sala en penumbra violeta donde las estanterías de madera son lo único cálido e iluminado. El resto de la interfaz se viste de noche profunda, oro y turquesa mágico. La sensación debe ser íntima, ordenada y un poco arcana, no recargada. La estantería sigue siendo la protagonista; la interfaz la acompaña sin competir con las portadas.

Principios:

1. **Las portadas mandan.** El mueble conserva su madera y sus colores cálidos; el entorno nocturno es sobrio para que los libros destaquen.
2. **Antiguo, no viejo.** Texturas sutiles, buena legibilidad y contrastes modernos.
3. **Ligero.** Nada de imágenes pesadas; texturas con CSS/SVG. El hosting es compartido.
4. **Cálido y nocturno.** Mensajes breves, en español, con tono literario pero directo.
5. **Consistencia.** Todo componente nuevo reutiliza los tokens de este documento.
6. **El mueble no se toca.** Madera, espaldar, balda, portadas y lomos conservan su paleta y sus reglas.

---

## 2. Paleta de colores

### Paleta de la noche (interfaz)

| Token | Hex | Uso |
|---|---|---|
| `night` | `#12101F` | Fondo base de la aplicación y del cielo arcano |
| `night-raised` | `#1A1730` | Barras de navegación, cabeceras y superficies elevadas sobre la noche |
| `panel` | `#211D3A` | Tarjetas, paneles, modales e inputs |
| `panel-raised` | `#2B2550` | Hover de tarjetas, opción activa de listas, botones secundarios |
| `line` | `#3A3363` | Bordes, separadores y anillos suaves |
| `moon` | `#F1ECFF` | Texto principal sobre la noche |
| `mist` | `#B7AED6` | Texto secundario, metadatos, placeholders y ayudas |
| `arcane` | `#7C5CE0` | Acción principal, pestaña activa |
| `arcane-strong` | `#6A4AC9` | Hover y estado activo de la acción principal |
| `magic` | `#74D8C9` | Enlaces, foco, borde activo y acentos mágicos |
| `magic-strong` | `#5FC7B8` | Hover de acentos turquesa |
| `gold` | `#E8C887` | Títulos, estrellas, logo y detalles dorados |
| `danger` | `#F2A0B0` | Errores, texto y bordes destructivos |
| `danger-strong` | `#A8455C` | Fondo de botones destructivos |
| `success` | `#8FE3B8` | Mensajes de éxito |

### Paleta del mueble (estanterías y libros)

Estos tokens siguen definidos con sus valores originales y quedan reservados al mueble y a los libros. No se usan en el resto de la interfaz.

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

### Colores semánticos

| Token | Valor | Uso |
|---|---|---|
| `surface` | `night` | Fondo de página |
| `surface-raised` | `panel` | Tarjetas, modales, inputs |
| `text` | `moon` | Texto principal |
| `text-muted` | `mist` | Texto secundario, metadatos |
| `border` | `line` | Bordes y separadores |
| `primary` | `arcane` | Acción principal |
| `primary-hover` | `arcane-strong` | Hover de la acción principal |
| `accent` | `magic` | Enlaces, foco y estados activos |
| `danger` | `danger` / `danger-strong` | Errores y acciones destructivas |
| `success` | `success` | Confirmaciones |
| `focus` | `magic` | Anillo de foco |

### Reglas de contraste

- Texto `moon` sobre `night`, `night-raised`, `panel` y `panel-raised`: cumple AA.
- Texto `mist` se usa para secundarios; no para párrafos largos.
- Texto `moon` sobre `arcane` y `danger-strong`: cumple AA.
- `gold` se reserva a títulos, estrellas, logo y detalles; no para párrafos.
- `magic` se usa en enlaces cortos y foco; no como color de cuerpo de texto.
- El mueble mantiene sus combinaciones propias: `crema`/`tinta` sobre madera, portadas y lomos.
- Nunca usar `dorado` como color de texto pequeño sobre fondos claros; pertenece al mueble.

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
- **Sombras:**
  - Suave: `0 1px 2px rgba(43, 38, 32, 0.10)`
  - Tarjeta: `0 2px 6px rgba(43, 38, 32, 0.12)`
  - Portada en hover: `0 8px 20px rgba(43, 38, 32, 0.18)`
  - Modal: `0 20px 50px rgba(43, 38, 32, 0.30)`
  - Balda: `0 6px 6px rgba(43, 38, 32, 0.22)` con frente biselado y sombra interior de contacto.

---

## 5. Texturas

- **Cielo arcano:** clase `.arcane-sky` sobre `night`, con dos resplandores radiales (`arcane` al 30 % arriba a la izquierda y `magic` al 16 % abajo a la derecha) y el componente decorativo `ArcaneStars` con cuatro capas de estrellas de 0.7–1.8 px en `moon` y acentos `gold`. Las capas derivan en diagonal durante 70–150 s y parpadean suavemente cada 6–14 s, con desfases entre capas. Solo se animan `transform` y `opacity`; sin canvas ni imágenes. La capa va detrás del contenido (`z-index: -1`) y no captura clics. Con `prefers-reduced-motion: reduce` las estrellas quedan fijas.
- **Madera:** degradado vertical `#6B4F3A → #8B6B4F` con vetas hechas con `repeating-linear-gradient` a baja opacidad. Sin imágenes bitmap.
- **Peso:** ninguna textura debe superar 50 KB en total; preferir CSS/SVG inline.

---

## 6. Componentes

### Navegación

- **Escritorio:** barra lateral fija de 240 px, fondo `night-raised` con borde final `line`, texto `mist`. Logo en Cormorant `gold`. Enlaces con icono y etiqueta.
- **Móvil:** barra inferior fija de 56 px, fondo `night-raised` con borde superior `line`, dos acciones: Biblioteca y Configuraciones. Añadir libro se abre desde el botón de la cabecera de la Biblioteca. Zona segura inferior respetada.
- Enlace activo: fondo `panel`, borde inicial `magic` y texto `moon` (escritorio); texto `gold` y subrayado `magic` (móvil).

### Botones

- **Primario:** fondo `arcane`, texto `moon`, radio 4 px, altura mínima 44 px. Hover y activo `arcane-strong`. Foco con anillo `magic` de 2 px y offset `night`.
- **Secundario:** fondo `panel`, borde `line`, texto `moon`. Hover `panel-raised`.
- **Peligro:** borde y texto `danger`; en confirmaciones, fondo `danger-strong` y texto `moon`.
- **Icono:** cuadrado de 44 × 44 px, sin borde, hover con fondo `panel`.
- **Enlaces:** `magic`; hover `gold`.

### Inputs

- Fondo `panel`, borde 1 px `line`, radio 4 px, altura 44 px.
- Texto `moon`; placeholder `mist`.
- Foco: borde `magic` + anillo `magic`.
- Error: mensaje `small` en `danger` debajo.
- Archivos: botón nativo en `panel-raised` con texto `moon`.

### Tarjeta de libro (`BookCard`)

- Proporción de portada 2:3, radio 4 px, sombra suave.
- Hover: halo `dorado` de 2 px alrededor de la portada con resplandor suave, 160 ms. Sin desplazamiento ni cambio de sombra.
- La tarjeta muestra solo la portada: sin título ni autor debajo. Título y autor aparecen en el tooltip nativo y en el texto alternativo de la imagen.
- Sin portada: fondo `crema`, borde `madera` al 35 %, título y autor centrados en Cormorant como portada de sustitución.
- Portada con `alt="Portada de {título}"`.

### Lomo (`BookSpine`)

- Rectángulo vertical de 160 px de alto y 18–88 px de ancho. El ancho crece con la longitud del título.
- El color de fondo se toma del color dominante de la portada (similar, no idéntico): se muestrea la imagen en un canvas pequeño y se usa el tono más frecuente. Sin portada, el color sale de un hash estable del título dentro de la paleta (oliva, bosque, madera, terracota, salvia).
- El título se muestra en vertical, centrado en el lomo. Si es largo, el lomo se engrosa y el texto se reparte en varias columnas (con el cuerpo de 10 a 8 px si hace falta) para que no se corte; aun así el tooltip y el `aria-label` llevan el título completo.
- El color del texto se elige automáticamente entre `crema` y `tinta` según el brillo del fondo.
- Al pasar el cursor: halo `dorado` alrededor del lomo, sin desplazamiento, y tooltip con título y autor.

### Estantería (`Shelf` + `ShelfBoard`)

- Contenedor con título `h2`, contador de libros y control de modo (portadas/lomos).
- **Espaldar:** panel de madera oscura detrás de los libros, con veta vertical sutil y sombras interiores arriba y a los lados que simulan profundidad. Junto con la balda forma un perfil en L.
- **Modo portadas:** rejilla con `auto-fill`, mínimo 88 px por portada, separación 12–20 px; máximo 7 columnas en escritorio. Los libros se alinean al fondo de la balda.
- **Modo lomos:** fila horizontal con scroll si hace falta; libros apoyados en la balda.
- **Balda:** volumen de 20–24 px con dos caras: superficie superior en perspectiva (`rotateX` suave, de `madera-clara` a `madera`) y frente más oscuro con bisel claro arriba, veta horizontal sutil, sombra interior en los extremos y abajo, y sombra proyectada. La cara superior lleva una sombra interior que simula el contacto de los libros. Debe parecer que sostiene los libros.
- Estado vacío: `EmptyShelf` con borde discontinuo `madera` al 35 %, frase breve en `crema` y enlace para añadir.

### Arrastrar libros entre estanterías

- Origen: cualquier `BookCard` o `BookSpine`. En escritorio se arrastra con el botón principal y el gesto se activa tras 6 px. En móvil se mantiene pulsado 320 ms y después se arrastra; si hay un desplazamiento mayor de 8 px antes de ese tiempo, el gesto se cancela y la página puede hacer scroll.
- Al activarse, el libro de origen se atenúa al 30 %, el cursor pasa a `grabbing` y aparece una miniatura flotante sobre el puntero: la portada (76 px de ancho) o el lomo (28 × 148 px) según el modo de la estantería de origen. La miniatura no intercepta eventos y lleva un resplandor `magic` suave.
- Todas las estanterías se delinean con un trazo discontinuo `magic` al 28 %. La estantería bajo el puntero usa un trazo continuo de 2 px `magic`, un resplandor exterior `magic` al 24 % y la etiqueta **Suelta aquí** en `magic` con texto `night`.
- Cerca de los bordes superior o inferior de la ventana, la página se desplaza automáticamente hasta 18 px por fotograma mientras se mantiene el arrastre.
- Soltar fuera de una estantería, en el mismo hueco o pulsar Escape cancela el movimiento sin hacer peticiones ni abrir la ficha.
- Al soltar en otra estantería, el cambio se aplica de forma optimista y se anuncia con un aviso `role="status"`; si la petición falla, el libro vuelve a su posición y se muestra el aviso en `danger`.

### Reordenar libros dentro de una estantería

- Al arrastrar dentro de la misma estantería, aparece una línea vertical de 2 px en `magic` con resplandor suave en el hueco donde quedará el libro.
- La línea se dibuja antes o después del libro bajo el puntero según su mitad horizontal. Si se suelta en la zona vacía, el libro va al final.
- Soltar en la misma posición no hace ninguna petición.
- El orden se guarda por libro y se mantiene al recargar. Los libros nuevos y los que llegan de otra estantería se añaden al final.
- El cambio es optimista y reversible: si falla, se restaura el orden anterior y se muestra un aviso `danger`.
- La reordenación se hace solo por arrastre; no se añade un campo de posición en la ficha.

### Valoración (`RatingStars`)

- Cinco estrellas de 20 px en `gold`; vacías con `gold` al 40 %.
- Editable con teclado (flechas) y accesible (`role="radiogroup"`).

### Modales

- Panel `panel`, borde `line`, radio 12 px, sombra de modal, ancho máximo 560 px (640 px para el buscador).
- Fondo oscurecido `night` al 80 % con desenfoque suave.
- Título en Cormorant `h2` color `gold`; cierre con botón y tecla Escape.
- En móvil ocupan el ancho completo con márgenes de 16 px.

### Toasts

- Fondo `night-raised`, borde `line`, texto `moon`, radio 6 px, duración 4 s.
- Éxito con acento `success`; error con acento `danger`.
- `aria-live="polite"`.

### Esqueletos de carga

- Bloques `mist` al 20 % con animación de brillo de 1.2 s. Con `prefers-reduced-motion`, sin animación.

---

## 7. Estructura de pantallas

### Biblioteca (`Library/Index`)

1. Cabecera con título “Mi biblioteca” y botón **Añadir libro**.
2. Lista de estanterías en orden, cada una con su balda y libros.
3. Estado vacío general si no hay libros ni estanterías.
4. Arrastrar una portada o un lomo a otra estantería para mover el libro, o dentro de la misma estantería para cambiar su posición; en móvil, pulsación larga y arrastre.

### Añadir/editar libro

- Modal con pestañas **Buscar** y **Manual**.
- En **Buscar**: escáner, ISBN, búsqueda por título/autor y resultados.
- Pie fijo con selector de estantería y botón **Guardar**.

### Configuraciones

- Secciones apiladas: Perfil, Contraseña, Estanterías, Idioma, Vista por defecto.
- Guardado por sección con confirmación visual.
- **Idioma:** selector con búsqueda, revisados primero (etiqueta “Revisado”) y resto con “Automático”. Al elegir un idioma automático se muestra el progreso de preparación y, si falla, un aviso en `danger` sin cambiar el idioma activo. Los nombres de estanterías se muestran traducidos, pero en esta sección se editan siempre en su idioma original.

### Autenticación

- Fondo `night` con el cielo arcano.
- Tarjeta centrada `panel` con borde `line`, 420 px máximo, título en Cormorant `gold`.
- Enlaces en `magic`; errores en `danger`.

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
- Respetar `prefers-reduced-motion: reduce`: sin transformaciones ni brillos animados.

---

## 10. Accesibilidad

- Contraste AA en texto y controles.
- Foco visible con anillo `magic` de 2 px y offset `night`.
- Navegación completa con teclado; modales con trampa de foco y Escape.
- `aria-label` en botones de icono, estrellas y lomos con título recortado.
- Textos alternativos descriptivos en portadas.
- No usar color como único indicador de estado.
- Arrastrar es una mejora progresiva: la ficha del libro mantiene el selector **Mover a otra estantería** para teclado y lectores de pantalla. La ayuda del gesto se anuncia con un texto oculto y el resultado con `role="status"`.

---

## 11. Tono y textos

- Idioma: la interfaz se muestra en el idioma elegido por la usuaria (español por defecto). Los textos de acceso, registro y recuperación permanecen en español.
- Tono cálido, breve y literario, sin exagerar.
- Las traducciones automáticas mantienen el tono; si una traducción falla, se muestra el texto en español sin romper la pantalla.
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
