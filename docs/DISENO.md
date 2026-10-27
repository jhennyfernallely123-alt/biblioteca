# DISEÑO — Biblioteca

**Última actualización:** 2026-10-26
**Estado:** vigente
**Alcance:** identidad visual, componentes, estructura de interfaz y convenciones de diseño de la aplicación Biblioteca.

> Este documento es la fuente única de verdad visual del proyecto. Cualquier agente o persona que modifique colores, tipografías, componentes, espaciados, texturas o estructura de las pantallas debe actualizar este archivo y registrar el cambio en la tabla **Registro de cambios** antes de considerar el trabajo terminado. No se permiten estilos nuevos fuera de estos tokens sin registrarlos aquí.

---

## 1. Concepto

Una biblioteca personal con aire de biblioteca antigua: madera cálida, verdes profundos, papel pergamino y detalles dorados. La sensación debe ser íntima y ordenada, no recargada. La estantería es la protagonista; la interfaz la acompaña sin competir con las portadas.

Principios:

1. **Las portadas mandan.** El color y la textura del entorno son sobrios para que los libros destaquen.
2. **Antiguo, no viejo.** Texturas sutiles, buena legibilidad y contrastes modernos.
3. **Ligero.** Nada de imágenes pesadas; texturas con CSS/SVG. El hosting es compartido.
4. **Cálido y claro.** Mensajes breves, en español, con tono literario pero directo.
5. **Consistencia.** Todo componente nuevo reutiliza los tokens de este documento.

---

## 2. Paleta de colores

### Colores base

| Token | Hex | Uso |
|---|---|---|
| `bosque` | `#2F4A3C` | Barra superior, barra lateral, títulos principales, textos sobre fondos claros destacados |
| `salvia` | `#7A9A7E` | Acentos suaves, estados hover secundarios, esqueletos de carga |
| `oliva` | `#556B4F` | Botón primario, enlaces, acentos activos |
| `madera` | `#6B4F3A` | Baldas de estantería, bordes de inputs, iconografía secundaria |
| `madera-clara` | `#8B6B4F` | Vetas y brillos de la madera, bordes suaves |
| `pergamino` | `#F3E9D2` | Fondo principal de la app, paneles de modal |
| `crema` | `#FAF4E6` | Fondo de tarjetas, inputs y superficies elevadas |
| `tinta` | `#2B2620` | Texto principal, fondos de toast |
| `terracota` | `#A9714B` | Acciones destructivas, advertencias |
| `dorado` | `#C2A878` | Estrellas, anillo de foco, detalles decorativos |

### Colores semánticos

| Token | Valor | Uso |
|---|---|---|
| `surface` | `pergamino` | Fondo de página |
| `surface-raised` | `crema` | Tarjetas, modales, inputs |
| `text` | `tinta` | Texto principal |
| `text-muted` | `tinta` al 65 % | Texto secundario, metadatos |
| `border` | `madera` al 35 % | Bordes y separadores |
| `primary` | `oliva` | Acción principal |
| `primary-hover` | `#465A41` | Hover de la acción principal |
| `danger` | `terracota` | Eliminar, errores |
| `focus` | `dorado` | Anillo de foco |

### Reglas de contraste

- Texto `tinta` sobre `pergamino` y `crema`: cumple AA.
- Texto `crema` sobre `bosque` y `oliva`: cumple AA.
- Nunca usar `dorado` como color de texto pequeño sobre fondos claros; reservarlo para iconos, estrellas y bordes.
- `terracota` no se usa como fondo de texto largo; solo como acento o botón con texto `crema`.

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

---

## 5. Texturas

- **Papel:** grano sutil con SVG `feTurbulence` en opacidad 0.03–0.05 sobre `pergamino`. Nunca superpuesto al texto.
- **Madera:** degradado vertical `#6B4F3A → #8B6B4F` con vetas hechas con `repeating-linear-gradient` a baja opacidad. Sin imágenes bitmap.
- **Peso:** ninguna textura debe superar 50 KB en total; preferir CSS/SVG inline.

---

## 6. Componentes

### Navegación

- **Escritorio:** barra lateral fija de 240 px, fondo `bosque`, texto `crema`. Logo en Cormorant. Enlaces con icono y etiqueta.
- **Móvil:** barra inferior fija de 56 px, fondo `bosque`, tres acciones: Biblioteca, Añadir, Configuraciones. Zona segura inferior respetada.
- Enlace activo: fondo `bosque` aclarado al 10 % y borde inicial `dorado` (escritorio) o subrayado `dorado` (móvil).

### Botones

