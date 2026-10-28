# Biblioteca — Apodo para la bienvenida

**Fecha:** 2026-10-28
**Estado:** Diseño aprobado por la usuaria; pendiente de implementación.
**Documento de identidad visual:** `docs/DISENO.md`.
**Reglas del proyecto:** `AGENTS.md`.

---

## 1. Objetivo

Permitir que cada usuaria elija un apodo al crear su cuenta y que ese apodo sea el que aparece en la pantalla de bienvenida, en lugar de su nombre real.

## 2. Alcance

### Incluido

- Campo obligatorio **Apodo** en el registro.
- Campo editable **Apodo** en Ajustes → Perfil.
- Uso del apodo en la intro `MagicWelcome`.
- Migración de las cuentas existentes: su apodo inicial será su nombre actual.
- Validación en el servidor y textos en los ocho idiomas revisados.
- Pruebas de registro, ajustes y bienvenida.
- Actualización de `docs/DISENO.md`.

### Fuera de alcance

- Apodos públicos o compartidos entre cuentas.
- Unicidad de apodos.
- Cambiar el nombre real, el correo o la barra lateral para mostrar el apodo.
- Traducir las pantallas de acceso y registro, que siguen en español.
- Usar el apodo en correos o notificaciones.

## 3. Modelo de datos

Se añade `nickname` a `users`:

- Tipo: cadena de hasta 50 caracteres.
- Nulo por compatibilidad con la migración; las cuentas existentes se rellenan con su `name` y la aplicación usa `name` como respaldo si faltara.
- Sin índice único.
- Añadido a `$fillable`.
- La fábrica de usuarios genera un apodo para las pruebas.

La bienvenida usa `nickname`; si faltara por cualquier motivo, usa `name` como respaldo.

## 4. Flujo

### Registro

1. La pantalla pide nombre, apodo, correo y contraseña.
2. El servidor valida `nickname` como obligatorio, texto y máximo 50.
3. Se crea la cuenta con el apodo y sus tres estanterías iniciales.
4. Al entrar, la intro saluda con el apodo.

### Ajustes

1. El formulario de perfil carga el apodo actual.
2. Al guardar, el servidor valida y actualiza `nickname`.
3. La siguiente apertura de la app muestra el nuevo apodo en la intro.

### Cuentas existentes

La migración copia `name` en `nickname` para que la bienvenida siga funcionando sin pasos manuales.

## 5. Interfaz

- Registro: campo **Apodo** entre el nombre y el correo, con `maxlength="50"` y `autocomplete="nickname"`.
- Ajustes: campo **Apodo** después del nombre, con el mismo límite y mensaje de error debajo.
- Intro: el título sigue siendo `ui.welcome_title` y recibe el apodo como valor de `:name`.
- El nombre real permanece visible en el perfil; la barra lateral no lo muestra y el botón de cerrar sesión se coloca en la parte superior.

## 6. Traducciones

Se añade `ui.nickname` a los ocho catálogos revisados:

- es: Apodo
- en: Nickname
- ca: Sobrenom
- gl: Alcume
- pt: Alcunha
- fr: Surnom
- de: Spitzname
- it: Soprannome

## 7. Pruebas

- Registro sin apodo: error de validación.
- Registro con apodo: se guarda en la cuenta.
- Ajustes: se actualiza el apodo.
- Ajustes: se rechaza un apodo vacío o de más de 50 caracteres.
- Bienvenida: muestra el apodo recibido.
- Integración: `AppShell` pasa el apodo del usuario a la intro y usa el nombre si falta.

## 8. Migración y despliegue

- Migración nueva y compatible con MySQL e InfinityFree.
- Sin cambios en colas, almacenamiento ni permisos.
- Ejecutar la migración con el flujo habitual antes de desplegar.
