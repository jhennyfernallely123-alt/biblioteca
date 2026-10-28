# Biblioteca — Diseño del multilingüe

**Fecha:** 2026-10-26
**Estado:** Diseño aprobado por la usuaria; pendiente de implementación.
**Documento de identidad visual:** `docs/DISENO.md` (documento vivo, de cumplimiento obligatorio).
**Reglas del proyecto para agentes:** `AGENTS.md`.

---

## 1. Objetivo

Permitir que cada usuaria elija el idioma de la aplicación desde Configuraciones. El cambio debe afectar a toda la interfaz autenticada, los mensajes de validación y error, los títulos de los resultados de búsqueda de libros y los nombres de las estanterías. Se incluyen los idiomas de derecha a izquierda (RTL).

Además, se corrige el fallo actual: `TranslationService` existe pero no está conectado, por lo que los títulos de búsqueda nunca se traducen.

## 2. Alcance

### Incluido

- Selector de idioma en Configuraciones con búsqueda.
- Todos los idiomas soportados por MyMemory (alrededor de 200 códigos).
- Ocho idiomas con traducción revisada: español, inglés, portugués, francés, alemán, italiano, catalán y gallego.
- Traducción automática de la interfaz para el resto de idiomas, generada una sola vez y guardada en caché.
- Traducción de mensajes de validación y de los mensajes de autenticación/contraseña que se muestran dentro de la aplicación autenticada (por ejemplo, al cambiar la contraseña).
- Traducción de los títulos de resultados de Google Books y Open Library al idioma activo.
- Nombres de estanterías traducidos al mostrarse en la biblioteca y en los formularios de libros, conservando el nombre original en la base de datos y en Configuraciones.
- Soporte básico RTL para árabe, hebreo, persa, urdu y demás idiomas de derecha a izquierda.
- Persistencia del idioma por usuario.
- Pruebas de backend y frontend.
- Actualización de `docs/DISENO.md` al terminar, si hubo cambios visuales.

### Fuera de alcance

- Traducción de las pantallas de login, registro, recuperación y verificación: permanecen siempre en español.
- Traducción de correos enviados por el framework.
- Traducción de páginas de error 404/500.
- Traducción de los títulos de libros ya guardados en la biblioteca: son datos de la usuaria y se conservan tal como se guardaron.
- Traducción de autores, editoriales, notas, correos ni nombres de usuario.
- Editor de traducciones para la usuaria.
- Detección automática del idioma del navegador.
- Traducciones profesionales de todos los idiomas; los idiomas no revisados usan traducción automática.

## 3. Arquitectura general

Se usa un **catálogo único de claves planas** compartido por Laravel y Vue.

```text
lang/es.json                  catálogo maestro de la interfaz (español)
lang/{locale}.json            traducciones revisadas (8 idiomas)
storage/app/i18n/{locale}.json traducciones automáticas generadas
config/languages.php          idiomas soportados, revisados y RTL
```

- Laravel registra `storage/app/i18n` como ruta de traducciones JSON.
- Laravel y Vue usan las mismas claves: `ui.add_book`, `ui.settings`, `validation.required`, etc.
- El español es el idioma de respaldo (`fallback`) y la fuente de las traducciones automáticas.
- Las traducciones automáticas no se generan en cada petición: se preparan al elegir el idioma y quedan guardadas.

## 4. Catálogo de textos

### 4.1 Claves

- `ui.*`: todos los textos visibles de la aplicación autenticada.
- `validation.*`: mensajes de validación y nombres de atributos.
- `auth.*`: mensajes de autenticación.
- `passwords.*`: mensajes de recuperación de contraseña.

Las claves no se traducen nunca. Los textos visibles solo existen en los archivos de traducción.

### 4.2 Catálogo maestro

`lang/es.json` contiene todas las claves `ui.*` en español. Es la fuente para:

- La interfaz cuando el idioma es español.
- La generación automática del resto de idiomas.

Los mensajes de validación, autenticación y contraseñas se obtienen de `lang/es/validation.php`, `lang/es/auth.php` y `lang/es/passwords.php`, y se aplanan a claves como `validation.required` o `validation.attributes.title`.

