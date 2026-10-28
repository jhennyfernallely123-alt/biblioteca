# Acceso en dos columnas con emblema y OAuth

**Fecha:** 2026-10-28
**Estado:** aprobado para planificación
**Alcance:** rediseño de las pantallas de acceso y acceso social con Google y Facebook.

## 1. Objetivo

Sustituir la tarjeta centrada actual por una composición de dos columnas: a la izquierda, un emblema de «Biblioteca» en arco con un libro abierto y destellos sobre una placa de cristal nocturna; a la derecha, el formulario de acceso. Añadir acceso con Google y Facebook, manteniendo la paleta arcana actual y sin copiar los colores azul/blanco de la referencia.

## 2. Decisiones aprobadas

- La columna visual va a la **izquierda** y el formulario a la **derecha**, como en la referencia.
- El diseño se aplica a **todas las pantallas de acceso**: iniciar sesión, crear cuenta, recuperar contraseña, restablecer contraseña, verificar correo y confirmar contraseña.
- Los botones de Google y Facebook aparecen en **iniciar sesión y crear cuenta**.
- Mientras no existan credenciales OAuth, los botones se muestran **deshabilitados con «Próximamente»**.
- Al entrar por primera vez con un proveedor social, se pide el **apodo después** de autorizar, antes de acceder a la biblioteca.
- Si el correo del proveedor ya pertenece a una cuenta, se **vincula automáticamente**.
- Se usa una tabla `social_accounts` para permitir Google y Facebook en la misma cuenta.
- Se mantiene la paleta actual: `night`, `night-raised`, `panel`, `line`, `moon`, `mist`, `gold`, `magic`, `arcane`.
- En móvil, el arte va arriba en una franja compacta y el formulario debajo.
- Quedan fuera de alcance: Apple, desvincular proveedores desde Ajustes, avatar social y acceso con teléfono.

## 3. Diseño visual

### 3.1 Estructura de escritorio

- Contenedor a pantalla completa, sin scroll horizontal.
- Dos columnas al 50 % desde `md` (768 px).
- **Columna izquierda:** fondo `arcane-sky` con `ArcaneStars` detrás del contenido. Centrada, una placa de cristal con el emblema.
- **Columna derecha:** fondo `night-raised`, formulario centrado verticalmente, ancho máximo 420 px y padding de 32–48 px.
- En pantallas de poca altura, la columna del formulario puede desplazarse verticalmente sin mover el emblema.

### 3.2 Emblema (`LibraryEmblem.vue`)

Componente reutilizable, decorativo (`aria-hidden="true"`):

- **Arco «Biblioteca»:** texto SVG sobre `textPath`, en Cormorant Garamond 600 y color `gold`. Debe leerse claramente como un arco, no como una línea recta.
- **Libro abierto:** SVG propio con dos páginas, lomo central, líneas de páginas y un resplandor `magic` suave entre ellas. Debe evocar un libro que se abre, no un icono plano.
- **Destellos:** entre 10 y 12 puntos y estrellas pequeñas en `gold` y `magic`, distribuidos alrededor del arco y del libro.
- **Placa de cristal:** fondo `panel` translúcido, borde `magic` suave, filo superior dorado, `backdrop-blur`, sombra de modal y halo arcano. Contiene el arco y el libro.
- **Movimiento:** parpadeo lento y desfasado de los destellos y respiración muy sutil del halo. Con `prefers-reduced-motion: reduce` quedan estáticos.
- **Móvil:** versión compacta, con el arco y el libro más pequeños, sin perder los destellos.

### 3.3 Formulario

- Título de página en Cormorant `gold` (por ejemplo, «Iniciar sesión»).
- Campos con los componentes actuales (`TextInput`, `InputLabel`, `InputError`).
- Contraseña mediante un nuevo `PasswordInput.vue` reutilizable, con botón de ojo para mostrar/ocultar, `aria-label` en español y área táctil mínima de 44 px. Se usa en todas las pantallas que piden contraseña (iniciar sesión, registro, restablecer y confirmar).
- Fila «Recordarme» + «¿Olvidaste tu contraseña?» en escritorio; en móvil se apila sin romper el ancho.
- Botón principal «Entrar» / «Crear cuenta», ancho completo, `arcane`.
- Separador «o continúa con».
- Botones sociales en dos columnas (`sm` en adelante) y apilados en móvil.
- Enlace final «¿Aún no tienes cuenta? Crear cuenta» o «¿Ya tienes cuenta? Inicia sesión».

### 3.4 Botones sociales (`SocialLoginButtons.vue`)

- Dos botones: **Google** y **Facebook**, con icono de marca propio en SVG inline y texto.
- **Sin credenciales:** `disabled`, `aria-disabled="true"` y una etiqueta pequeña «Próximamente». No navegan.
- **Con credenciales:** enlaces reales a `/auth/google/redirect` y `/auth/facebook/redirect`.
- Estilo: fondo `panel`, borde `line`, texto `moon`, hover `panel-raised`; deshabilitados con opacidad reducida y sin hover.
- Los iconos de marca no usan los colores corporativos; se adaptan a `moon`/`gold` para no romper la paleta.

