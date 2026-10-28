# Configurar Google y Facebook

Los botones sociales solo se activan cuando el proveedor tiene `client_id` y
`client_secret` configurados. Mientras falten, se muestran deshabilitados con la
etiqueta «Próximamente».

## Variables de entorno

Añade estas variables en `.env` (local) o en el panel de InfinityFree. No las
subas al repositorio.

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"
```

Si `GOOGLE_REDIRECT_URI` o `FACEBOOK_REDIRECT_URI` se dejan vacíos, la
aplicación usa `${APP_URL}/auth/{provider}/callback`.

## Google

1. Entra en <https://console.cloud.google.com/apis/credentials>.
2. Crea un proyecto y configura la pantalla de consentimiento externa.
3. Crea una credencial **ID de cliente de OAuth** de tipo *Aplicación web*.
4. Añade como URI de redirección autorizado:
   - Local: `http://localhost:8000/auth/google/callback`
   - Producción: `https://TU_DOMINIO/auth/google/callback`
5. Copia el client ID y el client secret a `.env`.

## Facebook

1. Entra en <https://developers.facebook.com/apps/> y crea una app de tipo
   *Consumidor*.
2. Añade el producto **Inicio de sesión con Facebook**.
3. En *Configuración básica*, indica la URL de política de privacidad y la URL
   del sitio.
4. En *Inicio de sesión con Facebook → Configuración*, añade como URI de
   redirección de OAuth válido:
   - Local: `http://localhost:8000/auth/facebook/callback`
   - Producción: `https://TU_DOMINIO/auth/facebook/callback`
5. Copia el App ID y el App Secret a `.env`.

Facebook exige **HTTPS** y que la app esté en modo *Live* para que personas
externas al equipo de desarrollo puedan iniciar sesión. En modo *Development*
solo funcionan las cuentas de prueba añadidas en el panel.

## Cómo funciona el acceso

- El servidor redirige al proveedor y valida el callback con Laravel Socialite.
- Si el proveedor entrega un correo verificado que ya pertenece a una cuenta, se
  vincula automáticamente a esa cuenta (no se crea un duplicado).
- Si no existe la cuenta, se crea con correo ya verificado, sin contraseña.
- Una cuenta puede tener Google y Facebook a la vez (`social_accounts`).
- Tras el primer acceso social, se pide un apodo en `/complete-profile` antes de
  entrar a la biblioteca.
- Si el proveedor no entrega correo o falla la validación, se vuelve a `/login`
  con un mensaje de error en español.

## Comprobar la configuración

```bash
php artisan config:clear
php artisan route:list --name=auth
```

El listado debe incluir `auth.redirect` y `auth.callback`. Para probar el flujo
completo hace falta una cuenta real en cada proveedor y un dominio HTTPS en
producción.
