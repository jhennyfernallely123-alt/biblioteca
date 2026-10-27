# Biblioteca — Diseño de la aplicación (MVP)

**Fecha:** 2026-10-22
**Estado:** Diseño aprobado por la usuaria. Implementado y verificado el 2026-10-22.
**Documento de identidad visual:** `docs/DISENO.md` (documento vivo, de cumplimiento obligatorio).
**Reglas del proyecto para agentes:** `AGENTS.md`.

---

## 1. Objetivo

Crear una aplicación web responsive con estética de biblioteca antigua (verdes y tonos tierra) en la que cada persona pueda registrar sus libros, organizarlos en estanterías y ver sus portadas como en una biblioteca real. Los libros se pueden añadir escaneando el código de barras, escribiendo el ISBN o buscando por título/autor, y también de forma manual.

La aplicación es de uso personal, con cuentas privadas: cada usuario ve únicamente su propia biblioteca.

## 2. Alcance del MVP

### Incluido

- Registro, inicio y cierre de sesión con correo y contraseña.
- Recuperación de contraseña por correo electrónico.
- Biblioteca privada por usuario.
- Estanterías personalizables: crear, renombrar, reordenar y eliminar.
- Estanterías iniciales al registrarse: **Pendientes**, **Leyendo**, **Leídos**.
- Alta de libros mediante:
  - Escaneo de ISBN/código de barras con la cámara.
  - Búsqueda por ISBN escrito a mano.
  - Búsqueda por título o autor.
  - Formulario manual.
- Datos del libro: título, autor, portada, ISBN, editorial, año, valoración (0–5 estrellas) y notas.
- Portadas visibles en la estantería, con dos modos por estantería: **portadas** y **lomos**.
- Mover un libro entre estanterías, editarlo y eliminarlo.
- Configuraciones: perfil, cambio de contraseña, gestión de estanterías y vista por defecto.
- Diseño responsive (móvil, tablet y escritorio).
- Pruebas automatizadas del backend y de los componentes principales del frontend.

### Fuera del MVP (fases posteriores)

- Inicio de sesión con Google y Facebook.
- Personalización visual avanzada de la estantería: espacio entre libros, objetos decorativos e imágenes generadas.
- Arrastrar y soltar libros entre estanterías.
- Fechas de lectura, progreso de páginas y préstamos.
- Bibliotecas públicas o compartidas.
- Aplicación móvil nativa y PWA instalable.
- Sincronización o importación/exportación masiva.

## 3. Usuarios y autenticación

- **Registro:** nombre, correo y contraseña (mínimo 8 caracteres, confirmación).
- **Inicio de sesión:** correo y contraseña, con límite de intentos.
- **Recuperación de contraseña:** enlace por correo con caducidad, usando el flujo estándar de Laravel.
- **Verificación de correo:** no se exige en el MVP.
- **Sesiones:** controladas por Laravel; todas las rutas privadas requieren autenticación.
- **Aislamiento:** todas las consultas de estanterías y libros se filtran por `user_id`. Un usuario no puede ver ni modificar datos de otro, ni siquiera conociendo el identificador.
- **Fase 2:** Google y Facebook con Laravel Socialite.

## 4. Modelo de datos

Base de datos MySQL: `biblioteca`.

### `users`

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint unsigned, PK | |
| name | string(120) | |
| email | string(255), único | |
| password | string | hash |
| remember_token | string(100), nullable | |
| default_view_mode | enum(`covers`,`spines`), default `covers` | vista de las nuevas estanterías |
| created_at / updated_at | timestamp | |

### `shelves`

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint unsigned, PK | |
| user_id | FK → users, cascade | dueño |
| name | string(60) | |
| position | unsigned int, default 0 | orden |
| view_mode | enum(`covers`,`spines`), default `covers` | modo de visualización |
| created_at / updated_at | timestamp | |

Índice: `(user_id, position)`.