### 4.3 Traducciones revisadas

Archivos `lang/en.json`, `lang/pt.json`, `lang/fr.json`, `lang/de.json`, `lang/it.json`, `lang/ca.json` y `lang/gl.json`.

Deben contener todas las claves del catálogo completo (interfaz + validación + autenticación + contraseñas). Una prueba automática verifica que no falte ninguna clave.

### 4.4 Traducciones automáticas

Se guardan en `storage/app/i18n/{locale}.json`. Se crean con MyMemory a partir del español y se validan antes de usarse.

Cada archivo contiene todas las claves del catálogo. Si falta una sola clave, el idioma no se activa.

## 5. Idiomas soportados

`config/languages.php` define:

- `supported`: mapa de código canónico a código de MyMemory. Ejemplo: `en` → `en-GB`, `pt` → `pt-PT`, `zh` → `zh-CN`.
- `curated`: los ocho códigos con traducción revisada.
- `rtl`: códigos que se muestran de derecha a izquierda.

Los códigos canónicos son los que se guardan en `users.locale`. La aplicación web muestra los nombres con `Intl.DisplayNames` en el idioma activo, con el código como respaldo.

La lista incluye los idiomas de MyMemory documentados en su API; no se aceptan códigos fuera de `supported`.

## 6. Base de datos y sesión

### 6.1 Migración

Añadir a `users`:

| Campo | Tipo | Notas |
|---|---|---|
| `locale` | string(10), default `es` | código canónico del idioma |

Los usuarios existentes quedan en `es`.

### 6.2 Modelo

- `locale` se añade a los atributos rellenables de `User`.
- El valor por defecto es `es`.

### 6.3 Middleware

Un middleware `SetLocale` se ejecuta en el grupo `web`:

- Si hay usuaria autenticada: usa `users.locale` si está soportado; si no, `es`.
- Si no hay usuaria autenticada: usa `es` siempre.
- Aplica `App::setLocale($locale)`.
- Comparte con Inertia: `locale`, `direction` (`ltr` o `rtl`) y `translations` (solo claves `ui.*`).

El login y el registro no se traducen porque los invitados siempre usan `es`.

## 7. Flujo de cambio de idioma

1. La usuaria abre Configuraciones y elige un idioma en el selector.
2. Pulsa «Aplicar idioma».
3. Si el idioma es revisado o su catálogo ya existe:
   - Se actualiza `users.locale`.
   - Se recarga la página con el nuevo idioma.
4. Si el idioma es automático y no existe:
   - Se muestra «Preparando idioma…» con progreso.
   - El frontend pide las claves que faltan en lotes.
   - El backend traduce cada lote con MyMemory y guarda el avance.
   - Al completar todas las claves, se escribe el archivo final y se actualiza `users.locale`.
5. Si algo falla:
   - No se cambia el idioma.
   - Se conserva el idioma anterior.
   - Se muestra un mensaje de error y se permite reintentar.
   - Las traducciones ya obtenidas se conservan para el siguiente intento.

El cambio de idioma nunca deja la aplicación a medio traducir.

## 8. Pantalla de Configuraciones

Se añade una sección «Idioma» con:

- Selector buscable con todos los idiomas.
- Los ocho idiomas revisados aparecen primero y con la etiqueta «Revisado».
- El resto aparece con la etiqueta «Automático».
- Nombre del idioma en el idioma activo y, si se puede, nombre nativo.
- Botón «Aplicar idioma».
- Estado de progreso durante la preparación.
- Mensaje de éxito o de error.

El selector es un componente nuevo `LanguageSelect.vue`, accesible con teclado y responsive a 360 px.

Los nombres de las estanterías en Configuraciones se muestran en su forma original para poder editarlos. En la biblioteca y en los formularios de libros se muestra su traducción.

## 9. Traducción del contenido

### 9.1 Resultados de búsqueda de libros

- `TranslationService` se conecta a `BookLookupService`.
- El destino es el idioma activo de la usuaria, no siempre español.
- Se añade `language` al resultado de Google Books; Open Library aporta el idioma cuando esté disponible.
- Si el idioma del libro coincide con el idioma activo, no se traduce.
- Si la traducción falla, se conserva el título original.
- Se guarda `original_title` para trazabilidad.
- La caché de búsqueda se separa de la caché de traducción:
  - Resultados crudos por ISBN o consulta.
  - Traducciones por idioma y título.
