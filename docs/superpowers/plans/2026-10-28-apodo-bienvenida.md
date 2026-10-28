# Apodo para la bienvenida — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. **Important:** the user makes all commits. Do not run `git add`, `git commit`, `git push` or `git tag`.

**Goal:** Añadir un apodo obligatorio a la cuenta, editable en Ajustes y usado en la intro de bienvenida.

**Architecture:** Nueva columna `nickname` en `users`, validación compartida en registro y ajustes, y propagación desde `auth.user` hasta `MagicWelcome`. Las cuentas existentes se rellenan con su nombre.

**Tech Stack:** Laravel, Inertia, Vue 3, Tailwind 4, Pest, Vitest.

---

## Task 1: Migración, modelo y fábrica

**Files:**
- Create: `database/migrations/2026_10_28_000000_add_nickname_to_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`

- [ ] Crear la migración con `nickname` nullable de 50 caracteres y rellenar las filas existentes desde `name`.
- [ ] Añadir `nickname` a `#[Fillable]`.
- [ ] Añadir un apodo a `UserFactory`.
- [ ] Ejecutar `php artisan migrate` y comprobar el esquema.

## Task 2: Registro

**Files:**
- Modify: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Modify: `resources/js/Pages/Auth/Register.vue`
- Modify: `tests/Feature/Auth/RegistrationTest.php`
- Modify: `tests/Feature/RegistrationTest.php`

- [ ] Escribir primero las pruebas de registro con apodo y sin apodo; ejecutarlas y confirmar que fallan.
- [ ] Validar `nickname` como `required|string|max:50`.
- [ ] Guardar el apodo al crear la cuenta.
- [ ] Añadir el campo Apodo al formulario, entre nombre y correo.
- [ ] Ejecutar las pruebas de registro.

## Task 3: Ajustes

**Files:**
- Modify: `app/Http/Requests/UpdateProfileRequest.php`
- Modify: `resources/js/Pages/Settings/Index.vue`
- Modify: `tests/Feature/SettingsTest.php`
- Modify: `lang/{es,en,ca,gl,pt,fr,de,it}.json`

- [ ] Escribir la prueba de actualización y validación del apodo; confirmar que falla.
- [ ] Añadir `nickname` a las reglas y al formulario.
- [ ] Añadir `ui.nickname` a los ocho idiomas.
- [ ] Ejecutar las pruebas de ajustes y el test del catálogo.

## Task 4: Bienvenida

**Files:**
- Modify: `resources/js/Components/MagicWelcome.vue`
- Modify: `resources/js/Components/AppShell.vue`
- Modify: `resources/js/Components/__tests__/MagicWelcome.test.js`
- Modify: `resources/js/Components/__tests__/AppShell.test.js`

- [ ] Cambiar la prop de la intro a `nickname` y usar el apodo en el saludo.
- [ ] Pasar `user.nickname` desde `AppShell` con respaldo a `user.name`.
- [ ] Actualizar las pruebas y ejecutarlas.

## Task 5: Documentación y verificación

**Files:**
- Modify: `docs/DISENO.md`

- [ ] Documentar el campo Apodo y la bienvenida personalizada.
- [ ] Añadir la entrada al registro de cambios.
- [ ] Ejecutar `php artisan test`, `npm run test` y `npm run build`.
- [ ] Verificar en navegador el registro, la bienvenida, el cambio desde Ajustes y 360 px.
