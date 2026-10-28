# Acceso en dos columnas con emblema y OAuth — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rediseñar todas las pantallas de acceso con una composición de dos columnas, un emblema arcano y acceso con Google/Facebook, incluyendo apodo obligatorio tras el primer acceso social.

**Architecture:** `GuestLayout` pasa a ser el layout compartido de dos columnas. Se añaden componentes de presentación (`LibraryEmblem`, `PasswordInput`, `SocialLoginButtons`). En backend se instala Socialite, se crea `social_accounts` y un controlador de callback que vincula por correo o crea la cuenta; un middleware bloquea la biblioteca hasta completar el apodo. Los botones sociales se activan solo cuando existen credenciales en `config/services.php`.

**Tech Stack:** Laravel 13, Laravel Socialite, Inertia.js, Vue 3, Tailwind CSS 4, PHPUnit, Vitest.

**Regla del proyecto:** la usuaria hace todos los commits. No ejecutar `git add`, `git commit`, `git push` ni `git tag`. Donde este plan indique una pausa, dejar los cambios en el árbol de trabajo para revisión.

---

## Mapa de archivos

**Backend**
- Modificar `composer.json` (Socialite).
- Modificar `config/services.php`.
- Modificar `.env.example`.
- Crear `database/migrations/2026_10_28_000001_create_social_accounts_table.php`.
- Crear `app/Models/SocialAccount.php`.
- Modificar `app/Models/User.php`.
- Crear `app/Services/DefaultShelvesService.php`.
- Modificar `app/Http/Controllers/Auth/RegisteredUserController.php`.
- Crear `app/Http/Controllers/Auth/SocialAuthController.php`.
- Crear `app/Http/Controllers/Auth/CompleteNicknameController.php`.
- Crear `app/Http/Middleware/EnsureNicknameIsSet.php`.
- Modificar `bootstrap/app.php`.
- Modificar `routes/web.php`.
- Modificar `routes/auth.php`.
- Modificar `app/Http/Middleware/HandleInertiaRequests.php`.
- Modificar `lang/es/auth.php`.
- Crear `tests/Feature/Auth/SocialProvidersTest.php`.
- Crear `tests/Feature/Auth/SocialAuthTest.php`.
- Crear `tests/Feature/Auth/CompleteNicknameTest.php`.
- Crear `tests/Feature/Auth/NicknameGateTest.php`.

**Frontend**
- Crear `resources/js/Components/LibraryEmblem.vue`.
- Crear `resources/js/Components/PasswordInput.vue`.
- Crear `resources/js/Components/SocialLoginButtons.vue`.
- Crear `resources/js/Components/__tests__/PasswordInput.test.js`.
- Crear `resources/js/Components/__tests__/SocialLoginButtons.test.js`.
- Crear `resources/js/Layouts/__tests__/GuestLayout.test.js`.
- Modificar `resources/js/Layouts/GuestLayout.vue`.
- Modificar `resources/css/textures.css`.
- Modificar `resources/js/Pages/Auth/Login.vue`.
- Modificar `resources/js/Pages/Auth/Register.vue`.
- Modificar `resources/js/Pages/Auth/ResetPassword.vue`.
- Modificar `resources/js/Pages/Auth/ConfirmPassword.vue`.
- Crear `resources/js/Pages/Auth/CompleteNickname.vue`.
- Crear `resources/js/Pages/Auth/__tests__/Login.test.js`.
- Crear `resources/js/Pages/Auth/__tests__/Register.test.js`.

**Documentación**
- Modificar `docs/DISENO.md`.
- Crear `docs/oauth-setup.md`.
- Este plan: `docs/superpowers/plans/2026-10-28-login-oauth-dos-columnas.md`.

---

## Task 1: Base de Socialite, configuración y props compartidos

**Files:**
- Modify: `composer.json`
- Modify: `config/services.php`
- Modify: `.env.example`
- Create: `database/migrations/2026_10_28_000001_create_social_accounts_table.php`
- Create: `app/Models/SocialAccount.php`
- Modify: `app/Models/User.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Test: `tests/Feature/Auth/SocialProvidersTest.php`

- [ ] **Step 1: Escribir el test que falla para los props compartidos**

Crea `tests/Feature/Auth/SocialProvidersTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SocialProvidersTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_providers_are_disabled_without_credentials(): void
    {
        config([
            'services.google' => ['client_id' => null, 'client_secret' => null],
            'services.facebook' => ['client_id' => null, 'client_secret' => null],
        ]);

        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->where('socialProviders.google', false)
            ->where('socialProviders.facebook', false));
    }

    public function test_social_providers_are_enabled_with_credentials(): void
    {
        config([
            'services.google' => ['client_id' => 'google-id', 'client_secret' => 'google-secret'],
            'services.facebook' => ['client_id' => 'facebook-id', 'client_secret' => 'facebook-secret'],
        ]);

        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->where('socialProviders.google', true)
            ->where('socialProviders.facebook', true));
    }
}
```

- [ ] **Step 2: Ejecutar el test y comprobar que falla**

Run:

```bash
php artisan test tests/Feature/Auth/SocialProvidersTest.php
```

Expected: FAIL porque `socialProviders` todavía no se comparte con Inertia.

- [ ] **Step 3: Instalar Socialite**

Run:

```bash
composer require laravel/socialite
```

Expected: `composer.json` y `composer.lock` actualizados con `laravel/socialite`.

- [ ] **Step 4: Añadir la configuración de proveedores**

En `config/services.php`, añade antes de `'postmark'`:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI') ?: rtrim((string) env('APP_URL', 'http://localhost'), '/').'/auth/google/callback',
],

'facebook' => [
    'client_id' => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect' => env('FACEBOOK_REDIRECT_URI') ?: rtrim((string) env('APP_URL', 'http://localhost'), '/').'/auth/facebook/callback',
],
```

- [ ] **Step 5: Documentar las variables en `.env.example`**

Añade al final de `.env.example`:

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=
```

- [ ] **Step 6: Crear la migración de `social_accounts`**

Crea `database/migrations/2026_10_28_000001_create_social_accounts_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20);
            $table->string('provider_id', 191);
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);
            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
```

- [ ] **Step 7: Crear el modelo `SocialAccount`**

Crea `app/Models/SocialAccount.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'provider', 'provider_id'])]
class SocialAccount extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 8: Añadir la relación a `User`**