### `books`

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint unsigned, PK | |
| user_id | FK → users, cascade | dueño |
| shelf_id | FK → shelves, restrict | se mueve antes de borrar estantería |
| title | string(255), obligatorio | |
| author | string(255), nullable | |
| isbn | string(20), nullable | normalizado, indexado |
| publisher | string(150), nullable | |
| published_year | smallint unsigned, nullable | |
| cover_path | string(255), nullable | archivo local en disco `public` |
| rating | tinyint unsigned, nullable | 0–5 |
| notes | text, nullable | |
| created_at / updated_at | timestamp | |

Índices: `(user_id, shelf_id)`, `(user_id, isbn)`.

### Reglas de datos

- Al registrar un usuario se crean sus tres estanterías iniciales dentro de una transacción.
- Eliminar una estantería requiere confirmación; sus libros se mueven a **Pendientes** antes de eliminar la estantería. Si no existe “Pendientes”, se usa la primera estantería del usuario; si el usuario no tuviera ninguna, se crea “Pendientes”.
- Eliminar un libro borra también su archivo de portada local.
- La posición de las estanterías se recalcula al crear, reordenar o eliminar.

## 5. Funcionalidades y flujos

### 5.1 Vista Biblioteca

- Muestra todas las estanterías del usuario, en orden.
- Cada estantería muestra sus libros en el modo elegido (portadas o lomos).
- Cabecera con título de la app, acceso a Configuraciones y botón **Añadir libro**.
- Estado vacío: mensaje breve y botón para añadir el primer libro.
- Paginación no incluida en el MVP; el diseño debe permitir añadirla después sin reestructurar.

### 5.2 Añadir libro

Modal o página con dos pestañas:

1. **Buscar**
   - Escáner de código de barras con la cámara (EAN-13, UPC-A e ISBN).
   - Campo de ISBN manual.
   - Campo de búsqueda por título o autor.
   - Resultados con portada, título, autor, año y editorial; al seleccionar uno se rellena el formulario.
2. **Manual**
   - Título (obligatorio), autor, ISBN, editorial, año, portada y estantería destino.

Antes de guardar siempre se elige la estantería. El usuario puede editar los datos precargados. Si el libro ya existe en su biblioteca (mismo ISBN), se avisa y se permite guardarlo igualmente.

### 5.3 Escáner de código de barras

- Implementado en el navegador con `@zxing/browser`.
- Requiere HTTPS o `localhost` (restricción del navegador para la cámara).
- Si se deniega el permiso, no hay cámara o el navegador no es compatible, se muestra el campo de ISBN manual.
- La cámara se detiene al cerrar el escáner o al desmontar el componente.
- Tras detectar un código se valida el ISBN y se lanza la búsqueda automáticamente.

### 5.4 Búsqueda de metadatos

Orden de consulta:

1. **Google Books** (`https://www.googleapis.com/books/v1/volumes`).
2. **Open Library** como respaldo (`https://openlibrary.org/api/books` y `https://openlibrary.org/search.json`).

- Las llamadas se hacen desde el servidor (nunca desde el navegador) para proteger la clave y evitar CORS.
- Si existe `GOOGLE_BOOKS_API_KEY`, se usa; si no, se consulta el endpoint público.
- Tiempo máximo por proveedor: 5 segundos.
- Resultados cacheados 24 horas.
- Los títulos se muestran en español aunque la edición encontrada esté en otro idioma: se traduce el título con MyMemory y se conserva el original para referencia. Si la traducción falla, se mantiene el título original. `MYMEMORY_EMAIL` es opcional y amplía la cuota diaria del servicio.
- Si ningún proveedor responde o no hay resultados, se muestra un mensaje claro y se ofrece el alta manual.
- Al guardar un libro con portada remota, el servidor descarga la imagen, valida que sea JPG/PNG/WebP de hasta 5 MB y la guarda en el disco `public`. Si la descarga falla, el libro se guarda sin portada y se muestra la portada provisional. No se almacenan URL externas.

### 5.5 Detalle y edición del libro

- Ver portada, datos, valoración y notas.
- Editar cualquier campo, cambiar portada, mover a otra estantería o eliminar con confirmación.

### 5.6 Estanterías

- Crear con nombre (máximo 60 caracteres, no vacío, único por usuario).
- Renombrar y cambiar el modo de vista.
- Reordenar con botones subir/bajar (sin arrastrar en el MVP).
- Eliminar con confirmación y reubicación de libros.