- Esto evita que una búsqueda hecha en español devuelva títulos en español a una usuaria en inglés.

### 9.2 Nombres de estanterías

- El nombre original permanece intacto en la base de datos.
- Al mostrarse en la biblioteca y en los formularios de libros, se traduce al idioma activo.
- En Configuraciones se muestra el original para editarlo.
- Si la traducción falla, se muestra el original.
- La caché de traducción de estanterías es independiente por idioma y texto.
- Los nombres predeterminados («Pendientes», «Leyendo», «Leídos») se traducen igual que los personalizados.

### 9.3 Títulos guardados

Los libros ya guardados conservan su título tal como se guardó. Cambiar de idioma no reescribe títulos de la biblioteca.

## 10. Dirección RTL y tipografía

- `config/languages.php` marca los idiomas RTL.
- El elemento `<html>` recibe `lang` y `dir` correctos.
- Al navegar con Inertia se actualizan `document.documentElement.lang` y `dir`.
- Los componentes tocados usan utilidades lógicas de Tailwind (`ms-*`, `me-*`, `ps-*`, `pe-*`, `start-*`, `end-*`) en lugar de `ml-*`, `mr-*`, `left-*`, `right-*` cuando corresponda.
- Se revisan navegación, menús, formularios, tarjetas y la barra de estanterías en RTL.
- No se añaden fuentes nuevas. Para alfabetos no latinos se usa la fuente del sistema como respaldo.
- El soporte RTL es básico: dirección correcta y layout principal usable; los detalles visuales menores se pueden pulir después.

## 11. Endpoints

Todos requieren autenticación y validación de `locale` contra `config/languages.php`.

| Método | Ruta | Función |
|---|---|---|
| `POST` | `/settings/locale/prepare` | Indica si el catálogo está listo y devuelve las claves que faltan |
| `POST` | `/settings/locale/translate` | Traduce un lote de claves y guarda el avance |
| `PATCH` | `/settings/locale` | Finaliza, valida el catálogo completo y guarda `users.locale` |

- `prepare` devuelve `ready`, `missing` y un primer lote de claves.
- `translate` acepta un máximo de 20 claves por petición.
- `PATCH` rechaza el cambio si el catálogo está incompleto.
- Los endpoints tienen throttling para evitar abusos.
- Las claves enviadas por el cliente se validan contra el catálogo maestro; no se traduce texto arbitrario.
- Las respuestas de error usan los mensajes de validación traducidos.

## 12. Errores, límites y seguridad

- MyMemory gratuito permite 5.000 caracteres al día sin correo y 50.000 con `MYMEMORY_EMAIL` configurado. Se documenta en `.env.example` y en `docs/DESPLIEGUE.md`.
- La generación se hace una sola vez por idioma. Si se agota el límite, se conserva el idioma anterior y se informa.
- Se detectan respuestas de advertencia de MyMemory y se tratan como fallo.
- Se protegen los marcadores (`:attribute`, `:max`, `:name`, etc.) y los separadores de plural antes de enviar texto a MyMemory. Si al restaurarlos falta un marcador, la traducción se considera inválida.
- No se envían a MyMemory datos personales: solo textos de interfaz, mensajes del framework y nombres de estanterías.
- Las traducciones automáticas se guardan en `storage/app/i18n`, fuera de `public`, y se sirven solo a través de la aplicación.
- El cambio de idioma está protegido por autenticación; ninguna usuaria puede modificar el idioma de otra.
- Los archivos generados no se sirven por URL directa.

## 13. Pruebas

### Backend (Pest)

