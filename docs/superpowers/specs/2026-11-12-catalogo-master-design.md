# Biblioteca — Catálogo master de libros ausentes

**Fecha:** 2026-11-12
**Estado:** Implementado y verificado.
**Documento de identidad visual:** `docs/DISENO.md` (documento vivo, de cumplimiento obligatorio).
**Reglas del proyecto para agentes:** `AGENTS.md`.

---

## 1. Objetivo

Permitir que la cuenta master de la biblioteca añada libros que no existen en Google Books ni en Open Library, de modo que queden guardados en un catálogo global y cualquier usuaria autenticada pueda encontrarlos al buscar por título, autor o ISBN.

El catálogo global es independiente de las estanterías privadas. Cuando la master añade una ficha, también se crea una copia en la estantería que elija.

## 2. Decisiones aprobadas

- Solo la cuenta marcada como master accede al panel de gestión. No hay postulaciones ni aprobaciones de otras usuarias.
- La master se identifica con `users.is_master`, activado una vez desde phpMyAdmin.
- El catálogo global vive en una tabla aparte, `catalog_books`; no se mezcla con los libros privados de `books`.
- Al crear una ficha se elige una estantería y se crea también una copia personal.
- La búsqueda muestra primero el catálogo local y después los resultados de las APIs, sin deduplicar.
- La master puede editar y eliminar fichas del catálogo.
- Editar o eliminar una ficha no modifica las copias personales ya añadidas a estanterías.
- Los campos de la ficha son los campos bibliográficos del formulario manual actual, sin valoraciones ni notas: título, autor, ISBN, editorial, año y portada (subida o URL).

## 3. Alcance

### Incluido

- Campo `users.is_master` y policy de administración.
- Tabla `catalog_books`, modelo `CatalogBook` y relación con la cuenta creadora.
- Vínculo opcional `books.catalog_book_id` para conservar la procedencia y copiar la portada.
- Panel master para listar, crear, editar y eliminar fichas.
- Copia automática de la ficha a una estantería de la master al crearla.
- Portadas del catálogo servidas por ruta autenticada.
- Búsqueda local por título, autor e ISBN, integrada con las APIs existentes.
- Copia de la portada del catálogo al libro personal, sin compartir el archivo.
- Textos en `lang/es.json` y `lang/en.json`.
- Pruebas Pest y Vitest.
- Registro del cambio visual en `docs/DISENO.md`.

### Fuera de alcance

- Que otras usuarias propongan libros o envíen solicitudes.
- Aprobación o moderación de propuestas.
- Importación masiva desde CSV, Excel u otras fuentes.
- Sincronización de datos entre la ficha del catálogo y las copias personales.
- Página pública de catálogo o navegación por el catálogo sin buscar.
- Traducción automática de los datos introducidos por la master.
- Edición de fichas por parte de otras usuarias.
- Paginación del panel en la primera versión; el catálogo previsto es pequeño.
- Historial de cambios o auditoría de fichas.

## 4. Arquitectura general

```text
Búsqueda (Books/Create)
        |
        v
BookLookupService
   |            |
   v            v
CatalogBook    APIs externas (Google Books / Open Library)
consulta       caché de un día
   |            |
   +-----+------+
         v
Resultados: catálogo primero, API después

Panel master
        |
        v
CatalogBookController
   |            |
   v            v
CatalogBook    Book (copia personal en la estantería elegida)
```

- El catálogo se consulta en cada búsqueda, sin caché, para que una ficha nueva aparezca de inmediato.
- Las APIs mantienen la caché actual (`lookup:v5:isbn:*` y `lookup:v6:q:*`).
- Las portadas del catálogo y de los libros personales son archivos independientes.

## 5. Base de datos

### 5.1 Migración de usuarias

Añadir a `users`:

| Campo | Tipo | Notas |
|---|---|---|
| `is_master` | boolean, default `false` | Solo la cuenta master lo tiene en `true` |

- Las cuentas existentes quedan en `false`.
- El campo se añade a los casts de `User` como booleano.
- La activación en producción se hace una vez desde phpMyAdmin:

```sql
UPDATE users SET is_master = 1 WHERE email = 'correo-de-la-master';
```

### 5.2 Tabla `catalog_books`

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint unsigned, autoincremental | |
| `created_by` | foreignId nullable, FK `users.id` | `nullOnDelete` |
| `title` | string(255) | Obligatorio |
| `author` | string(255) nullable | |
| `isbn` | string(20) nullable, unique | Único cuando no es nulo |
| `publisher` | string(150) nullable | |
| `published_year` | unsignedSmallInteger nullable | |
| `cover_path` | string(255) nullable | Ruta en el disco público |
| `created_at`, `updated_at` | timestamps | |