### 5.7 Configuraciones

- **Perfil:** nombre y correo.
- **Contraseña:** contraseña actual + nueva + confirmación.
- **Estanterías:** misma gestión que en la biblioteca.
- **Vista por defecto:** portadas o lomos para nuevas estanterías.
- La recuperación de contraseña se hace desde la pantalla de inicio de sesión.

### 5.8 Navegación

- **Móvil (< 768 px):** barra inferior con Biblioteca, Añadir y Configuraciones.
- **Escritorio (≥ 768 px):** barra lateral con las mismas acciones y acceso rápido a estanterías.

## 6. Identidad visual

La fuente de verdad es `docs/DISENO.md`. Resumen:

- Paleta: verdes bosque/salvia/oliva, maderas, pergamino/crema, tinta, terracota y dorado envejecido.
- Tipografías: Cormorant Garamond para títulos y nombres de libros; Inter para la interfaz.
- Texturas suaves de papel y madera con CSS/SVG, sin imágenes pesadas.
- Portadas con sombra y elevación al pasar el cursor; baldas de madera con degradado.
- Movimiento discreto (150–200 ms) y respeto a `prefers-reduced-motion`.

Cualquier cambio de color, tipografía, componente o estructura visual debe registrarse en la tabla de cambios de `docs/DISENO.md` antes de darse por terminado.

## 7. Arquitectura técnica

- **Backend:** Laravel 13 (PHP 8.3–8.5).
- **Frontend:** Inertia.js + Vue 3 + Tailwind CSS 4, compilado con Vite.
- **Autenticación:** Laravel Breeze (stack Vue/Inertia).
- **Base de datos:** MySQL, base `biblioteca`.
- **Correo:** SMTP externo (Brevo, Gmail con contraseña de aplicación o Zoho) en el puerto 587 con TLS. En local se usa Mailpit o el driver `log`.
- **Almacenamiento de portadas:** disco `public` de Laravel; se sirven mediante una ruta autenticada que comprueba la propiedad, sin depender de enlaces simbólicos (no disponibles en InfinityFree).
- **Caché y sesiones:** archivos o base de datos; sin Redis.
- **Colas:** síncronas (`QUEUE_CONNECTION=sync`); el hosting no ejecuta workers.
- **Tareas programadas:** no imprescindibles para el MVP.

### Estructura de carpetas relevante

```
app/
  Http/Controllers/
    ShelfController.php
    BookController.php
    BookLookupController.php
    SettingsController.php
    CoverController.php
  Models/
    User.php
    Shelf.php
    Book.php
  Services/
    GoogleBooksService.php
    OpenLibraryService.php
    BookLookupService.php
    Isbn.php
  Policies/
    ShelfPolicy.php
    BookPolicy.php
resources/js/
  Pages/
    Auth/
    Library/Index.vue
    Books/Create.vue
    Books/Edit.vue
    Settings/Index.vue
  Components/
    AppShell.vue
    Shelf.vue
    ShelfBoard.vue
    BookCover.vue
    BookCard.vue
    BookSpine.vue
    BarcodeScanner.vue
    RatingStars.vue
    EmptyShelf.vue
    Modal.vue
    Toast.vue
  layouts/
    AuthenticatedLayout.vue
    GuestLayout.vue
database/migrations/
  create_shelves_table.php
  create_books_table.php
```

## 8. Errores y validación

- Validación siempre en el servidor; los errores se muestran junto a cada campo.
- ISBN: se eliminan guiones y espacios y se valida el dígito verificador (ISBN-10/13). Si es inválido, no se consulta y se explica el motivo.
- Búsqueda sin resultados o proveedores caídos: mensaje claro y opción manual.
- Portada remota inaccesible: portada provisional con título y autor sobre fondo pergamino.
- Portada subida: solo imágenes JPG, PNG o WebP de hasta 5 MB; se valida el contenido real, no solo la extensión.
- Cámara denegada o no disponible: entrada manual de ISBN.
- Acceso a datos de otro usuario: respuesta 403 (probado con tests).
- Error de envío de correo: se registra en el log y se muestra un mensaje sin detalles técnicos.
- Eliminación de estantería: confirmación que explica que los libros se moverán a “Pendientes”.
- Sesión expirada (419) y página inexistente (404): páginas propias con el estilo de la app.

