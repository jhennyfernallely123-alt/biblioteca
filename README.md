# Biblioteca

Aplicación web responsive para organizar una biblioteca personal. Permite registrar libros, buscarlos por ISBN, código de barras, título o autor, colocarlos en estanterías y verlos como portadas o lomos sobre baldas de madera.

La interfaz parte de una estética de biblioteca antigua y nocturna, con verdes, tonos tierra, violeta, oro y turquesa. Cada cuenta es privada: las estanterías, los libros y las portadas se filtran siempre por el usuario autenticado.

## Funcionalidades

- Registro, inicio de sesión, recuperación de contraseña y perfil.
- Estanterías iniciales: Pendientes, Leyendo y Leídos.
- Crear, renombrar, reordenar y eliminar estanterías.
- Vista de portadas o de lomos, configurable por estantería.
- Alta manual de libros o mediante búsqueda por ISBN, código de barras, título o autor.
- Metadatos desde Google Books y Open Library, consultados desde el servidor.
- Portadas locales servidas mediante una ruta autenticada.
- Editar, mover y eliminar libros.
- Reordenar libros dentro de una estantería y moverlos entre estanterías mediante arrastre.
- Interfaz en español, catalán, gallego, inglés, portugués, francés, alemán e italiano.

## Lenguajes

- **PHP 8.3+** — lógica de backend y controladores
- **JavaScript (ES modules)** — interactividad de frontend con Vue 3
- **CSS** — estilos con Tailwind CSS 4 y tokens de diseño
- **SQL** — consultas a la base de datos MySQL

## Herramientas

- **Laravel 13** — framework de PHP
- **Inertia.js + Vue 3** — aplicación de una sola página sin recargas
- **Vite** — compilación y servidor de assets
- **Laravel Breeze** — autenticación con correo y contraseña
- **Laravel Sanctum** — autenticación de API
- **Laravel Socialite** — inicio de sesión con redes sociales
- **Laravel Pint** — formateo de código PHP
- **Pest + PHPUnit** — pruebas de backend
- **Vitest + @vue/test-utils + jsdom** — pruebas de frontend
- **Composer** — gestión de dependencias de PHP
- **npm** — gestión de dependencias de JavaScript
- **MySQL** — base de datos `biblioteca`
- **Git** — control de versiones

## Requisitos

- PHP 8.3 o superior con las extensiones habituales de Laravel
- Composer
- Node.js y npm solo para desarrollo y compilación de assets
- MySQL

## Instalación

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configura la conexión a MySQL en `.env`:

```dotenv
DB_CONNECTION=mysql
DB_DATABASE=biblioteca
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

Después crea la base de datos y ejecuta las migraciones:

```bash
php artisan migrate --seed
```

## Desarrollo

En dos terminales:

```bash
php artisan serve
npm run dev
```

La aplicación queda disponible en `http://localhost:8000`.

También se puede usar el script que arranca servidor, colas, logs y Vite a la vez:

```bash
composer run dev
```

## Pruebas y compilación

```bash
php artisan test
npm run test
npm run build
```

Para revisar el formato del código PHP:

```bash
./vendor/bin/pint
```

## Estructura

```text
app/
  Http/Controllers/    Controladores de la aplicación
  Http/Requests/       Validación de formularios
  Models/              Modelos Eloquent
  Policies/            Autorización por usuario
  Services/            Búsqueda de libros, portadas, traducciones e ISBN
database/
  migrations/          Esquema de la base de datos
  factories/           Datos de prueba
lang/                  Catálogo de textos de la interfaz
resources/
  css/                 Tokens de diseño y texturas
  js/                  Componentes y páginas Vue
routes/                Rutas web
tests/                 Pruebas de backend
```

## Documentación

- [`AGENTS.md`](AGENTS.md): reglas de trabajo del proyecto.

## Despliegue

El despliegue previsto es en InfinityFree (hosting compartido). Los assets se compilan en local con `npm run build`; en el servidor no se ejecuta Node.js. Las migraciones se aplican mediante el endpoint seguro de despliegue y las portadas se sirven por una ruta autenticada, sin enlaces simbólicos.