En `app/Models/User.php`, añade la importación:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;
```

La clase ya importa `HasMany`; conserva una sola importación. Añade el método después de `books()`:

```php
public function socialAccounts(): HasMany
{
    return $this->hasMany(SocialAccount::class);
}
```

- [ ] **Step 9: Compartir `socialProviders` y `flash.error` con Inertia**

En `app/Http/Middleware/HandleInertiaRequests.php`, dentro de `share()`:

```php
'socialProviders' => [
    'google' => filled(config('services.google.client_id')) && filled(config('services.google.client_secret')),
    'facebook' => filled(config('services.facebook.client_id')) && filled(config('services.facebook.client_secret')),
],
'flash' => [
    'success' => fn () => $request->session()->get('success'),
    'error' => fn () => $request->session()->get('error'),
],
```

Sustituye el bloque `flash` existente para no duplicar la clave.

- [ ] **Step 10: Ejecutar la migración y el test**

Run:

```bash
php artisan migrate --force
php artisan test tests/Feature/Auth/SocialProvidersTest.php
```

Expected: PASS. La migración crea `social_accounts` y los dos tests pasan.

- [ ] **Step 11: Pausa de revisión**

No hagas commit. Deja los cambios para que la usuaria los revise.

---

## Task 2: Servicio de estanterías iniciales

**Files:**
- Create: `app/Services/DefaultShelvesService.php`
- Modify: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Test: `tests/Feature/RegistrationTest.php` (existente)

- [ ] **Step 1: Ejecutar el test existente como red de seguridad**

Run:

```bash
php artisan test tests/Feature/RegistrationTest.php
```

Expected: PASS con el test «crea las estanterías iniciales al registrarse».

- [ ] **Step 2: Crear el servicio**

Crea `app/Services/DefaultShelvesService.php`:

```php
<?php

namespace App\Services;

use App\Models\User;