## 9. Pruebas

### Backend (Pest/PHPUnit)

- Registro crea usuario y sus tres estanterías.
- Login, logout y recuperación de contraseña.
- CRUD de estanterías, incluyendo reordenar y eliminar con reubicación de libros.
- CRUD de libros y movimiento entre estanterías.
- Aislamiento entre usuarios en estanterías, libros y portadas.
- Validación de ISBN (unitario).
- `BookLookupService`: Google Books, respaldo en Open Library, sin resultados y timeout, con `Http::fake()`.
- Subida y servido de portadas, incluida la denegación a terceros.
- Límite de intentos de login.

### Frontend (Vitest + Vue Test Utils)

- `RatingStars` (lectura y edición).
- `BookCard` en modo portada y lomo.
- `EmptyShelf`.
- `BarcodeScanner` con la librería simulada (permiso concedido y denegado).

### Manual

- Flujo completo en móvil (360 px) y escritorio.
- Escáner sobre HTTPS o `localhost`.
- Recuperación de contraseña con el proveedor SMTP real antes del despliegue.

## 10. Despliegue

### Local

- PHP 8.3+, Composer, Node 20+, MySQL.
- Base de datos `biblioteca` creada en phpMyAdmin.
- `php artisan migrate --seed` y `npm run dev`.

### InfinityFree

- PHP 8.4 y MySQL disponibles; sin Node.js ni SSH.
- Assets de Vite compilados en local y subidos.
- Se usa el flujo del skill `deploy-infinityfree`: `deploy.sh`, `.deploy-env`, endpoints de migración y parcheo de `platform_check.php`.
- **La usuaria hace los commits.** El agente no ejecuta `git add`, `git commit` ni `git push` salvo petición explícita.
- HTTPS obligatorio para la cámara y las cookies seguras.
- SMTP externo (puerto 587/TLS); la función `mail()` de PHP está deshabilitada.
- Sin enlaces simbólicos: las portadas se sirven por ruta autenticada.
- Riesgo puntual: InfinityFree puede bloquear temporalmente peticiones `multipart/form-data` durante incidentes de seguridad, lo que afecta subidas de portada. No es un defecto del código.

## 11. Decisiones y riesgos

| Decisión | Motivo |
|---|---|
| Laravel + Inertia + Vue en un solo proyecto | Menos complejidad de despliegue en hosting compartido y experiencia SPA. |
| Búsqueda en servidor con Google Books + Open Library | Protege la clave, evita CORS y ofrece respaldo. |
| Portadas locales servidas por ruta autenticada | Evita enlaces simbólicos y enlaces externos roto. |
| SMTP externo en lugar de `mail()` | `mail()` está deshabilitado en InfinityFree. |
| Sin colas ni workers | El hosting compartido no los ofrece. |
| Commits manuales de la usuaria | Regla explícita del proyecto. |

Riesgos principales: cuotas de Google Books, disponibilidad de la cámara/HTTPS, límites de correo del proveedor SMTP y rendimiento del hosting compartido. Todos tienen respaldo o mensaje claro para el usuario.

## 12. Criterios de aceptación del MVP

1. Una persona puede registrarse, iniciar sesión, cerrar sesión y recuperar su contraseña.
2. Cada usuario ve únicamente sus estanterías y libros.
3. Al registrarse existen las estanterías Pendientes, Leyendo y Leídos.
4. Se pueden crear, renombrar, reordenar y eliminar estanterías; al eliminar, los libros pasan a Pendientes.
5. Se puede añadir un libro escaneando el código, escribiendo el ISBN, buscando por título/autor o manualmente.
6. La portada se ve en la estantería en modo portadas y el libro se puede identificar en modo lomos.
7. Se puede valorar, anotar, mover y eliminar un libro.
8. La interfaz funciona correctamente desde 360 px y en escritorio, con la identidad visual de `docs/DISENO.md`.
9. Las pruebas de backend y frontend pasan.
10. La app está lista para desplegarse en InfinityFree siguiendo el flujo documentado.