- **Primario:** fondo `oliva`, texto `crema`, radio 4 px, altura mínima 44 px. Hover `primary-hover`. Foco con anillo `dorado` de 2 px.
- **Secundario:** fondo transparente, borde `madera`, texto `madera`. Hover con fondo `madera` al 8 %.
- **Peligro:** borde `terracota`, texto `terracota`; en confirmaciones, fondo `terracota` y texto `crema`.
- **Icono:** cuadrado de 44 × 44 px, sin borde, hover con fondo `madera` al 8 %.

### Inputs

- Fondo `crema`, borde 1 px `border`, radio 4 px, altura 44 px.
- Texto `tinta`; placeholder `text-muted`.
- Foco: borde `dorado` + anillo de 2 px al 35 %.
- Error: borde `terracota` y mensaje `small` en `terracota` debajo.

### Tarjeta de libro (`BookCard`)

- Proporción de portada 2:3, radio 4 px, sombra suave.
- Hover: `translateY(-2px)` y sombra de portada, 150 ms.
- Título debajo en `book-title`, autor en `small` y `text-muted`.
- Sin portada: fondo `crema`, borde `madera` al 35 %, título y autor centrados en Cormorant.
- Portada con `alt="Portada de {título}"`.

### Lomo (`BookSpine`)

- Rectángulo vertical de 140–180 px de alto y 18–32 px de ancho.
- Ancho y color derivados de un hash estable del título dentro de la paleta (oliva, bosque, madera, terracota, dorado oscuro).
- Título rotado 90° si cabe; si no, solo tooltip y `aria-label`.
- Al pasar el cursor: ligera elevación y tooltip con título y autor.

### Estantería (`Shelf` + `ShelfBoard`)

- Contenedor con título `h2`, contador de libros y control de modo (portadas/lomos).
- **Modo portadas:** rejilla con `auto-fill`, mínimo 88 px por portada, separación 12–20 px; máximo 7 columnas en escritorio. Los libros se alinean al fondo de la balda.
- **Modo lomos:** fila horizontal con scroll si hace falta; libros apoyados en la balda.
- **Balda:** franja de 12–16 px de alto con degradado de madera, borde superior `madera-clara` y sombra inferior. Debe parecer que sostiene los libros.
- Estado vacío: `EmptyShelf` con una frase breve y enlace para añadir.

### Valoración (`RatingStars`)

- Cinco estrellas de 20 px en `dorado`; vacías con borde `dorado` al 40 %.
- Editable con teclado (flechas) y accesible (`role="radiogroup"`).

### Modales

- Panel `crema`, radio 12 px, sombra de modal, ancho máximo 560 px (640 px para el buscador).
- Fondo oscurecido `rgba(43, 38, 32, 0.55)`.
- Título en Cormorant `h2` color `bosque`; cierre con botón y tecla Escape.
- En móvil ocupan el ancho completo con márgenes de 16 px.

### Toasts

- Fondo `tinta`, texto `crema`, radio 6 px, duración 4 s.
- Éxito con acento `salvia`; error con acento `terracota`.
- `aria-live="polite"`.

### Esqueletos de carga

- Bloques `salvia` al 20 % con animación de brillo de 1.2 s. Con `prefers-reduced-motion`, sin animación.

---

## 7. Estructura de pantallas

### Biblioteca (`Library/Index`)

1. Cabecera con título “Mi biblioteca” y botón **Añadir libro**.
2. Lista de estanterías en orden, cada una con su balda y libros.
3. Estado vacío general si no hay libros ni estanterías.

### Añadir/editar libro

- Modal con pestañas **Buscar** y **Manual**.
- En **Buscar**: escáner, ISBN, búsqueda por título/autor y resultados.
- Pie fijo con selector de estantería y botón **Guardar**.

### Configuraciones

- Secciones apiladas: Perfil, Contraseña, Estanterías, Idioma, Vista por defecto.
- Guardado por sección con confirmación visual.
- **Idioma:** selector con búsqueda, revisados primero (etiqueta “Revisado”) y resto con “Automático”. Al elegir un idioma automático se muestra el progreso de preparación y, si falla, un aviso en `terracota` sin cambiar el idioma activo. Los nombres de estanterías se muestran traducidos, pero en esta sección se editan siempre en su idioma original.

### Autenticación

- Fondo `pergamino` con textura de papel.
- Tarjeta centrada `crema` de 420 px máximo, título en Cormorant.
- Enlaces en `oliva`; errores en `terracota`.

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
- Hover de portada: elevación de 2 px.
- Aparición de modales: opacidad + escala de 0.98 a 1.
- Respetar `prefers-reduced-motion: reduce`: sin transformaciones ni brillos animados.

---

## 10. Accesibilidad

- Contraste AA en texto y controles.
- Foco visible con anillo `dorado` de 2 px y offset 2 px.
- Navegación completa con teclado; modales con trampa de foco y Escape.
- `aria-label` en botones de icono, estrellas y lomos sin texto visible.
- Textos alternativos descriptivos en portadas.
- No usar color como único indicador de estado.

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