### 3.5 Móvil

- Ancho de referencia: 360 px.
- Franja superior de 220–260 px con el emblema compacto sobre el cielo.
- Formulario debajo, a ancho completo con padding de 16 px.
- Sin desbordamiento horizontal.
- El emblema no debe empujar el formulario fuera de la primera pantalla en alturas habituales.

### 3.6 Pantallas afectadas

Todas usan `GuestLayout` rediseñado:

- `Auth/Login.vue`
- `Auth/Register.vue`
- `Auth/ForgotPassword.vue`
- `Auth/ResetPassword.vue`
- `Auth/VerifyEmail.vue`
- `Auth/ConfirmPassword.vue`
- Nueva `Auth/CompleteNickname.vue`

Solo Login y Register incluyen `SocialLoginButtons`.

## 4. Acceso social

### 4.1 Dependencia

Se añade `laravel/socialite` con Composer. No requiere Node.js ni workers en el servidor.

### 4.2 Configuración

En `config/services.php`:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
],
'facebook' => [
    'client_id' => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect' => env('FACEBOOK_REDIRECT_URI', env('APP_URL').'/auth/facebook/callback'),
],
```

Variables en `.env.example`:

- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI` (opcional)
- `FACEBOOK_CLIENT_ID`
- `FACEBOOK_CLIENT_SECRET`
- `FACEBOOK_REDIRECT_URI` (opcional)

### 4.3 Rutas

- `GET /auth/{provider}/redirect` → `SocialAuthController@redirect`, nombre `auth.redirect`.
- `GET /auth/{provider}/callback` → `SocialAuthController@callback`, nombre `auth.callback`.
- `provider` restringido a `google` y `facebook`.
- Middleware `guest`.
- Si el proveedor no está configurado, responde 404.

### 4.4 Tabla `social_accounts`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint unsigned | PK |
| `user_id` | foreignId | `users.id`, `cascadeOnDelete` |
| `provider` | string(20) | `google` o `facebook` |
| `provider_id` | string(191) | ID del proveedor |
| `created_at` / `updated_at` | timestamps | |

- Índice único `(provider, provider_id)`.
- Índice único `(user_id, provider)` para no duplicar el mismo proveedor en una cuenta.
- Modelo `SocialAccount` con `belongsTo(User)`; `User` con `hasMany(SocialAccount::class)`.
- No se guardan tokens de acceso del proveedor: solo el identificador necesario para reconocer el vínculo.

### 4.5 Flujo del callback

1. Obtener el usuario de Socialite.
2. Si no hay correo, volver al login con error claro.
3. Buscar `social_accounts` por `provider` + `provider_id`.
   - Si existe, iniciar sesión con su `user`.
4. Si no existe, buscar `users` por correo.
   - Si existe, crear el vínculo y no sobrescribir nombre ni apodo.
5. Si no existe la cuenta:
   - Crear `User` con nombre del proveedor, correo verificado (`email_verified_at = now()`), contraseña aleatoria hasheada, `nickname = null`, idioma y vista por defecto actuales.
   - Crear las tres estanterías iniciales dentro de una transacción.
   - Crear el vínculo social.
6. Regenerar la sesión e iniciar sesión.
7. Si `nickname` es `null`, redirigir a `/complete-profile`; si no, al dashboard.
8. Si el usuario cancela o el proveedor falla, volver al login con `flash.error`.

La creación de estanterías iniciales se extrae a una acción o servicio reutilizable para no duplicar la lógica del registro.

### 4.6 Disponibilidad de proveedores

`HandleInertiaRequests` comparte `socialProviders`:

```php
'socialProviders' => [
    'google' => filled(config('services.google.client_id')) && filled(config('services.google.client_secret')),
    'facebook' => filled(config('services.facebook.client_id')) && filled(config('services.facebook.client_secret')),
],
```

`SocialLoginButtons` usa ese prop para decidir entre enlace y botón deshabilitado.

## 5. Apodo pendiente

- Ruta `GET /complete-profile` → `CompleteNicknameController@edit`.
- Ruta `POST /complete-profile` → `CompleteNicknameController@update`.
- Middleware `auth`.
- Si la usuaria ya tiene apodo, redirige al dashboard.
- Vista `Auth/CompleteNickname.vue` con la misma estructura visual.
- Campo **Apodo** obligatorio, máximo 50 caracteres, con el texto «Así te saludaremos al entrar.».
- Botón «Continuar»; enlace secundario «Salir».
- Al guardar: actualizar apodo, regenerar sesión si corresponde y redirigir al dashboard.
- Middleware `EnsureNicknameIsSet` en las rutas autenticadas de la aplicación (biblioteca, libros, estanterías, ajustes, perfil y verificación de correo).
- El middleware no se aplica a `/complete-profile` ni a `/logout`.
- Si una cuenta sin apodo intenta entrar a la biblioteca, se redirige a `/complete-profile`.