- Migración y valor por defecto de `users.locale`.
- Middleware: usuaria autenticada usa su idioma; invitada usa español; idioma inválido cae en español.
- Configuraciones: cambio a idioma revisado, rechazo de idioma no soportado y persistencia.
- Catálogo: claves completas en los ocho idiomas revisados.
- Generación automática con `Http::fake`: preparación, lotes, finalización y reanudación.
- Fallo de MyMemory: no se cambia el idioma y se conserva el anterior.
- Protección y restauración de marcadores.
- Títulos de búsqueda traducidos al idioma activo; sin traducción si el idioma coincide.
- Caché de búsqueda independiente del idioma.
- Estanterías: original intacto, traducción al mostrar, original en Configuraciones y respaldo si falla.
- Autorización: no se puede cambiar el idioma de otra cuenta.

### Frontend (Vitest)

- `useI18n`: interpolación, respaldo y cambio de claves.
- `LanguageSelect`: búsqueda, selección, etiquetas de revisado/automático y accesibilidad básica.
- Configuraciones: progreso, éxito, error y conservación del idioma anterior.
- Dirección `dir` y `lang` del documento.

### Manual

- 360 px y escritorio.
- Alemán (textos largos), árabe (RTL), chino/japonés (alfabeto no latino).
- Login y registro siempre en español.
- Validaciones en varios idiomas.
- Búsqueda de libros en español, inglés y un idioma automático.
- Estanterías predeterminadas y personalizadas.
- `php artisan test`, `npm run test`, `npm run build` y `./vendor/bin/pint`.

## 14. Despliegue en InfinityFree

- `storage/app/i18n` debe existir y tener permisos de escritura.
- No se usan enlaces simbólicos, workers ni Node.js en el servidor.
- Los archivos generados se crean en el servidor la primera vez que se elige un idioma automático.
- `MYMEMORY_EMAIL` se configura por `.env` y se limpia la configuración con el endpoint de deploy existente.
- Si el hosting borra `storage/app/i18n`, la aplicación vuelve a generar el idioma al solicitarlo.
- El build de Vue se hace en local; el servidor solo sirve los assets compilados.

## 15. Documentación y diseño visual

Al terminar la implementación:

- Actualizar `docs/DISENO.md` con la sección de idioma, el componente `LanguageSelect`, las etiquetas revisado/automático, el estado de progreso y el soporte RTL.
- Registrar la fecha y el cambio en la tabla de cambios de `docs/DISENO.md`.
- Actualizar `docs/DESPLIEGUE.md` con `MYMEMORY_EMAIL` y la carpeta `storage/app/i18n`.
- Actualizar `.env.example` con `MYMEMORY_EMAIL`.
- Añadir a `AGENTS.md` la convención de no escribir textos visibles en componentes autenticados: deben usar claves del catálogo.

## 16. Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| MyMemory agota el límite diario | Los idiomas revisados no gastan cuota; los automáticos se generan una sola vez y el avance se conserva. Si falla, no se activa el idioma. |
| Traducción automática de baja calidad | Los ocho idiomas principales son revisados; el resto se marca como «Automático». |
| Textos largos rompen el layout | Revisión manual en alemán y en 360 px; los componentes usan `min-w-0` y permiten varias líneas. |
| RTL deja elementos desalineados | Utilidades lógicas de Tailwind y revisión de navegación, menús y formularios; el soporte es básico y se puede pulir después. |
| Falta una clave en un idioma | Prueba automática de completitud y validación antes de activar el idioma. |
| Marcadores de validación corruptos | Protección y restauración de marcadores; si falta uno, la traducción se descarta. |

## 17. Criterios de aceptación

1. La prueba de traducción de títulos deja de fallar.
2. Una usuaria puede elegir cualquiera de los idiomas soportados desde Configuraciones.
3. La interfaz autenticada, las validaciones y los títulos de búsqueda cambian al idioma elegido.
4. El catálogo de interfaz de los ocho idiomas revisados funciona sin llamadas a MyMemory.
5. Un idioma automático se prepara con progreso y, si falla, no se activa.
6. El idioma se conserva al cerrar sesión y volver a entrar.
7. Los invitados ven login y registro en español.
8. Las estanterías muestran su traducción sin perder el nombre original.
9. Los idiomas RTL se muestran de derecha a izquierda con un layout usable.
10. `php artisan test`, `npm run test`, `npm run build` y Pint pasan.
11. `docs/DISENO.md` refleja todos los cambios visuales.