- La unicidad de `isbn` permite varios `NULL` en MySQL.
- No se añaden índices de texto: el catálogo previsto es pequeño y la búsqueda usa `LIKE`.

### 5.3 Vínculo con libros personales

Añadir a `books`:

| Campo | Tipo | Notas |
|---|---|---|
| `catalog_book_id` | foreignId nullable, FK `catalog_books.id` | `nullOnDelete` |

- Al eliminar una ficha, las copias personales se conservan y quedan con `catalog_book_id = NULL`.
- El vínculo no sincroniza datos; solo identifica la procedencia y permite copiar la portada al añadir el libro.

### 5.4 Migraciones

- `2026_11_12_000001_add_is_master_to_users_table.php`
- `2026_11_12_000002_create_catalog_books_table.php`
- `2026_11_12_000003_add_catalog_book_id_to_books_table.php`

## 6. Modelos y permisos

### 6.1 `CatalogBook`

- `CatalogBook` se refiere al catálogo global de libros; no confundir con `CatalogService`, que gestiona las traducciones de la interfaz.
- `$fillable`: `created_by`, `title`, `author`, `isbn`, `publisher`, `published_year`, `cover_path`.
- Casts: `published_year` a entero.
- Relación `creator(): BelongsTo` con `User`, usando `created_by`.
- Relación `books(): HasMany` con `Book`.
- Incluye `HasFactory` y un `CatalogBookFactory` para las pruebas.

### 6.2 `User`

- `isMaster(): bool` devuelve `(bool) $this->is_master`.
- Relación `catalogBooks(): HasMany` con `CatalogBook`, usando `created_by`.
- **No** añadir `is_master` a los atributos rellenables de `User`: solo se cambia desde la base de datos, nunca desde el perfil.

### 6.3 `Book`

- Añadir `catalog_book_id` a `$fillable`.
- Relación `catalogBook(): BelongsTo` con `CatalogBook`.

### 6.4 Policy

`CatalogBookPolicy`:

- `manage(User $user, ?CatalogBook $catalogBook = null): bool` devuelve `$user->isMaster()`.
- El controlador autoriza con `$this->authorize('manage', CatalogBook::class)` en cada acción del panel.
- Un middleware `EnsureMaster` protege además todo el grupo de rutas del panel y responde `403` si la cuenta no es master.
- El middleware se registra con el alias `master` en `bootstrap/app.php`.

### 6.5 Datos compartidos con Inertia

`HandleInertiaRequests` ya comparte `auth.user`; al añadir `is_master` a `User`, el valor llega al frontend. El enlace del panel se muestra solo cuando `auth.user.is_master` es verdadero.

## 7. Panel master

### 7.1 Rutas

```php
Route::get('/catalog-covers/{catalogBook}', CatalogCoverController::class)
    ->middleware('auth')
    ->name('catalog-covers.show');

Route::middleware(['auth', 'nickname', 'master'])
    ->prefix('catalog')
    ->name('catalog.')
    ->group(function () {
        Route::get('/', [CatalogBookController::class, 'index'])->name('index');
        Route::get('/create', [CatalogBookController::class, 'create'])->name('create');
        Route::post('/', [CatalogBookController::class, 'store'])->name('store');
        Route::get('/{catalogBook}/edit', [CatalogBookController::class, 'edit'])->name('edit');
        Route::patch('/{catalogBook}', [CatalogBookController::class, 'update'])->name('update');
        Route::delete('/{catalogBook}', [CatalogBookController::class, 'destroy'])->name('destroy');
    });
```

### 7.2 Acceso visible

- En escritorio, el enlace **Catálogo** aparece en la navegación lateral solo para la master.
- En móvil, aparece como una entrada más dentro del menú de la barra inferior, también solo para la master.
- Las demás usuarias no ven el enlace y reciben `403` si intentan abrir la ruta directamente.

### 7.3 Página de listado

`Catalog/Index.vue`:

- Título **Catálogo master**.
- Botón **Añadir al catálogo**.
- Lista de fichas con portada, título, autor, ISBN, año y acciones **Editar** y **Eliminar**.
- Estado vacío con un texto que invita a añadir el primer libro.
- Confirmación antes de eliminar, usando el componente `Modal` existente.
- Mensajes de éxito y error mediante el sistema de flash actual.

### 7.4 Formularios

`Catalog/Create.vue` y `Catalog/Edit.vue` comparten un componente `CatalogBookForm.vue`:

- Campos: título, autor, ISBN, editorial, año, portada y, solo al crear, estantería.
- La portada admite subida de archivo o una URL visible en el formulario, con los límites actuales: JPG, PNG o WebP, máximo 5 MB. Si se envían ambos, gana el archivo subido.
- Al crear, la estantería es obligatoria y debe pertenecer a la master. Si la master no tiene estanterías, se muestra un aviso con enlace a **Estanterías**. Las estanterías se pasan con el mismo formato `display_name` que en Añadir libro.
- Al editar, no se muestra el selector de estantería.
- Si se indica ISBN, debe ser un ISBN válido de 10 o 13 caracteres; si no se conoce, se deja vacío.
- Los textos visibles se leen del catálogo de idiomas; no hay literales en los componentes.

## 8. Alta, edición y eliminación

### 8.1 Alta

`CatalogBookController::store`:

1. `StoreCatalogBookRequest::prepareForValidation` normaliza el ISBN y convierte una cadena vacía en `NULL` antes de validar.
2. Valida con `StoreCatalogBookRequest`: título obligatorio (máx. 255), autor (máx. 255), ISBN válido y único si se indica, editorial (máx. 150), año entre 0 y 2100, estantería existente y portada JPG/PNG/WebP de máximo 5 MB o URL válida.
3. Comprueba que la estantería pertenece a la master.
4. Guarda la portada del catálogo con `CoverService` (archivo subido o URL). Si la URL no se puede descargar, devuelve un error de validación y no crea la ficha.
5. Dentro de una transacción, crea la ficha `CatalogBook`.
6. Copia la portada del catálogo a un archivo propio del libro personal con `CoverService::copy`.
7. Calcula posición y banda con `BookPlacementService` y crea el `Book` en la estantería elegida, con `catalog_book_id` y `view_mode` de la master.
8. Si algo falla, revierte la transacción y elimina los archivos creados.
9. Redirige al listado con un mensaje de éxito.

`BookPlacementService` extrae la lógica que hoy vive en `BookController::nextPosition` y `BookController::nextBand`, para reutilizarla sin duplicar reglas de bandas. Las pruebas existentes de colocación y bandas deben seguir pasando.

### 8.2 Edición

`CatalogBookController::update`:

- `UpdateCatalogBookRequest::prepareForValidation` normaliza el ISBN y convierte una cadena vacía en `NULL` antes de validar.
- Valida con `UpdateCatalogBookRequest`; la regla `unique` del ISBN ignora la ficha actual.
- Actualiza solo la ficha del catálogo.
- Si se sube una portada nueva, guarda el archivo, actualiza `cover_path` y elimina la portada anterior.
- Si se envía una URL nueva y la descarga falla, se conserva la portada anterior y se devuelve un error de validación.
- No modifica ninguna copia personal.

### 8.3 Eliminación

`CatalogBookController::destroy`:

- Elimina la portada del catálogo.
- Elimina la ficha.
- La FK `books.catalog_book_id` deja las copias personales con `NULL`; los libros y sus portadas se conservan.
- Redirige al listado con un mensaje de éxito.

## 9. Portadas

- Las portadas del catálogo se guardan en el disco público, bajo `covers/`, igual que las portadas actuales.
- Se sirven por `GET /catalog-covers/{catalogBook}` con middleware `auth`; no se usa `storage:link`.
- Cualquier usuaria autenticada puede ver la portada de una ficha del catálogo; no se expone ningún libro privado.
- Al añadir un libro desde el catálogo, se copia el archivo para que la portada del libro personal sea independiente.
- Al eliminar una ficha, solo se borra su portada; las portadas copiadas de libros personales permanecen.

## 10. Búsqueda

### 10.1 Servicio

`BookLookupService` cambia así:

- `byIsbn(string $value)`:
  1. Normaliza y valida el ISBN como hoy.
  2. Consulta `catalog_books` por ISBN exacto.
  3. Obtiene los resultados de las APIs con la caché actual.
  4. Devuelve primero las fichas del catálogo y después los resultados de las APIs, sin deduplicar.
- `search(string $query)`:
  1. Exige al menos 3 caracteres como hoy.
  2. Consulta `catalog_books` por título o autor que contenga el texto; si el texto es un ISBN válido, también por ISBN.
  3. Obtiene los resultados de las APIs con la caché actual.
  4. Devuelve primero las fichas del catálogo y después los resultados de las APIs, sin deduplicar.

Reglas de la consulta local:

