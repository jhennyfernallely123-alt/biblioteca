# Despliegue en InfinityFree

La aplicación está pensada para ejecutarse en local durante el desarrollo y desplegarse después en InfinityFree (hosting compartido, sin Node.js ni SSH).

## 1. Preparar la base de datos

1. Crear una base de datos MySQL desde el panel de InfinityFree y anotar host, nombre, usuario y contraseña.
2. No usar `localhost` salvo que el panel lo indique: InfinityFree usa un host propio para la base de datos.

## 2. Configurar el entorno de producción

En el servidor, el archivo `.env` debe incluir al menos:

```dotenv
APP_NAME=Biblioteca
APP_ENV=production
APP_LOCALE=es
APP_DEBUG=false
APP_URL=https://tu-dominio.example

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=...
MAIL_FROM_NAME=Biblioteca

DEPLOY_TOKEN=un-token-largo-y-aleatorio
GOOGLE_BOOKS_API_KEY=
MYMEMORY_EMAIL=
```

- `MYMEMORY_EMAIL` es opcional: si se indica un correo válido, MyMemory amplía la cuota diaria de traducción de títulos y de la interfaz. Sin él, los títulos y los idiomas automáticos que no se puedan traducir se muestran en su idioma original o en español.
- El directorio `storage/app/i18n` debe existir y ser escribible por PHP: guarda las traducciones generadas de los idiomas automáticos. Se crea solo al arrancar la aplicación; si no hay permisos, la app sigue funcionando con los idiomas revisados.
- Los idiomas automáticos se generan una vez y quedan guardados en `storage/app/i18n`. Si se borra ese directorio, se regeneran al volver a elegir el idioma.
- Subir también `lang/` completo (incluye los 7 idiomas revisados además del español).
- `mail()` está deshabilitado: usar SMTP externo en el puerto 587 con TLS (Brevo, Gmail con contraseña de aplicación o Zoho).
- `QUEUE_CONNECTION=sync`: no hay workers de cola.
- No usar enlaces simbólicos ni `storage:link`. Las portadas se sirven por la ruta autenticada `/covers/{book}`.
- HTTPS es obligatorio: la cámara del escáner solo funciona en HTTPS o en `localhost`.

## 3. Compilar los assets en local

```powershell
npm run build
```

Subir el contenido de `public/build` junto con el resto de la aplicación. No se ejecuta Node.js en el servidor.

## 4. Subir la aplicación

1. Copiar `.deploy-env.example` a `.deploy-env` y rellenar los datos FTP.
2. Ejecutar el skill `deploy-infinityfree` (o `bash deploy.sh` sin flags).
3. **Saltar el paso de commit del skill:** los commits los hace la usuaria.
4. Si el skill lo indica, corregir `platform_check.php` mediante el flujo existente.

## 5. Ejecutar migraciones

Las migraciones no se ejecutan por SSH. Usar el endpoint seguro:

```text
https://tu-dominio.example/deploy/migrate/{DEPLOY_TOKEN}
```

Debe devolver `{"ok":true}`. Si cambian rutas o configuración, limpiar cachés:

```text
https://tu-dominio.example/deploy/cache/{DEPLOY_TOKEN}
```

## 6. Comprobaciones finales

- Registro e inicio de sesión con correo y contraseña.
- Recuperación de contraseña por correo (SMTP).
- Alta de libro por ISBN, por título/autor y manual.
- Escáner de código de barras en HTTPS.
- Portadas visibles solo para el usuario autenticado.
- Estanterías por defecto y gestión desde Configuraciones.
- Cambio de idioma desde Configuraciones, incluido un idioma automático (por ejemplo, alemán) y su progreso de preparación.
- La interfaz, las validaciones y los nombres de estanterías cambian de idioma sin alterar los datos guardados.
- Revisión en 360 px y escritorio.
