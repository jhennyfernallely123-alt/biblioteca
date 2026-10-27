# AGENTS.md — Reglas del proyecto Biblioteca

Este archivo es de cumplimiento obligatorio para cualquier agente que trabaje en este repositorio.

## 1. Qué es el proyecto

Aplicación web responsive de biblioteca personal: registrar libros, buscarlos por ISBN/código de barras o título/autor, organizarlos en estanterías y verlos con portadas sobre baldas de madera, con estética de biblioteca antigua (verdes y tonos tierra).

- **Stack:** Laravel 13 (PHP 8.3+), Inertia.js, Vue 3, Tailwind CSS 4, MySQL (base `biblioteca`).
- **Autenticación:** Laravel Breeze (Vue/Inertia), correo y contraseña.
- **Búsqueda de libros:** Google Books y Open Library como respaldo, siempre desde el servidor.
- **Despliegue previsto:** local primero; después InfinityFree (hosting compartido).

## 2. Reglas absolutas

1. **La usuaria hace los commits.** No ejecutar `git add`, `git commit`, `git push`, `git tag` ni ninguna operación que modifique el historial, salvo petición explícita de la usuaria.
2. **El diseño visual es vinculante.** La fuente de verdad es `docs/DISENO.md`. No introducir colores, tipografías, sombras, radios ni componentes nuevos sin registrarlos en ese documento y en su tabla de cambios.
3. **Registrar cada cambio visual.** Si se modifica un color, tamaño, componente, textura, estructura de pantalla o comportamiento visual, actualizar `docs/DISENO.md` con la fecha y el cambio antes de dar la tarea por terminada.
4. **No cambiar la estructura del proyecto** (carpetas, capas, convenciones) sin documentarlo y justificarlo.
5. **No romper la compatibilidad con InfinityFree.** Sin Node.js en el servidor, sin SSH, sin workers de cola, sin enlaces simbólicos, sin dependencia de `mail()`.
6. **Idioma:** la interfaz es multilingüe; español por defecto y textos de acceso siempre en español. Los textos visibles se leen del catálogo (`lang/es.json`, `lang/{locale}.json` o `storage/app/i18n/{locale}.json`) mediante `t('ui.*')`; no escribir cadenas visibles nuevas directamente en componentes. Validaciones y mensajes del servidor también se traducen. Código y nombres técnicos en inglés.
7. **Privacidad:** todas las consultas de estanterías, libros y portadas se filtran por el usuario autenticado.

## 3. Documentos del proyecto

| Documento | Contenido |
|---|---|
| `docs/superpowers/specs/2026-10-22-biblioteca-design.md` | Diseño funcional y técnico del MVP |
| `docs/superpowers/specs/2026-10-26-multilenguaje-design.md` | Diseño del multilingüismo: catálogo, idiomas revisados y automáticos |
| `docs/DISENO.md` | Identidad visual, componentes, responsive y registro de cambios |
| `AGENTS.md` | Reglas para agentes |
| `docs/superpowers/plans/` | Planes de implementación |

Ante cualquier duda de estilo visual, consultar `docs/DISENO.md` antes de inventar.

## 4. Convenciones de código

- Laravel: controladores delgados, lógica de consulta externa en `app/Services`.
- Autorización con Policies; nunca confiar en IDs enviados por el cliente.
- Validación con Form Requests o `$request->validate()`; nunca solo en el frontend.
- Vue: `<script setup>`, componentes en PascalCase, props tipadas cuando se use TypeScript.
- Tailwind con tokens definidos en `resources/css/app.css` (`@theme`); sin hex sueltos en componentes.
- Nombres de archivos y rutas en inglés; textos de interfaz en el catálogo de idiomas, nunca literales en componentes.
- Sin comentarios en el código salvo que aporten información no obvia.

## 5. Comandos de trabajo (una vez creado el proyecto)

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan test
npm run test
npm run build
```

- Backend: Pest/PHPUnit (`php artisan test`).
- Frontend: Vitest (`npm run test`).
- Formato PHP: `./vendor/bin/pint`.
- Build de producción: `npm run build`.

## 6. Despliegue en InfinityFree

Usar el skill `deploy-infinityfree` cuando la usuaria lo solicite.

- Compilar assets en local antes de subir.
- Ejecutar `bash deploy.sh` (sin flags salvo autorización explícita).
- **Saltar el paso de commit del skill:** la usuaria es quien commitea.
- Corregir `platform_check.php` mediante el flujo existente.
- Migraciones por el endpoint seguro `/deploy/migrate/{token}`.
- SMTP externo en el puerto 587 con TLS (Brevo, Gmail con contraseña de aplicación o Zoho). `mail()` está deshabilitado.
- HTTPS obligatorio: la cámara del escáner solo funciona en HTTPS o `localhost`.
- Las portadas se sirven por ruta autenticada; no usar `storage:link`.

## 7. Verificación antes de terminar una tarea

1. Ejecutar las pruebas afectadas y confirmar que pasan.
2. Revisar que no se accede a datos de otro usuario.
3. Revisar responsive en 360 px y escritorio.
4. Si hubo cambios visuales, actualizar `docs/DISENO.md` y su registro de cambios.
5. Informar a la usuaria de los archivos modificados y dejar que ella haga el commit.