class DefaultShelvesService
{
    public function createFor(User $user): void
    {
        foreach (['Pendientes', 'Leyendo', 'Leídos'] as $position => $name) {
            $user->shelves()->create([
                'name' => $name,
                'position' => $position,
            ]);
        }
    }
}
```

- [ ] **Step 3: Usar el servicio en el registro**

En `app/Http/Controllers/Auth/RegisteredUserController.php`, importa:

```php
use App\Services\DefaultShelvesService;
```

Añade el constructor:

```php
public function __construct(private DefaultShelvesService $defaultShelves)
{
}
```

Sustituye el bucle de estanterías dentro de la transacción por:

```php
$this->defaultShelves->createFor($user);
```

El bloque de creación debe quedar así:

```php
$user = DB::transaction(function () use ($request) {
    $user = User::create([
        'name' => $request->name,
        'nickname' => $request->nickname,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    $this->defaultShelves->createFor($user);

    return $user;
});
```

- [ ] **Step 4: Ejecutar el test del registro**

Run:

```bash
php artisan test tests/Feature/RegistrationTest.php
```

Expected: PASS; las tres estanterías siguen creándose.

- [ ] **Step 5: Pausa de revisión**

No hagas commit.

---

## Task 3: Apodo pendiente y bloqueo de la aplicación

**Files:**
- Create: `app/Http/Controllers/Auth/CompleteNicknameController.php`
- Create: `app/Http/Middleware/EnsureNicknameIsSet.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Modify: `routes/auth.php`
- Test: `tests/Feature/Auth/CompleteNicknameTest.php`
- Test: `tests/Feature/Auth/NicknameGateTest.php`

- [ ] **Step 1: Escribir los tests que fallan**

Crea `tests/Feature/Auth/CompleteNicknameTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompleteNicknameTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/complete-profile')->assertRedirect(route('login'));
    }

    public function test_user_without_nickname_sees_the_page(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->get('/complete-profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/CompleteNickname', false));
    }

    public function test_user_with_nickname_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create(['nickname' => 'Ana']);

        $this->actingAs($user)
            ->get('/complete-profile')
            ->assertRedirect(route('dashboard'));
    }

    public function test_nickname_is_required(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->post('/complete-profile', ['nickname' => ''])
            ->assertSessionHasErrors('nickname');
    }

    public function test_nickname_cannot_exceed_fifty_characters(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->post('/complete-profile', ['nickname' => str_repeat('a', 51)])
            ->assertSessionHasErrors('nickname');
    }

    public function test_user_can_complete_nickname(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->post('/complete-profile', ['nickname' => 'Luna'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('Luna', $user->fresh()->nickname);
    }
}
```

Crea `tests/Feature/Auth/NicknameGateTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NicknameGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_nickname_cannot_open_the_library(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('profile.complete'));
    }

    public function test_user_without_nickname_cannot_open_settings(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->get('/settings')
            ->assertRedirect(route('profile.complete'));
    }

    public function test_user_without_nickname_can_open_complete_profile(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->get('/complete-profile')
            ->assertOk();
    }

    public function test_user_without_nickname_can_logout(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
```

- [ ] **Step 2: Ejecutar los tests y comprobar que fallan**

Run:

```bash
php artisan test tests/Feature/Auth/CompleteNicknameTest.php tests/Feature/Auth/NicknameGateTest.php
```

Expected: FAIL porque las rutas y el middleware no existen.

- [ ] **Step 3: Crear el middleware**

Crea `app/Http/Middleware/EnsureNicknameIsSet.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNicknameIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->nickname === null) {
            return redirect()->route('profile.complete');
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Registrar el alias del middleware**

En `bootstrap/app.php`, añade:

```php
use App\Http\Middleware\EnsureNicknameIsSet;
```

Y dentro de `withMiddleware()`:

```php
$middleware->alias([
    'nickname' => EnsureNicknameIsSet::class,
]);
```

- [ ] **Step 5: Crear el controlador de apodo**

Crea `app/Http/Controllers/Auth/CompleteNicknameController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompleteNicknameController extends Controller
{
    public function edit(Request $request): Response|RedirectResponse
    {
        if ($request->user()->nickname !== null) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/CompleteNickname');
    }

    public function update(Request $request): RedirectResponse
    {
        if ($request->user()->nickname !== null) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:50'],
        ]);

        $request->user()->update([
            'nickname' => $validated['nickname'],
        ]);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
```

- [ ] **Step 6: Añadir las rutas de apodo**

En `routes/web.php`, añade antes del grupo principal:

```php
Route::middleware('auth')->group(function () {
    Route::get('/complete-profile', [CompleteNicknameController::class, 'edit'])
        ->name('profile.complete');
    Route::post('/complete-profile', [CompleteNicknameController::class, 'update'])
        ->name('profile.complete.store');
});
```

Añade la importación:

```php
use App\Http\Controllers\Auth\CompleteNicknameController;
```

- [ ] **Step 7: Proteger las rutas de la aplicación**

En `routes/web.php`, cambia el middleware de la portada y del dashboard a `['auth', 'nickname']` y el grupo principal a `['auth', 'nickname']`. El archivo debe quedar así en su parte de rutas de la aplicación:

```php
Route::middleware('auth')->group(function () {
    Route::get('/complete-profile', [CompleteNicknameController::class, 'edit'])
        ->name('profile.complete');
    Route::post('/complete-profile', [CompleteNicknameController::class, 'update'])
        ->name('profile.complete.store');
});

Route::get('/', [LibraryController::class, 'index'])
    ->middleware(['auth', 'nickname'])
    ->name('library.index');

Route::get('/dashboard', fn () => redirect()->route('library.index'))
    ->middleware(['auth', 'nickname'])
    ->name('dashboard');

Route::middleware(['auth', 'nickname'])->group(function () {
    Route::get('/books/lookup', BookLookupController::class)->middleware('throttle:60,1')->name('books.lookup');
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::patch('/books/reorder', [BookController::class, 'reorder'])->name('books.reorder');
    Route::patch('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::patch('/books/{book}/move', [BookController::class, 'move'])->name('books.move');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    Route::get('/covers/{book}', CoverController::class)->name('covers.show');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');

    Route::post('/settings/locale/prepare', [LocaleController::class, 'prepare'])
        ->middleware('throttle:30,1')
        ->name('settings.locale.prepare');
    Route::post('/settings/locale/translate', [LocaleController::class, 'translate'])
        ->middleware('throttle:30,1')
        ->name('settings.locale.translate');
    Route::patch('/settings/locale', [LocaleController::class, 'update'])
        ->name('settings.locale');

    Route::post('/shelves', [ShelfController::class, 'store'])->name('shelves.store');
    Route::post('/shelves/reorder', [ShelfController::class, 'reorder'])->name('shelves.reorder');
    Route::patch('/shelves/{shelf}', [ShelfController::class, 'update'])->name('shelves.update');
    Route::delete('/shelves/{shelf}', [ShelfController::class, 'destroy'])->name('shelves.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
```

No incluyas dentro de este grupo `/complete-profile` ni `/logout`. Las rutas `/deploy/*` y `require __DIR__.'/auth.php'` se mantienen al final sin cambios.

- [ ] **Step 8: Proteger las rutas de verificación y contraseña**

En `routes/auth.php`, separa el cierre de sesión del resto. El grupo autenticado debe quedar así:

```php
Route::middleware(['auth', 'nickname'])->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
```

- [ ] **Step 9: Ejecutar los tests de apodo**

Run:

```bash
php artisan test tests/Feature/Auth/CompleteNicknameTest.php tests/Feature/Auth/NicknameGateTest.php
```

Expected: PASS con 10 tests.

- [ ] **Step 10: Pausa de revisión**

No hagas commit.

---

## Task 4: Callback social, rutas y mensajes de error

**Files:**
- Create: `app/Http/Controllers/Auth/SocialAuthController.php`
- Modify: `routes/auth.php`
- Modify: `lang/es/auth.php`
- Test: `tests/Feature/Auth/SocialAuthTest.php`

- [ ] **Step 1: Escribir los tests que fallan**

Crea `tests/Feature/Auth/SocialAuthTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google' => ['client_id' => 'google-id', 'client_secret' => 'google-secret'],
            'services.facebook' => ['client_id' => 'facebook-id', 'client_secret' => 'facebook-secret'],
        ]);
    }

    public function test_new_social_user_is_created_and_sent_to_complete_nickname(): void
    {
        $this->mockProvider('google', [
            'id' => 'google-123',
            'name' => 'Ana Social',
            'email' => 'ana@example.com',
        ]);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('profile.complete'));
        $this->assertAuthenticated();

        $user = User::where('email', 'ana@example.com')->firstOrFail();

        $this->assertNull($user->nickname);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);
        $this->assertDatabaseCount('shelves', 3);
    }

    public function test_existing_email_is_linked_without_duplicating_the_account(): void
    {
        $user = User::factory()->create([
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'nickname' => 'Ana',
        ]);

        $this->mockProvider('google', [
            'id' => 'google-123',
            'name' => 'Otro Nombre',
            'email' => 'ana@example.com',
        ]);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Ana', $user->fresh()->nickname);
        $this->assertSame('Ana', $user->fresh()->name);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);
    }

    public function test_existing_social_account_logs_in_without_creating_another_user(): void
    {
        $user = User::factory()->create(['nickname' => 'Ana']);
        $user->socialAccounts()->create([
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);

        $this->mockProvider('google', [
            'id' => 'google-123',
            'name' => 'Ana Social',
            'email' => $user->email,
        ]);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('social_accounts', 1);
    }

    public function test_provider_without_email_returns_to_login_with_an_error(): void
    {
        $this->mockProvider('google', [
            'id' => 'google-123',
            'name' => 'Ana Social',
            'email' => null,
        ]);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'El proveedor no nos dio un correo. Usa otro método de acceso.');
        $this->assertGuest();
    }

    public function test_unconfigured_provider_returns_not_found(): void
    {
        config([
            'services.google' => ['client_id' => null, 'client_secret' => null],
        ]);

        $this->get('/auth/google/redirect')->assertNotFound();
    }

    public function test_provider_cancellation_returns_to_login_with_an_error(): void
    {
        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->once()->andThrow(new RuntimeException('denied'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'No pudimos completar el acceso con el proveedor. Inténtalo de nuevo.');
        $this->assertGuest();
    }

    public function test_redirect_route_returns_the_provider_redirect(): void
    {
        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

        $this->get('/auth/google/redirect')
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    private function mockProvider(string $provider, array $data): void
    {
        $socialiteUser = (new SocialiteUser)->map($data);

        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->once()->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->once()->with($provider)->andReturn($driver);
    }
}
```

- [ ] **Step 2: Ejecutar los tests y comprobar que fallan**

Run:

```bash
php artisan test tests/Feature/Auth/SocialAuthTest.php
```

Expected: FAIL porque las rutas y el controlador aún no existen.

- [ ] **Step 3: Añadir los mensajes de error**

En `lang/es/auth.php`, añade:

```php
'social_failed' => 'No pudimos completar el acceso con el proveedor. Inténtalo de nuevo.',
'social_email_missing' => 'El proveedor no nos dio un correo. Usa otro método de acceso.',
```

- [ ] **Step 4: Crear el controlador social**

Crea `app/Http/Controllers/Auth/SocialAuthController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\DefaultShelvesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'facebook'];

    public function __construct(private DefaultShelvesService $defaultShelves)
    {
    }

    public function redirect(string $provider): SymfonyRedirectResponse
    {
        $this->ensureConfigured($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureConfigured($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('login')->with('error', __('auth.social_failed'));
        }

        $email = $socialUser->getEmail();

        if (! $email) {
            return redirect()->route('login')->with('error', __('auth.social_email_missing'));
        }

        $user = DB::transaction(function () use ($provider, $socialUser, $email) {
            $account = SocialAccount::query()
                ->where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if ($account) {
                return $account->user;
            }

            $user = User::where('email', $email)->first();

            if (! $user) {
                $user = User::create([
                    'name' => $socialUser->getName() ?: Str::before($email, '@'),
                    'nickname' => null,
                    'email' => $email,
                    'password' => Hash::make(Str::random(40)),
                ]);

                $user->forceFill(['email_verified_at' => now()])->save();
                $this->defaultShelves->createFor($user);
            }

            $user->socialAccounts()->firstOrCreate(
                ['provider' => $provider],
                ['provider_id' => $socialUser->getId()],
            );

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->nickname === null) {
            return redirect()->route('profile.complete');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function ensureConfigured(string $provider): void
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        abort_unless(
            filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret")),
            404,
        );
    }
}
```

- [ ] **Step 5: Añadir las rutas sociales**

En `routes/auth.php`, dentro del grupo `guest` y después de la ruta `login` POST, añade:

```php
Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', ['google', 'facebook'])
    ->name('auth.redirect');

Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'facebook'])
    ->name('auth.callback');
```

Añade la importación:

```php
use App\Http\Controllers\Auth\SocialAuthController;
```

- [ ] **Step 6: Ejecutar los tests sociales**

Run:

```bash
php artisan test tests/Feature/Auth/SocialAuthTest.php
```

Expected: PASS con 7 tests.

- [ ] **Step 7: Pausa de revisión**

No hagas commit.

---

## Task 5: Emblema, contraseña visible y botones sociales

**Files:**
- Create: `resources/js/Components/LibraryEmblem.vue`
- Create: `resources/js/Components/PasswordInput.vue`
- Create: `resources/js/Components/SocialLoginButtons.vue`
- Create: `resources/js/Components/__tests__/PasswordInput.test.js`
- Create: `resources/js/Components/__tests__/SocialLoginButtons.test.js`
- Create: `resources/js/Layouts/__tests__/GuestLayout.test.js`
- Modify: `resources/js/Layouts/GuestLayout.vue`
- Modify: `resources/css/textures.css`

- [ ] **Step 1: Escribir los tests que fallan**

Crea `resources/js/Components/__tests__/PasswordInput.test.js`:

```js
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import PasswordInput from '../PasswordInput.vue';

describe('PasswordInput', () => {
    it('alterna entre contraseña visible y oculta sin perder el valor', async () => {
        const wrapper = mount(PasswordInput, {
            props: { modelValue: 'secreto', id: 'password' },
        });

        expect(wrapper.get('input').attributes('type')).toBe('password');
        expect(wrapper.get('input').element.value).toBe('secreto');

        await wrapper.get('button').trigger('click');

        expect(wrapper.get('input').attributes('type')).toBe('text');
        expect(wrapper.get('input').element.value).toBe('secreto');
        expect(wrapper.get('button').attributes('aria-label')).toBe('Ocultar contraseña');
    });
});
```

Crea `resources/js/Components/__tests__/SocialLoginButtons.test.js`:

```js
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import SocialLoginButtons from '../SocialLoginButtons.vue';

describe('SocialLoginButtons', () => {
    it('muestra los proveedores deshabilitados sin credenciales', () => {
        const wrapper = mount(SocialLoginButtons, {
            props: { providers: { google: false, facebook: false } },
        });

        const buttons = wrapper.findAll('button');

        expect(buttons).toHaveLength(2);
        buttons.forEach((button) => {
            expect(button.attributes('disabled')).toBeDefined();
            expect(button.attributes('aria-disabled')).toBe('true');
        });
        expect(wrapper.text()).toContain('Próximamente');
    });

    it('crea enlaces reales cuando hay credenciales', () => {
        const wrapper = mount(SocialLoginButtons, {
            props: { providers: { google: true, facebook: true } },
        });

        expect(wrapper.find('a[href="/auth/google/redirect"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/auth/facebook/redirect"]').exists()).toBe(true);
        expect(wrapper.find('button[disabled]').exists()).toBe(false);
    });
});
```

Crea `resources/js/Layouts/__tests__/GuestLayout.test.js`:

```js
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({ page: { props: { flash: {} } } }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mocks.page,
}));

import GuestLayout from '../GuestLayout.vue';

describe('GuestLayout', () => {
    it('muestra el emblema y el contenido', () => {
        const wrapper = mount(GuestLayout, {
            slots: { default: '<p>Formulario</p>' },
        });

        expect(wrapper.find('.auth-emblem').exists()).toBe(true);
        expect(wrapper.text()).toContain('Formulario');
    });

    it('muestra el error compartido encima del formulario', () => {
        mocks.page.props.flash = { error: 'No pudimos entrar.' };

        const wrapper = mount(GuestLayout, {
            slots: { default: '<p>Formulario</p>' },
        });

        expect(wrapper.text()).toContain('No pudimos entrar.');
        expect(wrapper.get('[role="alert"]').exists()).toBe(true);
    });
});
```

- [ ] **Step 2: Ejecutar los tests y comprobar que fallan**

Run:

```bash
npm run test -- resources/js/Components/__tests__/PasswordInput.test.js resources/js/Components/__tests__/SocialLoginButtons.test.js resources/js/Layouts/__tests__/GuestLayout.test.js
```

Expected: FAIL porque los componentes no existen y `GuestLayout` no tiene el emblema.

- [ ] **Step 3: Crear `LibraryEmblem.vue`**

Crea `resources/js/Components/LibraryEmblem.vue`:

```vue
<script setup>
defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <figure class="auth-emblem" :class="{ 'auth-emblem--compact': compact }" aria-hidden="true">
        <div class="auth-emblem__halo" />

        <div class="auth-emblem__card">
            <svg class="auth-emblem__art" viewBox="0 0 360 320" role="presentation" focusable="false">
                <defs>
                    <path id="biblioteca-arc" d="M 48 160 A 132 132 0 0 1 312 160" />
                    <linearGradient id="book-gold" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="var(--color-gold)" />
                        <stop offset="1" stop-color="var(--color-magic)" />
                    </linearGradient>
                </defs>

                <text class="auth-emblem__word" text-anchor="middle">
                    <textPath href="#biblioteca-arc" startOffset="50%">Biblioteca</textPath>
                </text>

                <g class="auth-emblem__book" fill="none" stroke="url(#book-gold)" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M 180 214 C 146 190 112 190 76 202 L 76 248 C 112 236 146 236 180 260" />
                    <path d="M 180 214 C 214 190 248 190 284 202 L 284 248 C 248 236 214 236 180 260" />
                    <path d="M 180 214 L 180 260" />
                    <path d="M 96 214 C 122 206 144 206 168 220" opacity=".55" />
                    <path d="M 264 214 C 238 206 216 206 192 220" opacity=".55" />
                    <path d="M 98 228 C 124 220 146 220 168 234" opacity=".35" />
                    <path d="M 262 228 C 236 220 214 220 192 234" opacity=".35" />
                </g>

                <g class="auth-emblem__sparks">
                    <circle class="auth-emblem__spark auth-emblem__spark--1" cx="54" cy="92" r="2.2" />
                    <circle class="auth-emblem__spark auth-emblem__spark--2" cx="306" cy="104" r="1.8" />
                    <circle class="auth-emblem__spark auth-emblem__spark--3" cx="44" cy="238" r="1.7" />
                    <circle class="auth-emblem__spark auth-emblem__spark--4" cx="316" cy="232" r="2.1" />
                    <circle class="auth-emblem__spark auth-emblem__spark--5" cx="112" cy="76" r="1.5" />
                    <circle class="auth-emblem__spark auth-emblem__spark--6" cx="248" cy="70" r="1.7" />
                    <circle class="auth-emblem__spark auth-emblem__spark--7" cx="72" cy="176" r="1.5" />
                    <circle class="auth-emblem__spark auth-emblem__spark--8" cx="288" cy="176" r="1.6" />
                    <circle class="auth-emblem__spark auth-emblem__spark--9" cx="136" cy="282" r="1.8" />
                    <circle class="auth-emblem__spark auth-emblem__spark--10" cx="224" cy="282" r="1.6" />
                    <path class="auth-emblem__spark auth-emblem__spark--11" d="M 180 44 l 2.4 7.1 7.1 2.4 -7.1 2.4 -2.4 7.1 -2.4 -7.1 -7.1 -2.4 7.1 -2.4Z" />
                    <path class="auth-emblem__spark auth-emblem__spark--12" d="M 180 280 l 1.8 5.3 5.3 1.8 -5.3 1.8 -1.8 5.3 -1.8 -5.3 -5.3 -1.8 5.3 -1.8Z" />
                </g>
            </svg>
        </div>
    </figure>
</template>
```

- [ ] **Step 4: Crear `PasswordInput.vue`**

Crea `resources/js/Components/PasswordInput.vue`:

```vue
<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    id: {
        type: String,
        default: undefined,
    },
    autocomplete: {
        type: String,
        default: 'current-password',
    },
    required: {
        type: Boolean,
        default: false,
    },
    autofocus: {
        type: Boolean,
        default: false,
    },
});

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);
const visible = ref(false);

onMounted(() => {
    if (props.autofocus) {
        input.value?.focus();
    }
});
</script>

<template>
    <div class="relative">
        <input
            :id="id"
            ref="input"
            :type="visible ? 'text' : 'password'"
            v-model="model"
            :autocomplete="autocomplete"
            :required="required"
            :autofocus="autofocus"
            class="w-full rounded-control border-line bg-panel px-3 py-2 pe-12 text-moon shadow-soft placeholder:text-mist focus:border-magic focus:ring-magic"
        />

        <button
            type="button"
            class="absolute inset-y-0 end-0 flex w-11 items-center justify-center rounded-control text-mist hover:text-moon focus:outline-none focus:ring-2 focus:ring-magic"
            :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            @click="visible = !visible"
        >
            <svg v-if="!visible" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                <circle cx="12" cy="12" r="2.7" />
            </svg>
            <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <path d="m3 3 18 18" />
                <path d="M10.6 6.2A10.9 10.9 0 0 1 12 6c6 0 9.5 6 9.5 6a17.7 17.7 0 0 1-3.1 3.8" />
                <path d="M6.6 6.6C4 8.4 2.5 12 2.5 12s3.5 6 9.5 6a10.7 10.7 0 0 0 4.5-1" />
            </svg>
        </button>
    </div>
</template>
```

- [ ] **Step 5: Crear `SocialLoginButtons.vue`**

Crea `resources/js/Components/SocialLoginButtons.vue`:

```vue
<script setup>
import { computed } from 'vue';

const props = defineProps({
    providers: {
        type: Object,
        default: () => ({ google: false, facebook: false }),
    },
});

const items = computed(() => [
    {
        key: 'google',
        label: 'Google',
        enabled: Boolean(props.providers?.google),
    },
    {
        key: 'facebook',
        label: 'Facebook',
        enabled: Boolean(props.providers?.facebook),
    },
]);
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2">
        <template v-for="provider in items" :key="provider.key">
            <a
                v-if="provider.enabled"
                :href="`/auth/${provider.key}/redirect`"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-control border border-line bg-panel px-3 py-2 text-sm font-medium text-moon hover:bg-panel-raised focus:outline-none focus:ring-2 focus:ring-magic"
            >
                <svg v-if="provider.key === 'google'" class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        fill="currentColor"
                        d="M21.35 11.1h-9.17v2.96h5.3c-.24 1.6-1.8 4.7-5.3 4.7-3.19 0-5.79-2.64-5.79-5.9s2.6-5.9 5.79-5.9c1.82 0 3.04.77 3.74 1.43l2.55-2.46C16.9 4.44 14.97 3.6 12.18 3.6 6.99 3.6 2.8 7.79 2.8 12.98s4.19 9.38 9.38 9.38c5.41 0 9-3.8 9-9.16 0-.61-.07-1.08-.16-1.54Z"
                    />
                </svg>
                <svg v-else class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        fill="currentColor"
                        d="M13.5 22v-8h2.7l.4-3.1h-3.1V8.9c0-.9.25-1.5 1.55-1.5H16.7V4.6c-.3-.04-1.3-.13-2.47-.13-2.44 0-4.11 1.49-4.11 4.22v2.21H7.4V14h2.72v8h3.38Z"
                    />
                </svg>
                <span>{{ provider.label }}</span>
            </a>

            <button
                v-else
                type="button"
                disabled
                aria-disabled="true"
                class="inline-flex min-h-11 cursor-not-allowed items-center justify-center gap-2 rounded-control border border-line bg-panel/60 px-3 py-2 text-sm font-medium text-mist opacity-70"
            >
                <svg v-if="provider.key === 'google'" class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        fill="currentColor"
                        d="M21.35 11.1h-9.17v2.96h5.3c-.24 1.6-1.8 4.7-5.3 4.7-3.19 0-5.79-2.64-5.79-5.9s2.6-5.9 5.79-5.9c1.82 0 3.04.77 3.74 1.43l2.55-2.46C16.9 4.44 14.97 3.6 12.18 3.6 6.99 3.6 2.8 7.79 2.8 12.98s4.19 9.38 9.38 9.38c5.41 0 9-3.8 9-9.16 0-.61-.07-1.08-.16-1.54Z"
                    />
                </svg>
                <svg v-else class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        fill="currentColor"
                        d="M13.5 22v-8h2.7l.4-3.1h-3.1V8.9c0-.9.25-1.5 1.55-1.5H16.7V4.6c-.3-.04-1.3-.13-2.47-.13-2.44 0-4.11 1.49-4.11 4.22v2.21H7.4V14h2.72v8h3.38Z"
                    />
                </svg>
                <span>{{ provider.label }}</span>
                <span class="ms-auto rounded-full border border-line px-2 py-0.5 text-[10px] uppercase tracking-wide">
                    Próximamente
                </span>
            </button>
        </template>
    </div>
</template>
```

- [ ] **Step 6: Rediseñar `GuestLayout.vue`**

Sustituye el contenido de `resources/js/Layouts/GuestLayout.vue` por:

```vue
<script setup>
import { usePage } from '@inertiajs/vue3';
import ArcaneStars from '@/Components/ArcaneStars.vue';
import LibraryEmblem from '@/Components/LibraryEmblem.vue';

const page = usePage();
</script>

<template>
    <div class="arcane-sky min-h-screen md:grid md:grid-cols-2">
        <ArcaneStars />

        <div class="relative flex h-56 items-center justify-center overflow-hidden md:hidden">
            <LibraryEmblem compact />
        </div>

        <aside class="relative hidden overflow-hidden md:flex md:items-center md:justify-center md:p-8">
            <LibraryEmblem />
        </aside>

        <main class="relative flex min-h-[calc(100vh-14rem)] flex-col justify-center bg-night-raised px-4 py-10 md:min-h-screen md:px-10">
            <div class="mx-auto w-full max-w-[420px]">
                <div
                    v-if="page.props.flash?.error"
                    role="alert"
                    class="mb-5 rounded-control border border-danger/50 bg-danger/10 px-4 py-3 text-sm text-danger"
                >
                    {{ page.props.flash.error }}
                </div>

                <slot />
            </div>
        </main>
    </div>
</template>
```

- [ ] **Step 7: Añadir los estilos del emblema**

Añade a `resources/css/textures.css`, antes de `.shelf-case`:

```css
.auth-emblem {
  position: relative;
  width: min(82%, 420px);
}

.auth-emblem--compact {
  width: min(64%, 230px);
}

.auth-emblem__halo {
  position: absolute;
  inset: -12% -16%;
  border-radius: 999px;
  background: radial-gradient(
    circle,
    color-mix(in srgb, var(--color-arcane) 34%, transparent) 0%,
    color-mix(in srgb, var(--color-magic) 16%, transparent) 45%,
    transparent 72%
  );
  filter: blur(20px);
  animation: auth-emblem-breathe 9s ease-in-out infinite alternate;
}

.auth-emblem__card {
  position: relative;
  overflow: hidden;
  border: 1px solid color-mix(in srgb, var(--color-magic) 36%, transparent);
  border-radius: 24px;
  background: linear-gradient(
    160deg,
    color-mix(in srgb, var(--color-panel) 88%, transparent) 0%,
    color-mix(in srgb, var(--color-night) 72%, transparent) 100%
  );
  padding: 22px 18px 14px;
  backdrop-filter: blur(18px);
  box-shadow:
    inset 0 1px 0 color-mix(in srgb, var(--color-gold) 35%, transparent),
    0 30px 80px -32px color-mix(in srgb, var(--color-arcane) 70%, transparent),
    0 18px 40px -24px rgba(0, 0, 0, 0.8);
}

.auth-emblem__card::before {
  content: "";
  position: absolute;
  inset: 0 0 auto;
  height: 2px;
  background: linear-gradient(90deg, transparent, var(--color-gold), transparent);
  opacity: 0.8;
}

.auth-emblem__art {
  display: block;
  width: 100%;
  height: auto;
}

.auth-emblem__word {
  fill: var(--color-gold);
  font-family: "Cormorant Garamond", serif;
  font-size: 38px;
  font-weight: 600;
  letter-spacing: 0.08em;
}

.auth-emblem__book {
  filter: drop-shadow(0 0 10px color-mix(in srgb, var(--color-magic) 45%, transparent));
}

.auth-emblem__spark {
  fill: var(--color-gold);
  transform-box: fill-box;
  transform-origin: center;
  animation: auth-emblem-twinkle 4.8s ease-in-out infinite;
}

.auth-emblem__spark--2,
.auth-emblem__spark--5,
.auth-emblem__spark--9 {
  fill: var(--color-magic);
}

.auth-emblem__spark--1 { animation-delay: -0.4s; }
.auth-emblem__spark--2 { animation-delay: -2.1s; }
.auth-emblem__spark--3 { animation-delay: -3.2s; }
.auth-emblem__spark--4 { animation-delay: -1.2s; }
.auth-emblem__spark--5 { animation-delay: -2.8s; }
.auth-emblem__spark--6 { animation-delay: -0.9s; }
.auth-emblem__spark--7 { animation-delay: -3.8s; }
.auth-emblem__spark--8 { animation-delay: -1.8s; }
.auth-emblem__spark--9 { animation-delay: -2.5s; }
.auth-emblem__spark--10 { animation-delay: -0.2s; }
.auth-emblem__spark--11 { animation-delay: -1.6s; }
.auth-emblem__spark--12 { animation-delay: -3.5s; }

@keyframes auth-emblem-breathe {
  from { opacity: 0.55; transform: scale(0.97); }
  to { opacity: 0.9; transform: scale(1.03); }
}

@keyframes auth-emblem-twinkle {
  0%, 100% { opacity: 0.35; transform: scale(0.85); }
  50% { opacity: 1; transform: scale(1.15); }
}

@media (prefers-reduced-motion: reduce) {
  .auth-emblem__halo,
  .auth-emblem__spark {
    animation: none;
  }
}
```

- [ ] **Step 8: Ejecutar los tests de componentes**

Run:

```bash
npm run test -- resources/js/Components/__tests__/PasswordInput.test.js resources/js/Components/__tests__/SocialLoginButtons.test.js resources/js/Layouts/__tests__/GuestLayout.test.js
```

Expected: PASS con 5 tests.

- [ ] **Step 9: Pausa de revisión**

No hagas commit.

---

## Task 6: Pantallas de acceso

**Files:**
- Modify: `resources/js/Pages/Auth/Login.vue`
- Modify: `resources/js/Pages/Auth/Register.vue`
- Modify: `resources/js/Pages/Auth/ResetPassword.vue`
- Modify: `resources/js/Pages/Auth/ConfirmPassword.vue`
- Create: `resources/js/Pages/Auth/__tests__/Login.test.js`
- Create: `resources/js/Pages/Auth/__tests__/Register.test.js`

- [ ] **Step 1: Escribir los tests que fallan**

Crea `resources/js/Pages/Auth/__tests__/Login.test.js`:

```js
import { mount } from '@vue/test-utils';
import { h } from 'vue';
import { describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    post: vi.fn(),
    reset: vi.fn(),
    page: { props: { socialProviders: { google: false, facebook: false }, flash: {} } },
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: {
        props: ['href'],
        inheritAttrs: false,
        setup(props, { attrs, slots }) {
            return () => h('a', { href: props.href, ...attrs }, slots.default?.());
        },
    },
    usePage: () => mocks.page,
    useForm: () => ({
        email: '',
        password: '',
        remember: false,
        processing: false,
        errors: {},
        post: mocks.post,
        reset: mocks.reset,
    }),
}));

import Login from '../Login.vue';

describe('Login', () => {
    it('muestra el formulario, el ojo de contraseña y los proveedores deshabilitados', () => {
        const wrapper = mount(Login, { props: { canResetPassword: true } });

        expect(wrapper.text()).toContain('Iniciar sesión');
        expect(wrapper.find('#email').exists()).toBe(true);
        expect(wrapper.get('#password').attributes('type')).toBe('password');
        expect(wrapper.text()).toContain('o continúa con');
        expect(wrapper.text()).toContain('Próximamente');
    });

    it('envía el formulario a /login', async () => {
        const wrapper = mount(Login, { props: { canResetPassword: true } });

        await wrapper.get('form').trigger('submit');

        expect(mocks.post).toHaveBeenCalledWith('/login', expect.any(Object));
    });
});
```

Crea `resources/js/Pages/Auth/__tests__/Register.test.js`:

```js
import { mount } from '@vue/test-utils';
import { h } from 'vue';
import { describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    post: vi.fn(),
    reset: vi.fn(),
    page: { props: { socialProviders: { google: false, facebook: false }, flash: {} } },
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: {
        props: ['href'],
        inheritAttrs: false,
        setup(props, { attrs, slots }) {
            return () => h('a', { href: props.href, ...attrs }, slots.default?.());
        },
    },
    usePage: () => mocks.page,
    useForm: () => ({
        name: '',
        nickname: '',
        email: '',
        password: '',
        password_confirmation: '',
        processing: false,
        errors: {},
        post: mocks.post,
        reset: mocks.reset,
    }),
}));

import Register from '../Register.vue';

describe('Register', () => {
    it('muestra nombre, apodo, correo y los proveedores deshabilitados', () => {
        const wrapper = mount(Register);

        expect(wrapper.text()).toContain('Crear cuenta');
        expect(wrapper.find('#name').exists()).toBe(true);
        expect(wrapper.find('#nickname').exists()).toBe(true);
        expect(wrapper.text()).toContain('Próximamente');
    });
});
```

- [ ] **Step 2: Ejecutar los tests y comprobar que fallan**

Run:

```bash
npm run test -- resources/js/Pages/Auth/__tests__/Login.test.js resources/js/Pages/Auth/__tests__/Register.test.js
```

Expected: FAIL porque faltan los botones sociales y el ojo de contraseña.

- [ ] **Step 3: Actualizar `Login.vue`**

Sustituye el contenido de `resources/js/Pages/Auth/Login.vue` por:

```vue
<script setup>
import { computed } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SocialLoginButtons from '@/Components/SocialLoginButtons.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const socialProviders = computed(() => page.props.socialProviders ?? { google: false, facebook: false });

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />

        <div class="mb-6">
            <h1 class="font-serif text-2xl text-gold">Iniciar sesión</h1>
            <p class="mt-1 text-sm text-mist">Entra para ver tus estanterías.</p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-success">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Correo electrónico" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Contraseña" />

                <PasswordInput
                    id="password"
                    class="mt-1"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="flex min-h-11 items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-mist">Recordarme</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    href="/forgot-password"
                    class="text-sm text-magic underline hover:text-gold focus:outline-none focus:ring-2 focus:ring-magic"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :class="{ 'opacity-50': form.processing }"
                :disabled="form.processing"
            >
                Entrar
            </PrimaryButton>
        </form>

        <div class="my-5 flex items-center gap-3 text-[11px] uppercase tracking-[0.16em] text-mist">
            <span class="h-px flex-1 bg-line" />
            o continúa con
            <span class="h-px flex-1 bg-line" />
        </div>

        <SocialLoginButtons :providers="socialProviders" />

        <div class="mt-6 border-t border-line pt-5 text-center text-sm text-mist">
            ¿Aún no tienes cuenta?
            <Link href="/register" class="font-medium text-magic underline hover:text-gold">
                Crear cuenta
            </Link>
        </div>
    </GuestLayout>
</template>
```

- [ ] **Step 4: Actualizar `Register.vue`**

Cambia las importaciones para incluir `computed`, `usePage`, `PasswordInput` y `SocialLoginButtons`:

```js
import { computed } from 'vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import SocialLoginButtons from '@/Components/SocialLoginButtons.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
```

Añade después de `const form = useForm(...)`:

```js
const page = usePage();
const socialProviders = computed(() => page.props.socialProviders ?? { google: false, facebook: false });
```

Sustituye los dos `TextInput` de contraseña por:

```vue
<PasswordInput
    id="password"
    class="mt-1"
    v-model="form.password"
    required
    autocomplete="new-password"
/>
```

```vue
<PasswordInput
    id="password_confirmation"
    class="mt-1"
    v-model="form.password_confirmation"
    required
    autocomplete="new-password"
/>
```

Añade después del `PrimaryButton` y antes del bloque de «¿Ya tienes cuenta?»:

```vue
<div class="my-5 flex items-center gap-3 text-[11px] uppercase tracking-[0.16em] text-mist">
    <span class="h-px flex-1 bg-line" />
    o continúa con
    <span class="h-px flex-1 bg-line" />
</div>

<SocialLoginButtons :providers="socialProviders" />
```

- [ ] **Step 5: Usar `PasswordInput` en restablecer y confirmar**

En `resources/js/Pages/Auth/ResetPassword.vue`:

- Importa `PasswordInput` y conserva `TextInput` para el campo de correo.
- Sustituye los dos campos de contraseña por:

```vue
<PasswordInput
    id="password"
    class="mt-1"
    v-model="form.password"
    required
    autocomplete="new-password"
/>
```

```vue
<PasswordInput
    id="password_confirmation"
    class="mt-1"
    v-model="form.password_confirmation"
    required
    autocomplete="new-password"
/>
```

En `resources/js/Pages/Auth/ConfirmPassword.vue`:

- Importa `PasswordInput` y elimina la importación de `TextInput`.
- Sustituye el campo de contraseña por:

```vue
<PasswordInput
    id="password"
    class="mt-1"
    v-model="form.password"
    required
    autocomplete="current-password"
    autofocus
/>
```

`ForgotPassword.vue` y `VerifyEmail.vue` no necesitan cambios de código: heredan la nueva estructura de `GuestLayout`.

- [ ] **Step 6: Ejecutar los tests de las pantallas**

Run:

```bash
npm run test -- resources/js/Pages/Auth/__tests__/Login.test.js resources/js/Pages/Auth/__tests__/Register.test.js
```

Expected: PASS con 3 tests.

- [ ] **Step 7: Pausa de revisión**

No hagas commit.

---

## Task 7: Pantalla «Elige tu apodo»

**Files:**
- Create: `resources/js/Pages/Auth/CompleteNickname.vue`
- Create: `resources/js/Pages/Auth/__tests__/CompleteNickname.test.js`

- [ ] **Step 1: Escribir el test que falla**

Crea `resources/js/Pages/Auth/__tests__/CompleteNickname.test.js`:

```js
import { mount } from '@vue/test-utils';
import { h } from 'vue';
import { describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    post: vi.fn(),
    page: { props: { flash: {} } },
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: {
        props: ['href'],
        inheritAttrs: false,
        setup(props, { attrs, slots }) {
            return () => h('a', { href: props.href, ...attrs }, slots.default?.());
        },
    },
    usePage: () => mocks.page,
    router: { post: vi.fn() },
    useForm: () => ({
        nickname: '',
        processing: false,
        errors: {},
        post: mocks.post,
    }),
}));

import CompleteNickname from '../CompleteNickname.vue';

describe('CompleteNickname', () => {
    it('muestra el campo de apodo y el botón continuar', () => {
        const wrapper = mount(CompleteNickname);

        expect(wrapper.text()).toContain('Elige tu apodo');
        expect(wrapper.find('#nickname').exists()).toBe(true);
        expect(wrapper.text()).toContain('Continuar');
    });
});
```

- [ ] **Step 2: Ejecutar el test y comprobar que falla**

Run:

```bash
npm run test -- resources/js/Pages/Auth/__tests__/CompleteNickname.test.js
```

Expected: FAIL porque la página no existe.

- [ ] **Step 3: Crear `CompleteNickname.vue`**

Crea `resources/js/Pages/Auth/CompleteNickname.vue`:

```vue
<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const form = useForm({
    nickname: '',
});

const submit = () => {
    form.post('/complete-profile');
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <GuestLayout>
        <Head title="Elige tu apodo" />

        <div class="mb-6">
            <h1 class="font-serif text-2xl text-gold">Elige tu apodo</h1>
            <p class="mt-1 text-sm text-mist">
                Así te saludaremos cuando entres a tu biblioteca.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="nickname" value="Apodo" />

                <TextInput
                    id="nickname"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.nickname"
                    required
                    autofocus
                    maxlength="50"
                    autocomplete="nickname"
                />

                <p class="mt-1 text-xs text-mist">
                    Puedes cambiarlo más tarde en Ajustes → Perfil.
                </p>

                <InputError class="mt-2" :message="form.errors.nickname" />
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :class="{ 'opacity-50': form.processing }"
                :disabled="form.processing"
            >
                Continuar
            </PrimaryButton>

            <button
                type="button"
                class="block w-full text-center text-sm text-magic underline hover:text-gold focus:outline-none focus:ring-2 focus:ring-magic"
                @click="logout"
            >
                Salir
            </button>
        </form>
    </GuestLayout>
</template>
```

- [ ] **Step 4: Ejecutar el test de la página**

Run:

```bash
npm run test -- resources/js/Pages/Auth/__tests__/CompleteNickname.test.js
```

Expected: PASS con 1 test.

- [ ] **Step 5: Pausa de revisión**

No hagas commit.

---

## Task 8: Documentación y verificación final

**Files:**
- Modify: `docs/DISENO.md`
- Create: `docs/oauth-setup.md`

- [ ] **Step 1: Actualizar la sección de autenticación de `docs/DISENO.md`**

Sustituye la sección `### Autenticación` por:

```markdown
### Autenticación

- Composición de dos columnas en escritorio: columna visual a la izquierda y formulario a la derecha.
- La columna visual usa `arcane-sky`, `ArcaneStars` y una placa de cristal con `LibraryEmblem`: «Biblioteca» en arco, libro abierto y destellos `gold`/`magic`.
- El formulario va sobre `night-raised`, con ancho máximo de 420 px y título en Cormorant `gold`.
- En móvil, el emblema compacto ocupa una franja superior de 220–260 px y el formulario queda debajo.
- El campo de contraseña usa `PasswordInput`, con ojo para mostrar/ocultar y área táctil de 44 px.
- Iniciar sesión y crear cuenta muestran `SocialLoginButtons` con Google y Facebook. Sin credenciales OAuth, los botones aparecen deshabilitados con «Próximamente».
- Al configurar Google/Facebook, el primer acceso social pide el apodo en una pantalla propia antes de entrar a la biblioteca.
- Los errores OAuth se muestran en `danger` encima del formulario.
- Enlaces en `magic`; errores en `danger`.
```

- [ ] **Step 2: Añadir la fila al registro de cambios**

Añade al final de la tabla **Registro de cambios**:

```markdown
| 2026-10-28 | Pantallas de acceso en dos columnas: emblema arcano con «Biblioteca» en arco, libro abierto y destellos sobre placa de cristal; formulario a la derecha, contraseña visible, y botones de Google/Facebook deshabilitados hasta configurar OAuth. En móvil el emblema va arriba. | Agente |
```

- [ ] **Step 3: Ejecutar Pint sobre los PHP modificados**

Run:

```bash
php vendor/bin/pint --test app/Http/Controllers/Auth/RegisteredUserController.php app/Http/Controllers/Auth/SocialAuthController.php app/Http/Controllers/Auth/CompleteNicknameController.php app/Http/Middleware/EnsureNicknameIsSet.php app/Http/Middleware/HandleInertiaRequests.php app/Models/SocialAccount.php app/Models/User.php app/Services/DefaultShelvesService.php database/migrations/2026_10_28_000001_create_social_accounts_table.php bootstrap/app.php routes/web.php routes/auth.php tests/Feature/Auth/SocialProvidersTest.php tests/Feature/Auth/SocialAuthTest.php tests/Feature/Auth/CompleteNicknameTest.php tests/Feature/Auth/NicknameGateTest.php
```

Expected: PASS. Si falla, ejecuta `php vendor/bin/pint` sobre esos archivos y vuelve a comprobar.

- [ ] **Step 4: Ejecutar toda la suite backend**

Run:

```bash
php artisan test
```

Expected: PASS con todos los tests, incluidos los nuevos.

- [ ] **Step 5: Ejecutar toda la suite frontend**

Run:

```bash
npm run test
```

Expected: PASS con todos los tests, incluidos los nuevos.

- [ ] **Step 6: Compilar assets**

Run:

```bash
npm run build
```

Expected: build correcto sin errores.

- [ ] **Step 7: Verificar visualmente en navegador**

Con la aplicación en local:

1. Abrir `/login` a 1280×900: dos columnas, emblema legible, formulario centrado, botones Google/Facebook deshabilitados con «Próximamente».
2. Abrir `/login` a 360×800: emblema arriba, formulario debajo, sin scroll horizontal.
3. Probar el ojo de contraseña.
4. Comprobar que `/register` muestra también los botones sociales y todos los campos.
5. Comprobar `/forgot-password`, `/reset-password/{token}`, `/verify-email` y `/confirm-password` con la nueva estructura.
6. Con `prefers-reduced-motion: reduce`, comprobar que los destellos no se animan.

- [ ] **Step 8: Documentar la configuración de Google y Facebook**

Crea `docs/oauth-setup.md`:

````markdown
# Configurar Google y Facebook

Los botones sociales se activan solos cuando existen las credenciales. Sin ellas, la interfaz los muestra deshabilitados con «Próximamente».

## Google

1. Entrar en Google Cloud Console → APIs & Services → Credentials.
2. Crear un OAuth client ID de tipo «Web application».
3. Añadir como Authorized redirect URI: `{APP_URL}/auth/google/callback`.
4. Copiar el Client ID y el Client Secret a `.env`:

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
```

## Facebook

1. Entrar en Facebook Developers → Create App → Consumer.
2. Añadir el producto «Facebook Login» y configurar Valid OAuth Redirect URIs: `{APP_URL}/auth/facebook/callback`.
3. Copiar el App ID y el App Secret a `.env`:

```dotenv
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=
```

## Producción

- Usar HTTPS. Facebook exige HTTPS y que la app esté en modo Live.
- Completar la política de privacidad y los datos de la app en Facebook Developers.
- Tras cambiar `.env`, ejecutar `php artisan config:clear` (o usar el endpoint de caché del despliegue).
- El primer acceso con Google o Facebook crea la cuenta y pide el apodo antes de entrar a la biblioteca.
- Si el correo ya existe, la cuenta se vincula automáticamente con el proveedor.
````

- [ ] **Step 9: Pausa de revisión final**

No hagas commit. Informa a la usuaria de los archivos modificados y deja que ella revise y commitee.

---

## Criterios de aceptación

- [ ] Las seis pantallas de acceso usan la composición de dos columnas.
- [ ] El emblema muestra «Biblioteca» en arco, libro abierto y destellos.
- [ ] La paleta arcana se mantiene; no se introducen azul/blanco de la referencia.
- [ ] En móvil el arte va arriba y el formulario debajo, sin desbordes.
- [ ] Login y registro muestran Google y Facebook deshabilitados sin credenciales.
- [ ] Con credenciales, los botones inician el flujo OAuth real.
- [ ] Una cuenta nueva por Google/Facebook elige apodo antes de entrar.
- [ ] Un correo existente se vincula automáticamente.
- [ ] Las cuentas existentes no se ven afectadas.
- [ ] Los errores OAuth se muestran en español y sin detalles técnicos.
- [ ] Pasan `php artisan test`, `npm run test`, `npm run build` y Pint.
- [ ] `docs/DISENO.md` refleja el cambio.