## 6. Errores y mensajes

- Añadir `flash.error` a los props compartidos de Inertia.
- `GuestLayout` muestra el error en un aviso `danger` encima del formulario.
- Mensajes en español:
  - «No pudimos completar el acceso con el proveedor. Inténtalo de nuevo.»
  - «El proveedor no nos dio un correo. Usa otro método de acceso.»
- Si el proveedor no está configurado, la ruta responde 404; los botones deshabilitados no llegan a ella.
- No se muestran detalles técnicos ni trazas.

## 7. Seguridad y privacidad

- Socialite gestiona el parámetro `state`.
- Las credenciales OAuth solo viven en `.env` y `config/services.php`; nunca se envían al navegador.
- La contraseña aleatoria de las cuentas sociales no se muestra ni se registra.
- El vínculo automático por correo es seguro porque Google y Facebook entregan correos verificados.
- La tabla `social_accounts` se crea y consulta por `user_id`; no se aceptan IDs de usuaria desde el cliente.
- No se permite cambiar el correo desde el callback social.
- El middleware de apodo evita estados intermedios accesibles.

## 8. Pruebas

### Backend

- `SocialAuthTest`:
  - cuenta nueva con Google crea usuaria, vínculo y estanterías, y redirige a completar apodo.
  - correo existente vincula sin duplicar la cuenta.
  - vínculo existente inicia sesión sin crear otra cuenta.
  - proveedor sin correo redirige al login con error.
  - proveedor no configurado responde 404.
  - cuenta con apodo va directa al dashboard.
  - cancelación del proveedor vuelve al login con error.
- `CompleteNicknameTest`:
  - invitada es redirigida al login.
  - formulario visible.
  - apodo obligatorio y máximo 50.
  - guardado correcto y redirección al dashboard.
  - cuenta con apodo no vuelve a ver el formulario.
- `NicknameGateTest`:
  - cuenta sin apodo no accede a biblioteca ni ajustes.
  - sí accede a completar apodo y cerrar sesión.
- `AuthenticationTest` sigue pasando.

### Frontend

- `SocialLoginButtons.test.js`: deshabilitados sin credenciales, enlaces con credenciales, etiqueta «Próximamente».
- `PasswordInput.test.js`: alterna entre `password` y `text`, mantiene el valor y expone un `aria-label` claro.
- `Login.test.js`: campos, ojo de contraseña, enlaces, separador y botones sociales.
- `Register.test.js`: campos de nombre/apodo/correo/contraseña y botones sociales.
- `GuestLayout.test.js`: renderiza el emblema y el formulario, y no muestra el error si no existe.
- Pruebas existentes de Inertia y Vitest siguen pasando.

### Verificación manual

- Escritorio 1280×900: dos columnas, emblema legible, formulario centrado.
- Móvil 360×800: arte arriba, formulario debajo, sin scroll horizontal.
- Teclado: orden de foco lógico, ojo accesible, botones deshabilitados no reciben foco.
- `prefers-reduced-motion`: destellos quietos.

## 9. Documentación y despliegue

- Actualizar `docs/DISENO.md`:
  - estructura de las pantallas de acceso;
  - `LibraryEmblem`;
  - `SocialLoginButtons`;
  - comportamiento responsive;
  - registro de cambios con fecha 2026-10-28.
- Crear el plan de implementación en `docs/superpowers/plans/`.
- Documentar en el spec o en el plan cómo crear las apps de Google y Facebook y qué URL de redirección usar.
- Google Cloud exige registrar el origen y la URL de redirección autorizada; Meta exige una app en modo Live, política de privacidad y HTTPS para producción.
- Compilar assets en local antes de desplegar.
- En InfinityFree, añadir las variables OAuth al `.env` del servidor y usar URLs HTTPS.
- No se requiere Node.js, colas ni enlaces simbólicos en el servidor.

## 10. Criterios de aceptación

- [ ] Las seis pantallas de acceso usan la composición de dos columnas.
- [ ] El emblema muestra «Biblioteca» en arco, un libro abierto y destellos.
- [ ] La paleta arcana se mantiene; no se introducen azul/blanco de la referencia.
- [ ] En móvil el arte va arriba y el formulario debajo, sin desbordes.
- [ ] Iniciar sesión y crear cuenta muestran Google y Facebook deshabilitados mientras falten credenciales.
- [ ] Al configurar las credenciales, los botones inician el flujo OAuth real.
- [ ] Una cuenta nueva por Google/Facebook elige apodo antes de entrar.
- [ ] Un correo existente se vincula automáticamente.
- [ ] Las cuentas existentes no se ven afectadas.
- [ ] Los errores OAuth se muestran en español y sin detalles técnicos.
- [ ] Pasan `php artisan test`, `npm run test` y `npm run build`.
- [ ] `docs/DISENO.md` refleja el cambio.