- Se escapan `%`, `_` y `\` antes de usar `LIKE`.
- Se ordena por título ascendente y se limita a 10 resultados.
- No se cachea: una ficha nueva aparece en la siguiente búsqueda.
- Si las APIs fallan, se devuelven igualmente las fichas locales.

### 10.2 Traducción

- Los resultados de las APIs se traducen como hoy.
- Las fichas del catálogo no se traducen: son datos introducidos por la master y se muestran tal cual.
- `withOfficialTitles` sigue aplicándose solo a resultados de Open Library con `work_key`.

### 10.3 Forma del resultado local

Cada ficha se transforma al mismo formato que consume el frontend:

```php
[
    'title' => $book->title,
    'author' => $book->author,
    'isbn' => $book->isbn,
    'publisher' => $book->publisher,
    'published_year' => $book->published_year,
    'cover_url' => $book->cover_path ? route('catalog-covers.show', $book, false) : null,
    'catalog_book_id' => $book->id,
]
```

La URL de portada es relativa para evitar depender de `APP_URL` y para que funcione igual en local y en InfinityFree.

### 10.4 Selección en el formulario

`Books/Create.vue`:

- El formulario incluye `catalog_book_id` con valor inicial `null`.
- Al seleccionar un resultado:
  - Si trae `catalog_book_id`, se guarda el id, se limpia `cover_url` y la vista previa usa la portada interna.
  - Si no lo trae, se guarda `cover_url` como hoy y `catalog_book_id` queda en `null`.
- La lógica de selección se extrae a `resources/js/bookLookup.js` para poder probarla con Vitest.

### 10.5 Guardado del libro personal

`StoreBookRequest` valida `catalog_book_id` como `nullable|integer|exists:catalog_books,id`.

`BookController::store` aplica este orden para la portada:

1. Si hay archivo subido, se usa ese archivo.
2. Si hay `catalog_book_id` y la ficha tiene portada, se copia la portada del catálogo.
3. Si hay `cover_url`, se descarga como hoy.
4. Si no hay nada, `cover_path` queda en `null`.

El `catalog_book_id` se guarda en el libro personal. `BookController::update` no permite cambiar ese vínculo.

## 11. Textos

Claves nuevas, presentes en `lang/es.json` y `lang/en.json`:

| Clave | Español |
|---|---|
| `ui.nav_catalog` | Catálogo |
| `ui.catalog_heading` | Catálogo master |
| `ui.catalog_add` | Añadir al catálogo |
| `ui.catalog_empty` | Aún no has añadido libros al catálogo. |
| `ui.catalog_edit_title` | Editar ficha del catálogo |
| `ui.catalog_delete_title` | Eliminar ficha |
| `ui.catalog_delete_confirm` | ¿Eliminar esta ficha del catálogo? Las copias en las estanterías se conservarán. |
| `ui.catalog_created` | Libro añadido al catálogo y a tu estantería. |
| `ui.catalog_updated` | Ficha del catálogo actualizada. |
| `ui.catalog_deleted` | Ficha del catálogo eliminada. |
| `ui.catalog_shelf_help` | El libro también se añadirá a esta estantería. |
| `ui.catalog_no_shelves` | Necesitas una estantería para añadir el libro. |
| `ui.catalog_cover_url` | URL de la portada |
| `ui.catalog_cover_error` | No se pudo descargar la portada. Revisa la URL. |
| `ui.catalog_isbn_invalid` | El ISBN no es válido. |

- Se reutilizan las claves existentes para **Editar**, **Eliminar**, **Guardar**, **Volver** y los campos del formulario.
- Las validaciones usan los mensajes existentes de `lang/es/validation.php` y sus equivalentes en inglés.
- `CatalogTest` seguirá comprobando que no falte ninguna clave en los idiomas revisados.

## 12. Pruebas

### 12.1 Pest

`tests/Feature/CatalogBookTest.php`:

- Una cuenta master ve el listado y puede abrir el formulario.
- Una cuenta normal recibe `403` en todas las rutas del panel.
- Una invitada se redirige al login.
- Crear una ficha crea también un libro en la estantería elegida, con `catalog_book_id` y portada copiada.
- No se puede usar una estantería de otra usuaria.
- El ISBN se normaliza y no se permiten duplicados.
- Editar una ficha no cambia las copias personales.
- Eliminar una ficha conserva las copias y deja `catalog_book_id` en `NULL`.
- La portada del catálogo requiere autenticación y devuelve `404` si no existe.
- Eliminar una ficha borra su portada.
- Una usuaria normal no puede activar `is_master` enviando ese campo al actualizar su perfil.
- Las pruebas existentes de colocación, bandas y libros siguen pasando.

Las pruebas usan `RefreshDatabase`, `User::factory()` y `CatalogBookFactory`. La cuenta master se crea con `User::factory()->master()->create()`; el estado `master()` del factory fuerza `is_master = true` sin añadirlo a los atributos rellenables.

`tests/Feature/BookLookupServiceTest.php`:

- Una ficha local aparece antes que los resultados de la API.
- Una ficha local aparece aunque las APIs fallen.
- La búsqueda por ISBN encuentra fichas locales.
- Las fichas locales no pasan por el traductor.

`tests/Feature/BookTest.php`:

- Guardar un libro con `catalog_book_id` copia la portada del catálogo.
- Un archivo subido tiene prioridad sobre la portada del catálogo.
- Un `catalog_book_id` inexistente falla la validación.

### 12.2 Vitest

`resources/js/bookLookup.test.js`:

- Seleccionar un resultado del catálogo guarda `catalog_book_id`, limpia `cover_url` y conserva la vista previa.
- Seleccionar un resultado de la API guarda `cover_url` y deja `catalog_book_id` en `null`.

### 12.3 Comprobaciones manuales

- Responsive a 360 px y escritorio.
- Enlace **Catálogo** visible solo para la master.
- Un libro añadido a mano aparece al buscarlo desde otra cuenta.
- La portada se ve en los resultados y en la estantería.
- Editar o eliminar la ficha no altera la copia personal.

## 13. Documentación visual

- Actualizar `docs/DISENO.md` con una fila fechada el 2026-11-12.
- Registrar: enlace **Catálogo** en navegación, página de listado, formulario de ficha y estado vacío.
- No se introducen colores, tipografías, sombras ni radios nuevos; se usan los tokens y componentes existentes.
- Revisar el diseño a 360 px y en escritorio antes de terminar.

## 14. Despliegue

- Las migraciones se ejecutan por el endpoint seguro `/deploy/migrate/{token}`.
- Los assets se compilan en local con `npm run build` antes de subir.
- No se usa `storage:link`; las portadas se sirven por rutas autenticadas.
- No se añaden colas, workers, Node.js en el servidor ni dependencia de `mail()`.
- El flag `is_master` se activa una vez desde phpMyAdmin, después de crear o registrar la cuenta.
- La usuaria realiza los commits; el agente no ejecuta `git add`, `git commit` ni `git push`.

## 15. Riesgos y consideraciones

- **Duplicados entre catálogo y API:** son intencionales. La ficha local aparece primero y la API después.
- **ISBN único:** dos ediciones distintas deben tener ISBN distintos; si no se conoce, se deja vacío.
- **Crecimiento del catálogo:** la búsqueda usa `LIKE` con comodín inicial. Es suficiente para un catálogo pequeño; si crece mucho, se valorará índice de texto completo en otra tarea.
- **Portadas duplicadas:** cada copia personal guarda su propio archivo. Es el precio de que editar o borrar el catálogo no afecte a las copias.
- **Flag manual:** si la cuenta master pierde el flag, se vuelve a activar desde phpMyAdmin. No hay autoservicio.
- **Varias cuentas master:** el sistema no limita el flag a una sola cuenta. Si se activa en otra, también podrá gestionar el catálogo; es una decisión operativa.
- **Coincidencia local:** usa la colación de MySQL y no añade búsqueda fonética ni normalización de acentos en esta versión.
- **Sin sincronización:** una corrección en el catálogo no corrige copias antiguas. Es una decisión explícita para no sorprender a otras usuarias.
- **Caché de APIs:** no se toca. Las fichas locales no se cachean y aparecen de inmediato.

## 16. Criterios de aceptación

- [ ] La cuenta master ve el enlace **Catálogo**; las demás no.
- [ ] Las rutas del panel responden `403` a cuentas no master.
- [ ] La master puede crear una ficha con título, autor, ISBN, editorial, año y portada.
- [ ] Al crear la ficha se crea también una copia en la estantería elegida.
- [ ] La ficha aparece en la búsqueda de cualquier usuaria autenticada, antes que los resultados de la API.
- [ ] La búsqueda funciona por título, autor e ISBN.
- [ ] La portada del catálogo se ve en los resultados y requiere autenticación.
- [ ] La master puede editar y eliminar fichas.
- [ ] Editar una ficha no cambia las copias personales.
- [ ] Eliminar una ficha conserva las copias personales y sus portadas.
- [ ] Los textos nuevos están en español e inglés.
- [ ] Las pruebas Pest y Vitest pasan.
- [ ] `npm run build` funciona.
- [ ] `docs/DISENO.md` tiene la fila del cambio visual.
- [ ] La usuaria revisa y hace el commit.
