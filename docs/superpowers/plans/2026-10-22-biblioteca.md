# Biblioteca — Plan de implementación

> **Para agentes ejecutores:** usar `superpowers:subagent-driven-development` (recomendado) o `superpowers:executing-plans`. Cada tarea se ejecuta con TDD y termina con una pausa para que **la usuaria revise y haga el commit**.

**Objetivo:** construir el MVP de Biblioteca: web responsive con autenticación, estanterías personalizables, alta de libros por ISBN/código de barras o búsqueda, portadas y lomos, y configuración de perfil.

**Arquitectura:** Laravel 13 + Inertia.js + Vue 3 + Tailwind CSS 4 sobre MySQL. Controladores delgados, servicios para búsqueda externa, Policies para autorización y portadas servidas por una ruta autenticada.

**Stack:** PHP 8.3+, Laravel 13, Inertia.js, Vue 3, Tailwind CSS 4, Vite, Pest/PHPUnit, Vitest, @zxing/browser, MySQL (`biblioteca`).

**Reglas de ejecución:**

1. **No ejecutar comandos de git.** La usuaria hace todos los commits. Los pasos marcados “Checkpoint” son pausas para que ella revise y commitee.
2. Antes de cerrar una tarea con cambios visuales, actualizar `docs/DISENO.md` y su registro de cambios.
3. Mantener la compatibilidad con InfinityFree: assets compilados en local, sin colas, sin enlaces simbólicos, sin `mail()`.
4. Todas las rutas privadas filtran por el usuario autenticado.

---

## Fase 0 — Fundación

### Tarea 1: Crear el proyecto Laravel 13 con Breeze (Vue/Inertia) y MySQL

**Archivos:**
- Crear: proyecto Laravel completo en `D:\Documentos\Proyectos_jhenny2\biblioteca`
- Modificar: `.env`, `.env.example`, `package.json`
- Conservar: `AGENTS.md`, `docs/`

- [x] **Paso 1: Verificar herramientas**

```powershell
php -v
composer -V
node -v
npm -v
```

Esperado: PHP 8.3+, Composer 2, Node 20+.

- [x] **Paso 2: Crear el esqueleto en una carpeta temporal y moverlo**

```powershell
Set-Location "D:\Documentos\Proyectos_jhenny2"
composer create-project laravel/laravel biblioteca-temp "^13.0" --prefer-dist
robocopy .\biblioteca-temp .\biblioteca /E /MOVE
Remove-Item .\biblioteca-temp -Recurse -Force
```

Esperado: el proyecto queda en `biblioteca` sin borrar `AGENTS.md` ni `docs/`.

- [x] **Paso 3: Instalar Breeze Vue/Inertia**

```powershell
Set-Location "D:\Documentos\Proyectos_jhenny2\biblioteca"
composer require laravel/breeze --dev
php artisan breeze:install vue --no-interaction
npm install
```

- [x] **Paso 4: Configurar MySQL**

Crear la base de datos en phpMyAdmin:

```sql
CREATE DATABASE IF NOT EXISTS biblioteca
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Editar `.env`:

```dotenv
APP_NAME=Biblioteca
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=biblioteca
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=sync
```

Copiar los mismos valores de base de datos a `.env.example` sin credenciales reales.

- [x] **Paso 5: Migrar y compilar**

```powershell
php artisan key:generate
php artisan migrate
npm run build
```

Esperado: migraciones OK y build sin errores.

- [x] **Paso 6: Verificar autenticación**

```powershell
php artisan test
```

Esperado: pruebas de Breeze en verde.

- [ ] **Paso 7: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 2: Tokens de diseño, tipografías y layout base

**Archivos:**
- Modificar: `resources/css/app.css`, `resources/js/app.js`, `resources/js/Layouts/AuthenticatedLayout.vue`, `resources/js/Layouts/GuestLayout.vue`
- Crear: `resources/css/textures.css`, `resources/js/Components/AppShell.vue`
- Modificar: `docs/DISENO.md` (registro de cambios)

- [x] **Paso 1: Instalar fuentes y verificar Tailwind 4**

```powershell
npm install @fontsource/cormorant-garamond @fontsource/inter
npm ls tailwindcss
```

Esperado: `tailwindcss@4.x`. Si Breeze instaló Tailwind 3, actualizar:

```powershell
npm install tailwindcss@^4 @tailwindcss/vite
```

Y añadir el plugin en `vite.config.js`:

```js
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true }), vue(), tailwindcss()],
})
```

- [x] **Paso 2: Definir tokens en `resources/css/app.css`**

```css
@import 'tailwindcss';
@import './textures.css';

@theme {
  --color-bosque: #2F4A3C;
  --color-salvia: #7A9A7E;
  --color-oliva: #556B4F;
  --color-oliva-hover: #465A41;
  --color-madera: #6B4F3A;
  --color-madera-clara: #8B6B4F;
  --color-pergamino: #F3E9D2;
  --color-crema: #FAF4E6;
  --color-tinta: #2B2620;
  --color-terracota: #A9714B;
  --color-dorado: #C2A878;

  --font-serif: "Cormorant Garamond", Georgia, serif;
  --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;

  --radius-control: 4px;
  --radius-card: 6px;
  --radius-modal: 12px;
}

@layer base {
  body {
    @apply bg-pergamino text-tinta font-sans antialiased;
  }

  h1, h2, h3 {
    @apply font-serif text-bosque;
  }

  :focus-visible {
    @apply outline-none ring-2 ring-dorado ring-offset-2 ring-offset-pergamino;
  }
}
```

- [x] **Paso 3: Crear `resources/css/textures.css`**

```css
.paper-texture {
  background-color: var(--color-pergamino);
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='120' height='120' filter='url(%23n)' opacity='0.035'/%3E%3C/svg%3E");
}

.wood-board {
  background-image: linear-gradient(180deg, var(--color-madera-clara), var(--color-madera));
  box-shadow: inset 0 1px 0 rgba(250, 244, 230, 0.25), 0 2px 6px rgba(43, 38, 32, 0.18);
}

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

- [x] **Paso 4: Importar fuentes en `resources/js/app.js`**

```js
import '@fontsource/cormorant-garamond/500.css';
import '@fontsource/cormorant-garamond/600.css';
import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';
```

- [x] **Paso 5: Crear `AppShell.vue` con navegación responsive**

```vue
<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth.user)

const links = [
  { label: 'Biblioteca', href: '/' },
]
</script>

<template>
  <div class="min-h-screen paper-texture md:flex">
    <aside class="hidden md:flex md:w-60 md:flex-col bg-bosque text-crema p-4">
      <Link :href="route('library.index')" class="font-serif text-2xl mb-8">Biblioteca</Link>
      <nav class="space-y-2">
        <Link
          v-for="link in links"
          :key="link.href"
          :href="link.href"
          class="block rounded px-3 py-2 hover:bg-white/10"
        >
          {{ link.label }}
        </Link>
      </nav>
      <div class="mt-auto text-sm text-crema/70">{{ user?.name }}</div>
    </aside>

    <div class="flex-1 pb-20 md:pb-0">
      <header class="flex items-center justify-between bg-bosque px-4 py-3 text-crema md:hidden">
        <span class="font-serif text-xl">Biblioteca</span>
      </header>
      <main class="mx-auto w-full max-w-[1200px] p-4 md:p-6">
        <slot />
      </main>
    </div>

    <nav class="fixed inset-x-0 bottom-0 flex justify-around bg-bosque py-2 text-crema md:hidden">
      <Link
        v-for="link in links"
        :key="link.href"
        :href="link.href"
        class="px-4 py-2 text-sm"
      >
        {{ link.label }}
      </Link>
    </nav>
  </div>
</template>
```

- [x] **Paso 6: Usar `AppShell` en `AuthenticatedLayout.vue`**

Sustituir el contenido del layout por:

```vue
<script setup>
import AppShell from '@/Components/AppShell.vue'
</script>

<template>
  <AppShell>
    <slot />
  </AppShell>
</template>
```

- [x] **Paso 7: Compilar y verificar**

```powershell
npm run build
```

Esperado: build sin errores; login y registro conservan su funcionamiento.

- [x] **Paso 8: Registrar el cambio en `docs/DISENO.md`**

Añadir en el registro de cambios:

```
| 2026-10-22 | Implementación inicial de tokens, fuentes y layout responsive. | Agente |
```

- [ ] **Paso 9: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 3: Configurar Pest y Vitest

**Archivos:**
- Modificar: `composer.json`, `package.json`, `vite.config.js`
- Crear: `vitest.config.js`, `resources/js/Components/__tests__/smoke.test.js`

- [x] **Paso 1: Instalar Pest**

```powershell
composer require pestphp/pest pestphp/pest-plugin-laravel --dev
.\vendor\bin\pest --init
```

Esperado: se crea `tests/Pest.php` y las pruebas existentes se ejecutan con Pest.

- [x] **Paso 2: Instalar Vitest**

```powershell
npm install -D vitest @vue/test-utils jsdom
```

Editar `package.json`:

```json
"scripts": {
  "dev": "vite",
  "build": "vite build",
  "test": "vitest run",
  "test:watch": "vitest"
}
```

- [x] **Paso 3: Crear `vitest.config.js`**

```js
import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'jsdom',
    globals: true,
  },
})
```

- [x] **Paso 4: Crear una prueba de humo**

`resources/js/Components/__tests__/smoke.test.js`:

```js
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import RatingStars from '../RatingStars.vue'

describe('entorno de pruebas', () => {
  it('monta un componente Vue', () => {
    const wrapper = mount(RatingStars, { props: { modelValue: 3 } })
    expect(wrapper.findAll('[data-star]')).toHaveLength(5)
  })
})
```

- [x] **Paso 5: Crear `RatingStars.vue` mínimo para que la prueba funcione**

`resources/js/Components/RatingStars.vue`:

```vue
<script setup>
defineProps({ modelValue: { type: Number, default: 0 } })
</script>

<template>
  <div class="flex gap-1" role="img" :aria-label="`${modelValue} de 5 estrellas`">
    <span
      v-for="star in 5"
      :key="star"
      data-star
      :class="star <= modelValue ? 'text-dorado' : 'text-dorado/40'"
    >★</span>
  </div>
</template>
```

- [x] **Paso 6: Ejecutar ambas suites**

```powershell
php artisan test
npm run test
```

Esperado: todo en verde.

- [ ] **Paso 7: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 4: Esquema de datos, modelos y estanterías iniciales

**Archivos:**
- Crear: `database/migrations/*_create_shelves_table.php`, `database/migrations/*_create_books_table.php`, `database/migrations/*_add_default_view_mode_to_users_table.php`
- Crear: `app/Models/Shelf.php`, `app/Models/Book.php`
- Modificar: `app/Models/User.php`, `app/Http/Controllers/Auth/RegisteredUserController.php`
- Crear: `database/factories/ShelfFactory.php`, `database/factories/BookFactory.php`
- Test: `tests/Feature/RegistrationTest.php`, `tests/Unit/ShelfModelTest.php`

- [x] **Paso 1: Escribir la prueba de registro**

`tests/Feature/RegistrationTest.php`:

```php
<?php

use App\Models\User;

it('crea las estanterías iniciales al registrarse', function () {
    $response = $this->post('/register', [
        'name' => 'Ana',
        'email' => 'ana@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'ana@example.com')->firstOrFail();

    expect($user->shelves()->pluck('name')->all())
        ->toBe(['Pendientes', 'Leyendo', 'Leídos']);
});
```

- [x] **Paso 2: Ejecutar y ver que falla**

```powershell
php artisan test --filter="estanterías iniciales"
```

Esperado: FAIL (no existe la tabla `shelves`).

- [x] **Paso 3: Crear las migraciones**

`create_shelves_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shelves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->unsignedInteger('position')->default(0);
            $table->enum('view_mode', ['covers', 'spines'])->default('covers');
            $table->timestamps();
            $table->index(['user_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelves');
    }
};
```

`create_books_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shelf_id')->constrained()->restrictOnDelete();
            $table->string('title', 255);
            $table->string('author', 255)->nullable();
            $table->string('isbn', 20)->nullable();
            $table->string('publisher', 150)->nullable();
            $table->unsignedSmallInteger('published_year')->nullable();
            $table->string('cover_path', 255)->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'shelf_id']);
            $table->index(['user_id', 'isbn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
```

`add_default_view_mode_to_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('default_view_mode', ['covers', 'spines'])->default('covers');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('default_view_mode');
        });
    }
};
```

- [x] **Paso 4: Crear los modelos**

`app/Models/Shelf.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shelf extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'position', 'view_mode'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class)->orderBy('title');
    }
}
```

`app/Models/Book.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'shelf_id', 'title', 'author', 'isbn', 'publisher',
        'published_year', 'cover_path', 'rating', 'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }
}
```

Añadir a `User.php`:

```php
public function shelves(): HasMany
{
    return $this->hasMany(Shelf::class)->orderBy('position');
}

public function books(): HasMany
{
    return $this->hasMany(Book::class);
}
```

Y `default_view_mode` a `$fillable`.

- [x] **Paso 5: Crear las estanterías en el registro**

En `RegisteredUserController@store`, dentro de una transacción:

```php
$user = DB::transaction(function () use ($request) {
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    foreach (['Pendientes', 'Leyendo', 'Leídos'] as $position => $name) {
        $user->shelves()->create(['name' => $name, 'position' => $position]);
    }

    return $user;
});

event(new Registered($user));
Auth::login($user);

return redirect(route('dashboard', absolute: false));
```

- [x] **Paso 6: Crear factories**

`ShelfFactory`: nombre aleatorio y posición 0. `BookFactory`: título, autor, ISBN ficticio válido, estantería del usuario.

- [x] **Paso 7: Migrar y ejecutar pruebas**

```powershell
php artisan migrate:fresh
php artisan test
```

Esperado: todas las pruebas en verde, incluida la de estanterías iniciales.

- [ ] **Paso 8: Checkpoint — la usuaria revisa y hace el commit**

---

## Fase 1 — Estanterías

### Tarea 5: CRUD de estanterías en servidor

**Archivos:**
- Crear: `app/Http/Requests/StoreShelfRequest.php`, `app/Http/Requests/UpdateShelfRequest.php`
- Crear: `app/Policies/ShelfPolicy.php`
- Crear: `app/Http/Controllers/ShelfController.php`
- Modificar: `routes/web.php`
- Test: `tests/Feature/ShelfTest.php`

- [ ] **Paso 1: Escribir las pruebas**

`tests/Feature/ShelfTest.php`:

```php
<?php

use App\Models\Shelf;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('crea una estantería para el usuario autenticado', function () {
    $this->post(route('shelves.store'), ['name' => 'Favoritos'])
        ->assertRedirect();

    expect($this->user->shelves()->where('name', 'Favoritos')->exists())->toBeTrue();
});

it('no permite nombres vacíos', function () {
    $this->post(route('shelves.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('no permite modificar una estantería ajena', function () {
    $shelf = Shelf::factory()->for(User::factory())->create(['name' => 'Ajena']);

    $this->patch(route('shelves.update', $shelf), ['name' => 'Hackeada'])
        ->assertForbidden();
});

it('renombra una estantería propia', function () {
    $shelf = Shelf::factory()->for($this->user)->create(['name' => 'Vieja']);

    $this->patch(route('shelves.update', $shelf), ['name' => 'Nueva'])
        ->assertRedirect();

    expect($shelf->fresh()->name)->toBe('Nueva');
});
```

- [x] **Paso 2: Ejecutar y ver el fallo**

```powershell
php artisan test --filter=ShelfTest
```

Esperado: FAIL (rutas/controlador inexistentes).

- [x] **Paso 3: Implementar**

`StoreShelfRequest` y `UpdateShelfRequest`:

```php
public function authorize(): bool { return true; }

public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:60'],
        'view_mode' => ['sometimes', 'in:covers,spines'],
    ];
}
```

`ShelfPolicy`:

```php
public function update(User $user, Shelf $shelf): bool
{
    return $user->id === $shelf->user_id;
}

public function delete(User $user, Shelf $shelf): bool
{
    return $user->id === $shelf->user_id;
}
```

`ShelfController@store`:

```php
$position = $request->user()->shelves()->max('position') + 1;

$request->user()->shelves()->create([
    'name' => $request->validated('name'),
    'position' => $position,
    'view_mode' => $request->user()->default_view_mode,
]);

return back();
```

`ShelfController@update`:

```php
$this->authorize('update', $shelf);
$shelf->update($request->validated());

return back();
```

Rutas:

```php
Route::middleware('auth')->group(function () {
    Route::post('/shelves', [ShelfController::class, 'store'])->name('shelves.store');
    Route::patch('/shelves/{shelf}', [ShelfController::class, 'update'])->name('shelves.update');
});
```

- [x] **Paso 4: Ejecutar pruebas**

```powershell
php artisan test --filter=ShelfTest
```

Esperado: PASS.

- [ ] **Paso 5: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 6: Reordenar y eliminar estanterías

**Archivos:**
- Modificar: `app/Http/Controllers/ShelfController.php`, `routes/web.php`
- Test: `tests/Feature/ShelfTest.php`

- [x] **Paso 1: Escribir las pruebas**

```php
it('reordena las estanterías del usuario', function () {
    $a = Shelf::factory()->for($this->user)->create(['name' => 'A', 'position' => 0]);
    $b = Shelf::factory()->for($this->user)->create(['name' => 'B', 'position' => 1]);

    $this->post(route('shelves.reorder'), ['ids' => [$b->id, $a->id]])
        ->assertRedirect();

    expect($a->fresh()->position)->toBe(1)
        ->and($b->fresh()->position)->toBe(0);
});

it('mueve los libros a Pendientes al eliminar una estantería', function () {
    $pendientes = Shelf::factory()->for($this->user)->create(['name' => 'Pendientes']);
    $otra = Shelf::factory()->for($this->user)->create(['name' => 'Otra']);
    $book = Book::factory()->for($this->user)->for($otra)->create();

    $this->delete(route('shelves.destroy', $otra))->assertRedirect();

    expect($book->fresh()->shelf_id)->toBe($pendientes->id);
});
```

- [x] **Paso 2: Implementar `reorder`**

```php
public function reorder(Request $request)
{
    $data = $request->validate([
        'ids' => ['required', 'array'],
        'ids.*' => ['integer', 'exists:shelves,id'],
    ]);

    $ownIds = $request->user()->shelves()->pluck('id')->all();

    abort_unless(empty(array_diff($data['ids'], $ownIds)), 403);

    DB::transaction(function () use ($data) {
        foreach ($data['ids'] as $position => $id) {
            Shelf::where('id', $id)->update(['position' => $position]);
        }
    });

    return back();
}
```

- [x] **Paso 3: Implementar `destroy`**

```php
public function destroy(Shelf $shelf)
{
    $this->authorize('delete', $shelf);

    DB::transaction(function () use ($shelf) {
        $user = $shelf->user;

        $fallback = $user->shelves()
            ->where('name', 'Pendientes')
            ->whereKeyNot($shelf->id)
            ->first();

        if (! $fallback) {
            $fallback = $user->shelves()
                ->whereKeyNot($shelf->id)
                ->orderBy('position')
                ->first();
        }

        if (! $fallback) {
            $fallback = $user->shelves()->create([
                'name' => 'Pendientes',
                'position' => 0,
            ]);
        }

        $shelf->books()->update(['shelf_id' => $fallback->id]);
        $shelf->delete();

        $user->shelves()->orderBy('position')->get()
            ->each(fn ($item, $index) => $item->update(['position' => $index]));
    });

    return back();
}
```

Rutas:

```php
Route::post('/shelves/reorder', [ShelfController::class, 'reorder'])->name('shelves.reorder');
Route::delete('/shelves/{shelf}', [ShelfController::class, 'destroy'])->name('shelves.destroy');
```

- [x] **Paso 4: Ejecutar pruebas**

```powershell
php artisan test --filter=ShelfTest
```

Esperado: PASS.

- [ ] **Paso 5: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 7: Biblioteca visual y componentes de estantería

**Archivos:**
- Crear: `app/Http/Controllers/LibraryController.php`
- Crear: `resources/js/Pages/Library/Index.vue`
- Crear: `resources/js/Components/Shelf.vue`, `ShelfBoard.vue`, `BookCard.vue`, `BookSpine.vue`, `EmptyShelf.vue`, `RatingStars.vue` (completar)
- Modificar: `routes/web.php`
- Test frontend: `resources/js/Components/__tests__/BookCard.test.js`, `BookSpine.test.js`, `EmptyShelf.test.js`
- Modificar: `docs/DISENO.md` (registro de cambios)

- [x] **Paso 1: Crear la ruta y el controlador**

```php
Route::get('/', [LibraryController::class, 'index'])
    ->middleware('auth')
    ->name('library.index');
```

`LibraryController@index`:

```php
$shelves = $request->user()
    ->shelves()
    ->with('books')
    ->get();

return Inertia::render('Library/Index', [
    'shelves' => $shelves,
]);
```

Actualizar la ruta `dashboard` de Breeze para que redirija a la biblioteca, sin romper las pruebas de autenticación existentes:

```php
Route::get('/dashboard', fn () => redirect()->route('library.index'))
    ->middleware('auth')
    ->name('dashboard');
```

Actualizar `AppShell.vue` para que el enlace de la biblioteca use `route('library.index')` en lugar de `href="/"`, ahora que la ruta existe.

- [x] **Paso 2: Crear `BookCard.vue`**

```vue
<script setup>
const props = defineProps({
  book: { type: Object, required: true },
})

const coverUrl = props.book.cover_path
  ? route('covers.show', props.book.id)
  : null
</script>

<template>
  <article class="group w-full">
    <div class="aspect-[2/3] overflow-hidden rounded-md bg-crema shadow-sm ring-1 ring-madera/35 transition group-hover:-translate-y-0.5 group-hover:shadow-lg">
      <img
        v-if="coverUrl"
        :src="coverUrl"
        :alt="`Portada de ${book.title}`"
        class="h-full w-full object-cover"
        loading="lazy"
      >
      <div v-else class="flex h-full flex-col justify-center p-2 text-center">
        <span class="font-serif text-base font-semibold text-tinta">{{ book.title }}</span>
        <span class="mt-1 text-xs text-tinta/65">{{ book.author }}</span>
      </div>
    </div>
    <h3 class="mt-2 truncate font-serif text-[18px] leading-6 text-tinta">{{ book.title }}</h3>
    <p class="truncate text-sm text-tinta/65">{{ book.author }}</p>
  </article>
</template>
```

- [x] **Paso 3: Crear `BookSpine.vue` con ancho y color deterministas**

```vue
<script setup>
import { computed } from 'vue'

const props = defineProps({ book: { type: Object, required: true } })

const palette = ['bg-bosque', 'bg-oliva', 'bg-madera', 'bg-terracota', 'bg-salvia']

const hash = computed(() => {
  let value = 0
  for (const char of props.book.title) value = (value * 31 + char.charCodeAt(0)) % 997
  return value
})

const width = computed(() => 18 + (hash.value % 15))
const color = computed(() => palette[hash.value % palette.length])
</script>

<template>
  <button
    type="button"
    class="h-40 rounded-sm text-crema shadow-sm transition hover:-translate-y-1"
    :class="color"
    :style="{ width: `${width}px` }"
    :aria-label="`${book.title}, ${book.author ?? 'autor desconocido'}`"
    :title="`${book.title} — ${book.author ?? ''}`"
  />
</template>
```

- [x] **Paso 4: Crear `Shelf.vue` y `ShelfBoard.vue`**

`Shelf.vue` recibe `shelf`, muestra título, contador y libros. En modo portadas usa una rejilla; en modo lomos usa una fila con scroll. Debajo, `<ShelfBoard />`.

```vue
<script setup>
import BookCard from './BookCard.vue'
import BookSpine from './BookSpine.vue'
import EmptyShelf from './EmptyShelf.vue'
import ShelfBoard from './ShelfBoard.vue'

defineProps({ shelf: { type: Object, required: true } })
</script>

<template>
  <section>
    <div class="mb-2 flex items-baseline justify-between">
      <h2 class="text-2xl">{{ shelf.name }}</h2>
      <span class="text-sm text-tinta/65">{{ shelf.books.length }} libros</span>
    </div>

    <EmptyShelf v-if="shelf.books.length === 0" />

    <div
      v-else-if="shelf.view_mode === 'covers'"
      class="grid grid-cols-[repeat(auto-fill,minmax(88px,1fr))] items-end gap-4"
    >
      <BookCard v-for="book in shelf.books" :key="book.id" :book="book" />
    </div>

    <div v-else class="flex items-end gap-2 overflow-x-auto pb-1">
      <BookSpine v-for="book in shelf.books" :key="book.id" :book="book" />
    </div>

    <ShelfBoard />
  </section>
</template>
```

`ShelfBoard.vue`:

```vue
<template>
  <div class="wood-board mt-1 h-3 rounded-b-sm" aria-hidden="true" />
</template>
```

- [x] **Paso 5: Crear `EmptyShelf.vue`**

```vue
<template>
  <div class="rounded-md border border-dashed border-madera/35 p-6 text-center">
    <p class="font-serif text-lg text-bosque">Aún no hay libros en esta estantería.</p>
  </div>
</template>
```

- [x] **Paso 6: Crear `Library/Index.vue`**

```vue
<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Shelf from '@/Components/Shelf.vue'

defineProps({ shelves: { type: Array, required: true } })
</script>

<template>
  <Head title="Mi biblioteca" />
  <AuthenticatedLayout>
    <h1 class="mb-6 text-3xl md:text-4xl">Mi biblioteca</h1>

    <div class="space-y-8">
      <Shelf v-for="shelf in shelves" :key="shelf.id" :shelf="shelf" />
    </div>
  </AuthenticatedLayout>
</template>
```

El botón **Añadir libro** y su enlace en la navegación se incorporan en la tarea 13, cuando exista la ruta `books.create`.

- [x] **Paso 7: Escribir pruebas de componentes**

`BookCard.test.js`:

```js
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import BookCard from '../BookCard.vue'

describe('BookCard', () => {
  it('muestra título y autor cuando no hay portada', () => {
    const wrapper = mount(BookCard, {
      props: { book: { id: 1, title: 'Rayuela', author: 'Julio Cortázar', cover_path: null } },
    })

    expect(wrapper.text()).toContain('Rayuela')
    expect(wrapper.text()).toContain('Julio Cortázar')
  })
})
```

`BookSpine.test.js`: comprobar `aria-label` con título y autor.

`EmptyShelf.test.js`: comprobar el texto “Aún no hay libros en esta estantería.”.

- [x] **Paso 8: Ejecutar pruebas y build**

```powershell
npm run test
npm run build
```

Esperado: PASS y build correcto.

- [x] **Paso 9: Registrar el cambio en `docs/DISENO.md`**

- [ ] **Paso 10: Checkpoint — la usuaria revisa y hace el commit**

---

## Fase 2 — Libros

### Tarea 8: Utilidad de ISBN

**Archivos:**
- Crear: `app/Services/Isbn.php`
- Test: `tests/Unit/IsbnTest.php`

- [x] **Paso 1: Escribir la prueba**

```php
<?php

use App\Services\Isbn;

it('normaliza un isbn con guiones', function () {
    expect(Isbn::normalize('978-84-376-0494-7'))->toBe('9788437604947');
});

it('valida isbn 13 correctos', function () {
    expect(Isbn::isValid('9788437604947'))->toBeTrue();
});

it('rechaza isbn 13 incorrectos', function () {
    expect(Isbn::isValid('9788437604948'))->toBeFalse();
});

it('convierte isbn 10 a 13', function () {
    expect(Isbn::to13('8437604942'))->toBe('9788437604947');
});
```

- [x] **Paso 2: Implementar `Isbn`**

```php
<?php

namespace App\Services;

class Isbn
{
    public static function normalize(string $value): string
    {
        return strtoupper(preg_replace('/[^0-9Xx]/', '', $value));
    }

    public static function isValid(string $value): bool
    {
        $isbn = self::normalize($value);

        return match (strlen($isbn)) {
            10 => self::isValid10($isbn),
            13 => self::isValid13($isbn),
            default => false,
        };
    }

    public static function to13(string $value): ?string
    {
        $isbn = self::normalize($value);

        if (strlen($isbn) === 13) {
            return self::isValid13($isbn) ? $isbn : null;
        }

        if (strlen($isbn) !== 10 || ! self::isValid10($isbn)) {
            return null;
        }

        $base = '978'.substr($isbn, 0, 9);
        $sum = 0;

        foreach (str_split($base) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return $base.((10 - $sum % 10) % 10);
    }

    private static function isValid10(string $isbn): bool
    {
        if (! preg_match('/^\d{9}[\dX]$/', $isbn)) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 10; $i++) {
            $value = $isbn[$i] === 'X' ? 10 : (int) $isbn[$i];
            $sum += $value * (10 - $i);
        }

        return $sum % 11 === 0;
    }

    private static function isValid13(string $isbn): bool
    {
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return (10 - $sum % 10) % 10 === (int) $isbn[12];
    }
}
```

- [x] **Paso 3: Ejecutar**

```powershell
php artisan test --filter=IsbnTest
```

Esperado: PASS.

- [ ] **Paso 4: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 9: Servicios de búsqueda (Google Books + Open Library)

**Archivos:**
- Crear: `app/Services/GoogleBooksService.php`, `app/Services/OpenLibraryService.php`, `app/Services/BookLookupService.php`
- Modificar: `config/services.php`, `.env.example`
- Test: `tests/Feature/BookLookupServiceTest.php`

- [x] **Paso 1: Añadir configuración**

`config/services.php`:

```php
'google_books' => [
    'key' => env('GOOGLE_BOOKS_API_KEY'),
],
```

`.env.example`:

```dotenv
GOOGLE_BOOKS_API_KEY=
```

- [x] **Paso 2: Escribir las pruebas**

```php
<?php

use App\Services\BookLookupService;
use Illuminate\Support\Facades\Http;

it('encuentra un libro por isbn en Google Books', function () {
    Http::fake([
        'www.googleapis.com/books/v1/volumes*' => Http::response([
            'items' => [[
                'volumeInfo' => [
                    'title' => 'Rayuela',
                    'authors' => ['Julio Cortázar'],
                    'publisher' => 'Alfaguara',
                    'publishedDate' => '1963-06-28',
                    'imageLinks' => ['thumbnail' => 'https://covers.test/rayuela.jpg'],
                ],
            ]],
        ]),
    ]);

    $result = app(BookLookupService::class)->byIsbn('9788437604947');

    expect($result)->not->toBeEmpty()
        ->and($result[0]['title'])->toBe('Rayuela')
        ->and($result[0]['author'])->toBe('Julio Cortázar')
        ->and($result[0]['published_year'])->toBe(1963);
});

it('usa Open Library cuando Google Books falla', function () {
    Http::fake([
        'www.googleapis.com/*' => Http::response([], 500),
        'openlibrary.org/api/books*' => Http::response([
            'ISBN:9788437604947' => [
                'title' => 'Rayuela',
                'authors' => [['name' => 'Julio Cortázar']],
                'publishers' => [['name' => 'Alfaguara']],
                'publish_date' => '1963',
                'cover' => ['medium' => 'https://covers.test/rayuela.jpg'],
            ],
        ]),
    ]);

    $result = app(BookLookupService::class)->byIsbn('9788437604947');

    expect($result[0]['title'])->toBe('Rayuela')
        ->and($result[0]['author'])->toBe('Julio Cortázar');
});

it('rechaza un isbn inválido', function () {
    app(BookLookupService::class)->byIsbn('123');
})->throws(\Illuminate\Validation\ValidationException::class);
```

- [x] **Paso 3: Implementar los servicios**

`GoogleBooksService`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleBooksService
{
    public function byIsbn(string $isbn): array
    {
        return $this->fetch(['q' => "isbn:{$isbn}"]);
    }

    public function search(string $query): array
    {
        return $this->fetch(['q' => $query]);
    }

    private function fetch(array $params): array
    {
        $params = array_filter($params + [
            'key' => config('services.google_books.key'),
        ]);

        $response = Http::timeout(5)->get(
            'https://www.googleapis.com/books/v1/volumes',
            $params
        );

        if ($response->failed()) {
            return [];
        }

        return collect($response->json('items', []))
            ->map(fn (array $item) => $this->map($item))
            ->filter()
            ->values()
            ->all();
    }

    private function map(array $item): ?array
    {
        $info = $item['volumeInfo'] ?? [];
        $title = $info['title'] ?? null;

        if (! $title) {
            return null;
        }

        $identifiers = collect($info['industryIdentifiers'] ?? []);
        $isbn13 = $identifiers->firstWhere('type', 'ISBN_13');
        $isbn10 = $identifiers->firstWhere('type', 'ISBN_10');

        return [
            'title' => $title,
            'author' => implode(', ', $info['authors'] ?? []),
            'isbn' => $isbn13['identifier'] ?? $isbn10['identifier'] ?? null,
            'publisher' => $info['publisher'] ?? null,
            'published_year' => isset($info['publishedDate'])
                ? (int) substr($info['publishedDate'], 0, 4)
                : null,
            'cover_url' => $info['imageLinks']['thumbnail'] ?? null,
        ];
    }
}
```

`OpenLibraryService`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenLibraryService
{
    public function byIsbn(string $isbn): array
    {
        $response = Http::timeout(5)->get('https://openlibrary.org/api/books', [
            'bibkeys' => "ISBN:{$isbn}",
            'format' => 'json',
            'jscmd' => 'data',
        ]);

        if ($response->failed()) {
            return [];
        }

        $data = $response->json("ISBN:{$isbn}");

        if (! $data) {
            return [];
        }

        return [[
            'title' => $data['title'] ?? 'Sin título',
            'author' => collect($data['authors'] ?? [])->pluck('name')->implode(', '),
            'isbn' => $isbn,
            'publisher' => $data['publishers'][0]['name'] ?? null,
            'published_year' => isset($data['publish_date'])
                ? (int) substr($data['publish_date'], -4)
                : null,
            'cover_url' => $data['cover']['large'] ?? $data['cover']['medium'] ?? null,
        ]];
    }

    public function search(string $query): array
    {
        $response = Http::timeout(5)->get('https://openlibrary.org/search.json', [
            'q' => $query,
            'limit' => 10,
        ]);

        if ($response->failed()) {
            return [];
        }

        return collect($response->json('docs', []))
            ->map(fn (array $doc) => [
                'title' => $doc['title'] ?? 'Sin título',
                'author' => implode(', ', $doc['author_name'] ?? []),
                'isbn' => $doc['isbn'][0] ?? null,
                'publisher' => $doc['publisher'][0] ?? null,
                'published_year' => $doc['first_publish_year'] ?? null,
                'cover_url' => isset($doc['cover_i'])
                    ? "https://covers.openlibrary.org/b/id/{$doc['cover_i']}-M.jpg"
                    : null,
            ])
            ->values()
            ->all();
    }
}
```

`BookLookupService`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class BookLookupService
{
    public function __construct(
        private GoogleBooksService $google,
        private OpenLibraryService $openLibrary,
    ) {
    }

    public function byIsbn(string $value): array
    {
        $isbn = Isbn::normalize($value);

        if (! Isbn::isValid($isbn)) {
            throw ValidationException::withMessages([
                'isbn' => 'El ISBN no es válido.',
            ]);
        }

        return Cache::remember("lookup:isbn:{$isbn}", now()->addDay(), function () use ($isbn) {
            return $this->google->byIsbn($isbn)
                ?: $this->openLibrary->byIsbn($isbn);
        });
    }

    public function search(string $query): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 3) {
            return [];
        }

        return Cache::remember('lookup:q:'.md5($query), now()->addDay(), function () use ($query) {
            return $this->google->search($query)
                ?: $this->openLibrary->search($query);
        });
    }
}
```

- [x] **Paso 4: Ejecutar pruebas**

```powershell
php artisan test --filter=BookLookupServiceTest
```

Esperado: PASS.

- [ ] **Paso 5: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 10: Endpoint de búsqueda

**Archivos:**
- Crear: `app/Http/Controllers/BookLookupController.php`
- Modificar: `routes/web.php`
- Test: `tests/Feature/BookLookupEndpointTest.php`

- [x] **Paso 1: Escribir la prueba**

```php
it('devuelve resultados de búsqueda para el usuario autenticado', function () {
    $this->actingAs(User::factory()->create());

    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [['volumeInfo' => ['title' => 'Rayuela', 'authors' => ['Cortázar']]]],
        ]),
    ]);

    $this->getJson(route('books.lookup', ['q' => 'rayuela']))
        ->assertOk()
        ->assertJsonPath('results.0.title', 'Rayuela');
});

it('rechaza isbn inválido con 422', function () {
    $this->actingAs(User::factory()->create());

    $this->getJson(route('books.lookup', ['isbn' => '123']))
        ->assertStatus(422);
});
```

- [x] **Paso 2: Implementar**

```php
public function __invoke(Request $request, BookLookupService $lookup)
{
    $data = $request->validate([
        'isbn' => ['required_without:q', 'string', 'max:20'],
        'q' => ['required_without:isbn', 'string', 'min:3', 'max:120'],
    ]);

    $results = isset($data['isbn'])
        ? $lookup->byIsbn($data['isbn'])
        : $lookup->search($data['q']);

    return response()->json(['results' => $results]);
}
```

Ruta con límite:

```php
Route::get('/books/lookup', BookLookupController::class)
    ->middleware(['auth', 'throttle:30,1'])
    ->name('books.lookup');
```

- [x] **Paso 3: Ejecutar**

```powershell
php artisan test --filter=BookLookupEndpointTest
```

Esperado: PASS.

- [ ] **Paso 4: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 11: CRUD de libros y portadas

**Archivos:**
- Crear: `app/Http/Requests/StoreBookRequest.php`, `app/Http/Requests/UpdateBookRequest.php`
- Crear: `app/Policies/BookPolicy.php`
- Crear: `app/Http/Controllers/BookController.php`, `app/Http/Controllers/CoverController.php`
- Crear: `app/Services/CoverService.php`
- Modificar: `routes/web.php`
- Test: `tests/Feature/BookTest.php`, `tests/Feature/CoverTest.php`

- [x] **Paso 1: Escribir las pruebas**

```php
it('crea un libro manual en una estantería propia', function () {
    $shelf = Shelf::factory()->for($this->user)->create();

    $this->post(route('books.store'), [
        'title' => 'Cien años de soledad',
        'author' => 'Gabriel García Márquez',
        'shelf_id' => $shelf->id,
        'rating' => 5,
        'notes' => 'Una relectura pendiente.',
    ])->assertRedirect();

    expect($this->user->books()->where('title', 'Cien años de soledad')->exists())->toBeTrue();
});

it('no permite guardar en una estantería ajena', function () {
    $shelf = Shelf::factory()->for(User::factory())->create();

    $this->post(route('books.store'), [
        'title' => 'Intruso',
        'shelf_id' => $shelf->id,
    ])->assertForbidden();
});

it('mueve un libro a otra estantería propia', function () {
    $from = Shelf::factory()->for($this->user)->create();
    $to = Shelf::factory()->for($this->user)->create();
    $book = Book::factory()->for($this->user)->for($from)->create();

    $this->patch(route('books.move', $book), ['shelf_id' => $to->id])
        ->assertRedirect();

    expect($book->fresh()->shelf_id)->toBe($to->id);
});
```

- [x] **Paso 2: Implementar validación y autorización**

`StoreBookRequest`:

```php
public function rules(): array
{
    return [
        'title' => ['required', 'string', 'max:255'],
        'author' => ['nullable', 'string', 'max:255'],
        'isbn' => ['nullable', 'string', 'max:20'],
        'publisher' => ['nullable', 'string', 'max:150'],
        'published_year' => ['nullable', 'integer', 'min:0', 'max:2100'],
        'shelf_id' => ['required', 'integer', 'exists:shelves,id'],
        'rating' => ['nullable', 'integer', 'min:0', 'max:5'],
        'notes' => ['nullable', 'string', 'max:5000'],
        'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        'cover_url' => ['nullable', 'url', 'max:2048'],
    ];
}
```

En el controlador, verificar propiedad de la estantería:

```php
abort_unless(
    $request->user()->shelves()->whereKey($request->integer('shelf_id'))->exists(),
    403
);
```

`BookPolicy`: `view`, `update`, `delete` comprueban `$book->user_id === $user->id`.

- [x] **Paso 3: Implementar `CoverService`**

```php
public function storeUploaded(UploadedFile $file): string
{
    return $file->store('covers', 'public');
}

public function storeFromUrl(?string $url): ?string
{
    if (! $url) {
        return null;
    }

    try {
        $response = Http::timeout(10)->get($url);

        if ($response->failed()) {
            return null;
        }

        $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (! isset($extensions[$mime]) || strlen($response->body()) > 5 * 1024 * 1024) {
            return null;
        }

        $path = 'covers/'.Str::uuid().'.'.$extensions[$mime];
        Storage::disk('public')->put($path, $response->body());

        return $path;
    } catch (\Throwable) {
        return null;
    }
}

public function delete(?string $path): void
{
    if ($path) {
        Storage::disk('public')->delete($path);
    }
}
```

- [x] **Paso 4: Implementar `BookController@store`**

```php
$data = $request->validated();
$data['user_id'] = $request->user()->id;

unset($data['cover'], $data['cover_url']);

if ($request->hasFile('cover')) {
    $data['cover_path'] = $coverService->storeUploaded($request->file('cover'));
} elseif ($request->filled('cover_url')) {
    $data['cover_path'] = $coverService->storeFromUrl($request->input('cover_url'));
}

$data['isbn'] = $request->filled('isbn')
    ? Isbn::normalize($request->input('isbn'))
    : null;

$request->user()->books()->create($data);

return redirect()->route('library.index');
```

`update` reemplaza la portada y borra la anterior; `move` actualiza `shelf_id`; `destroy` borra la portada y el libro.

- [x] **Paso 5: Implementar `CoverController`**

```php
public function __invoke(Book $book)
{
    $this->authorize('view', $book);

    abort_unless($book->cover_path, 404);

    return Storage::disk('public')->response($book->cover_path);
}
```

Rutas:

```php
Route::get('/covers/{book}', CoverController::class)
    ->middleware('auth')
    ->name('covers.show');

Route::post('/books', [BookController::class, 'store'])->name('books.store');
Route::patch('/books/{book}', [BookController::class, 'update'])->name('books.update');
Route::patch('/books/{book}/move', [BookController::class, 'move'])->name('books.move');
Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
```

- [x] **Paso 6: Escribir prueba de portada ajena**

```php
it('no permite ver la portada de un libro ajeno', function () {
    $book = Book::factory()->for(User::factory())->create(['cover_path' => 'covers/x.jpg']);

    $this->actingAs(User::factory()->create())
        ->get(route('covers.show', $book))
        ->assertForbidden();
});
```

- [x] **Paso 7: Ejecutar**

```powershell
php artisan test --filter="BookTest|CoverTest"
```

Esperado: PASS.

- [ ] **Paso 8: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 12: Escáner de código de barras

**Archivos:**
- Instalar: `@zxing/browser`, `@zxing/library`
- Crear: `resources/js/Components/BarcodeScanner.vue`
- Test: `resources/js/Components/__tests__/BarcodeScanner.test.js`

- [x] **Paso 1: Instalar dependencias**

```powershell
npm install @zxing/browser @zxing/library
```

- [x] **Paso 2: Crear el componente**

```vue
<script setup>
import { BrowserMultiFormatReader } from '@zxing/browser'
import { onBeforeUnmount, onMounted, ref } from 'vue'

const emit = defineEmits(['detected', 'error'])
const video = ref(null)
const message = ref('')
let controls = null

onMounted(async () => {
  const reader = new BrowserMultiFormatReader()

  try {
    controls = await reader.decodeFromVideoDevice(
      undefined,
      video.value,
      (result) => {
        if (result) {
          emit('detected', result.getText())
          controls?.stop()
        }
      }
    )
  } catch {
    message.value = 'No se pudo acceder a la cámara. Escribe el ISBN manualmente.'
    emit('error')
  }
})

onBeforeUnmount(() => controls?.stop())
</script>

<template>
  <div class="space-y-2">
    <video ref="video" class="aspect-[4/3] w-full rounded-md bg-tinta/80" muted playsinline />
    <p v-if="message" class="text-sm text-terracota">{{ message }}</p>
  </div>
</template>
```

- [x] **Paso 3: Prueba con la librería simulada**

```js
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@zxing/browser', () => ({
  BrowserMultiFormatReader: class {
    async decodeFromVideoDevice(_device, _video, callback) {
      callback({ getText: () => '9788437604947' })
      return { stop: vi.fn() }
    }
  },
}))

import BarcodeScanner from '../BarcodeScanner.vue'

describe('BarcodeScanner', () => {
  it('emite el código detectado', async () => {
    const wrapper = mount(BarcodeScanner, { attachTo: document.body })
    await new Promise((resolve) => setTimeout(resolve, 0))
    expect(wrapper.emitted('detected')[0]).toEqual(['9788437604947'])
    wrapper.unmount()
  })
})
```

- [x] **Paso 4: Ejecutar**

```powershell
npm run test
```

Esperado: PASS.

- [ ] **Paso 5: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 13: Pantalla Añadir libro

**Archivos:**
- Crear: `app/Http/Controllers/BookCreateController.php` (o método `create` en `BookController`)
- Crear: `resources/js/Pages/Books/Create.vue`
- Modificar: `routes/web.php`
- Test: `tests/Feature/BookCreatePageTest.php`

- [x] **Paso 1: Ruta y controlador**

```php
Route::get('/books/create', [BookController::class, 'create'])
    ->middleware('auth')
    ->name('books.create');
```

`create` devuelve Inertia con las estanterías del usuario.

Además, añadir el botón a `Library/Index.vue` (importar `Link` desde `@inertiajs/vue3`):

```vue
<div class="mb-6 flex items-center justify-between">
  <h1 class="text-3xl md:text-4xl">Mi biblioteca</h1>
  <Link :href="route('books.create')" class="rounded bg-oliva px-4 py-2 text-crema hover:bg-oliva-hover">
    Añadir libro
  </Link>
</div>
```

Y en `AppShell.vue`, el enlace **Añadir** en la navegación inferior y en la barra lateral:

```vue
<Link :href="route('books.create')" class="block rounded px-3 py-2 hover:bg-white/10">
  Añadir libro
</Link>
```

- [x] **Paso 2: Crear la página con pestañas**

Estructura mínima:

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import BarcodeScanner from '@/Components/BarcodeScanner.vue'

const props = defineProps({ shelves: Array })

const tab = ref('search')
const results = ref([])
const searching = ref(false)
const error = ref('')

const form = useForm({
  title: '',
  author: '',
  isbn: '',
  publisher: '',
  published_year: null,
  shelf_id: props.shelves[0]?.id ?? null,
  rating: null,
  notes: '',
  cover: null,
  cover_url: null,
})

async function lookupIsbn(isbn) {
  searching.value = true
  error.value = ''

  try {
    const { data } = await axios.get(route('books.lookup'), { params: { isbn } })
    results.value = data.results
    if (data.results.length === 0) error.value = 'No encontramos ese ISBN. Puedes añadirlo manualmente.'
  } catch (e) {
    error.value = e.response?.data?.message ?? 'No se pudo consultar el ISBN.'
  } finally {
    searching.value = false
  }
}

async function search() {
  searching.value = true
  error.value = ''

  try {
    const { data } = await axios.get(route('books.lookup'), { params: { q: form.title } })
    results.value = data.results
  } finally {
    searching.value = false
  }
}

function select(book) {
  form.title = book.title
  form.author = book.author ?? ''
  form.isbn = book.isbn ?? ''
  form.publisher = book.publisher ?? ''
  form.published_year = book.published_year
  form.cover_url = book.cover_url
  tab.value = 'manual'
}

function submit() {
  form.post(route('books.store'), { forceFormData: true })
}
</script>

<template>
  <AuthenticatedLayout>
    <h1 class="mb-4 text-3xl">Añadir libro</h1>

    <div class="mb-4 flex gap-2">
      <button class="rounded px-3 py-2" :class="tab === 'search' ? 'bg-oliva text-crema' : 'bg-crema'" @click="tab = 'search'">Buscar</button>
      <button class="rounded px-3 py-2" :class="tab === 'manual' ? 'bg-oliva text-crema' : 'bg-crema'" @click="tab = 'manual'">Manual</button>
    </div>

    <section v-if="tab === 'search'" class="space-y-4">
      <BarcodeScanner @detected="lookupIsbn" />
      <div class="flex gap-2">
        <input v-model="form.isbn" class="w-full rounded border border-madera/35 bg-crema px-3 py-2" placeholder="ISBN" @keyup.enter="lookupIsbn(form.isbn)">
        <button class="rounded bg-oliva px-4 py-2 text-crema" :disabled="searching" @click="lookupIsbn(form.isbn)">Buscar ISBN</button>
      </div>
      <div class="flex gap-2">
        <input v-model="form.title" class="w-full rounded border border-madera/35 bg-crema px-3 py-2" placeholder="Título o autor">
        <button class="rounded bg-oliva px-4 py-2 text-crema" @click="search">Buscar</button>
      </div>
      <p v-if="error" class="text-sm text-terracota">{{ error }}</p>
      <ul class="space-y-2">
        <li v-for="(book, index) in results" :key="index">
          <button class="w-full rounded border border-madera/35 bg-crema p-3 text-left" @click="select(book)">
            <strong class="font-serif">{{ book.title }}</strong>
            <span class="block text-sm text-tinta/65">{{ book.author }}</span>
          </button>
        </li>
      </ul>
    </section>

    <form v-if="tab === 'manual'" class="space-y-4" @submit.prevent="submit">
      <input v-model="form.title" class="w-full rounded border border-madera/35 bg-crema px-3 py-2" placeholder="Título *">
      <input v-model="form.author" class="w-full rounded border border-madera/35 bg-crema px-3 py-2" placeholder="Autor">
      <input v-model="form.isbn" class="w-full rounded border border-madera/35 bg-crema px-3 py-2" placeholder="ISBN">
      <input v-model="form.publisher" class="w-full rounded border border-madera/35 bg-crema px-3 py-2" placeholder="Editorial">
      <input v-model.number="form.published_year" type="number" class="w-full rounded border border-madera/35 bg-crema px-3 py-2" placeholder="Año">
      <select v-model="form.shelf_id" class="w-full rounded border border-madera/35 bg-crema px-3 py-2">
        <option v-for="shelf in props.shelves" :key="shelf.id" :value="shelf.id">{{ shelf.name }}</option>
      </select>
      <input type="file" accept="image/jpeg,image/png,image/webp" @change="form.cover = $event.target.files[0]">
      <p v-if="form.errors.title" class="text-sm text-terracota">{{ form.errors.title }}</p>
      <button class="rounded bg-oliva px-4 py-2 text-crema" :disabled="form.processing">Guardar</button>
    </form>
  </AuthenticatedLayout>
</template>
```

- [x] **Paso 3: Prueba de la página**

```php
it('muestra el formulario de alta con las estanterías del usuario', function () {
    $user = User::factory()->create();
    Shelf::factory()->for($user)->create(['name' => 'Pendientes']);

    $this->actingAs($user)
        ->get(route('books.create'))
        ->assertOk()
        ->assertSee('Pendientes');
});
```

- [x] **Paso 4: Ejecutar**

```powershell
php artisan test --filter=BookCreatePageTest
npm run build
```

Esperado: PASS y build correcto.

- [ ] **Paso 5: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 14: Editar, mover y eliminar libros

**Archivos:**
- Crear: `resources/js/Pages/Books/Edit.vue`
- Modificar: `resources/js/Components/BookCard.vue` (enlace a edición)
- Modificar: `app/Http/Controllers/BookController.php`
- Test: `tests/Feature/BookTest.php`, `resources/js/Components/__tests__/RatingStars.test.js`

- [x] **Paso 1: Añadir ruta de edición**

```php
Route::get('/books/{book}/edit', [BookController::class, 'edit'])
    ->middleware('auth')
    ->name('books.edit');
```

`edit` autoriza con `$this->authorize('update', $book)` y devuelve el libro y las estanterías.

- [x] **Paso 2: Crear `Edit.vue`**

Formulario con los mismos campos, selector de estantería, botones **Guardar**, **Mover** y **Eliminar**. Usar `form.patch(route('books.update', book.id))`, `form.patch(route('books.move', book.id))` y `router.delete(route('books.destroy', book.id))` con confirmación.

- [x] **Paso 3: Hacer editable `RatingStars`**

Añadir soporte de clic y teclado que emita `update:modelValue`. Prueba:

```js
it('emite la nueva valoración al hacer clic', async () => {
  const wrapper = mount(RatingStars, { props: { modelValue: 0, editable: true } })
  await wrapper.findAll('[data-star]')[3].trigger('click')
  expect(wrapper.emitted('update:modelValue')[0]).toEqual([4])
})
```

- [x] **Paso 4: Ejecutar**

```powershell
php artisan test --filter=BookTest
npm run test
```

Esperado: PASS.

- [ ] **Paso 5: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 15: Cambio de modo portadas/lomos

**Archivos:**
- Modificar: `app/Http/Controllers/ShelfController.php` (update ya acepta `view_mode`)
- Modificar: `resources/js/Components/Shelf.vue`
- Test: `tests/Feature/ShelfTest.php`

- [x] **Paso 1: Prueba de cambio de modo**

```php
it('cambia el modo de vista de una estantería', function () {
    $shelf = Shelf::factory()->for($this->user)->create(['view_mode' => 'covers']);

    $this->patch(route('shelves.update', $shelf), ['name' => $shelf->name, 'view_mode' => 'spines'])
        ->assertRedirect();

    expect($shelf->fresh()->view_mode)->toBe('spines');
});
```

- [x] **Paso 2: Implementar el control en `Shelf.vue`**

Añadir un botón que alterne `covers`/`spines` y envíe `router.patch(route('shelves.update', shelf.id), { name: shelf.name, view_mode })`. Renderizar `BookSpine` cuando el modo sea `spines`.

- [x] **Paso 3: Ejecutar**

```powershell
php artisan test --filter=ShelfTest
npm run build
```

Esperado: PASS.

- [ ] **Paso 4: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 16: Configuraciones

**Archivos:**
- Crear: `app/Http/Controllers/SettingsController.php`
- Crear: `app/Http/Requests/UpdateProfileRequest.php`, `UpdatePasswordRequest.php`
- Crear: `resources/js/Pages/Settings/Index.vue`
- Modificar: `routes/web.php`
- Test: `tests/Feature/SettingsTest.php`

- [x] **Paso 1: Pruebas**

```php
it('actualiza el nombre y el correo del usuario', function () {
    $this->patch(route('settings.profile'), [
        'name' => 'Nuevo nombre',
        'email' => 'nuevo@example.com',
        'default_view_mode' => 'spines',
    ])->assertRedirect();

    $user = $this->user->fresh();

    expect($user->name)->toBe('Nuevo nombre')
        ->and($user->email)->toBe('nuevo@example.com')
        ->and($user->default_view_mode)->toBe('spines');
});

it('exige la contraseña actual para cambiarla', function () {
    $this->put(route('settings.password'), [
        'current_password' => 'incorrecta',
        'password' => 'nueva12345',
        'password_confirmation' => 'nueva12345',
    ])->assertSessionHasErrors('current_password');
});
```

- [x] **Paso 2: Implementar controlador y validación**

`UpdateProfileRequest`: nombre requerido, correo único ignorando el usuario actual, `default_view_mode` en `covers,spines`.

`UpdatePasswordRequest`: usar `current_password` y `Password::defaults()`.

`SettingsController@index` devuelve `Settings/Index` con `user` y las estanterías:

```php
return Inertia::render('Settings/Index', [
    'user' => $request->user(),
    'shelves' => $request->user()->shelves()->get(),
]);
```

Rutas:

```php
Route::middleware('auth')->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
});
```

Añadir el enlace **Configuraciones** en `AppShell.vue`, tanto en la barra lateral como en la navegación inferior:

```vue
<Link :href="route('settings.index')" class="block rounded px-3 py-2 hover:bg-white/10">
  Configuraciones
</Link>
```

- [x] **Paso 3: Crear `Settings/Index.vue`**

Secciones Perfil, Contraseña, Estanterías y Vista por defecto. Reutilizar el gestor de estanterías (crear, renombrar, reordenar con botones, eliminar) y formularios con `useForm`.

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
  user: { type: Object, required: true },
  shelves: { type: Array, required: true },
})

const profile = useForm({
  name: props.user.name,
  email: props.user.email,
  default_view_mode: props.user.default_view_mode,
})

const password = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

function saveProfile() {
  profile.patch(route('settings.profile'))
}

function savePassword() {
  password.put(route('settings.password'))
}
</script>

<template>
  <AuthenticatedLayout>
    <h1 class="mb-6 text-3xl">Configuraciones</h1>

    <form class="mb-8 space-y-3" @submit.prevent="saveProfile">
      <h2 class="text-2xl">Perfil</h2>
      <input v-model="profile.name" class="w-full rounded border border-madera/35 bg-crema px-3 py-2" placeholder="Nombre">
      <input v-model="profile.email" type="email" class="w-full rounded border border-madera/35 bg-crema px-3 py-2" placeholder="Correo">
      <select v-model="profile.default_view_mode" class="w-full rounded border border-madera/35 bg-crema px-3 py-2">
        <option value="covers">Portadas</option>
        <option value="spines">Lomos</option>
      </select>
      <button class="rounded bg-oliva px-4 py-2 text-crema" :disabled="profile.processing">Guardar perfil</button>
    </form>

    <form class="mb-8 space-y-3" @submit.prevent="savePassword">
      <h2 class="text-2xl">Contraseña</h2>
      <input v-model="password.current_password" type="password" class="w-full rounded border border-madera/35 bg-crema px-3 py-2" placeholder="Contraseña actual">
      <input v-model="password.password" type="password" class="w-full rounded border border-madera/35 bg-crema px-3 py-2" placeholder="Nueva contraseña">
      <input v-model="password.password_confirmation" type="password" class="w-full rounded border border-madera/35 bg-crema px-3 py-2" placeholder="Confirmar contraseña">
      <button class="rounded bg-oliva px-4 py-2 text-crema" :disabled="password.processing">Cambiar contraseña</button>
    </form>

    <section>
      <h2 class="mb-3 text-2xl">Estanterías</h2>
      <ul class="space-y-2">
        <li v-for="shelf in props.shelves" :key="shelf.id" class="flex items-center justify-between rounded border border-madera/35 bg-crema p-3">
          <span>{{ shelf.name }}</span>
        </li>
      </ul>
    </section>
  </AuthenticatedLayout>
</template>
```

Las acciones de crear, renombrar, reordenar y eliminar estanterías reutilizan las rutas de las tareas 5 y 6.

- [x] **Paso 4: Ejecutar**

```powershell
php artisan test --filter=SettingsTest
npm run build
```

Esperado: PASS.

- [x] **Paso 5: Registrar cambios visuales en `docs/DISENO.md`**

- [ ] **Paso 6: Checkpoint — la usuaria revisa y hace el commit**

---

## Fase 3 — Acabado y despliegue

### Tarea 17: Responsive, accesibilidad y estados vacíos

**Archivos:**
- Modificar: componentes y páginas con detalles visuales
- Modificar: `docs/DISENO.md`

- [ ] **Paso 1: Revisar en 360 px**

```powershell
npm run dev
```

Comprobar: navegación inferior, estantería con 1–2 portadas, modales a ancho completo, áreas táctiles de 44 px.

- [ ] **Paso 2: Revisar teclado y foco**

Tabular por toda la app; comprobar foco dorado, cierre de modales con Escape y `aria-label` en lomos y estrellas.

- [ ] **Paso 3: Revisar estados**

Sin libros, sin resultados de búsqueda, error de cámara, error de ISBN, portada rota.

- [x] **Paso 4: Actualizar `docs/DISENO.md` si se ajustó algo**

- [ ] **Paso 5: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 18: Verificación final

**Archivos:**
- Modificar: `docs/superpowers/specs/2026-10-22-biblioteca-design.md` (estado)

- [x] **Paso 1: Ejecutar backend y formato**

```powershell
php artisan test
.\vendor\bin\pint --test
```

Esperado: todas las pruebas pasan y Pint no reporta cambios.

- [x] **Paso 2: Ejecutar frontend y build**

```powershell
npm run test
npm run build
```

Esperado: PASS y build correcto.

- [ ] **Paso 3: Recorrido manual de los criterios de aceptación**

Comprobar los 10 criterios del spec, incluido el aislamiento entre usuarios.

- [x] **Paso 4: Actualizar el estado del spec**

Cambiar “Pendiente el plan de implementación” por “Implementado y verificado el 2026-10-22”.

- [ ] **Paso 5: Checkpoint — la usuaria revisa y hace el commit**

---

### Tarea 19: Preparación para InfinityFree (sin subir todavía)

**Archivos:**
- Crear: `config/deploy.php`
- Modificar: `routes/web.php` (endpoints de deploy)
- Crear: `.deploy-env.example`
- Crear: `docs/DESPLIEGUE.md`

Los archivos `deploy.sh`, `.htaccess` y `platform_check.php` se generan o parchean al ejecutar el skill `deploy-infinityfree`; no se crean a mano en esta tarea.

- [x] **Paso 1: Endpoints seguros de deploy**

```php
Route::get('/deploy/migrate/{token}', function (string $token) {
    abort_unless(config('deploy.token') && hash_equals(config('deploy.token'), $token), 403);

    Artisan::call('migrate', ['--force' => true]);

    return response()->json(['ok' => true]);
});

Route::get('/deploy/cache/{token}', function (string $token) {
    abort_unless(config('deploy.token') && hash_equals(config('deploy.token'), $token), 403);

    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    return response()->json(['ok' => true]);
});
```

Añadir `use Illuminate\Support\Facades\Artisan;` al inicio de `routes/web.php`.

`config/deploy.php`:

```php
<?php

return [
    'token' => env('DEPLOY_TOKEN'),
];
```

- [x] **Paso 2: Crear `.deploy-env.example`**

```dotenv
FTP_HOST=ftpupload.net
FTP_USER=
FTP_PASS=
FTP_PATH=/htdocs
VITE_ENABLED=true
EXTRA_UPLOADS=
```

- [x] **Paso 3: Documentar el despliegue**

`docs/DESPLIEGUE.md` debe incluir: crear base de datos en el panel, configurar `.env` de producción, compilar con `npm run build`, ejecutar el skill `deploy-infinityfree`, saltar el commit (lo hace la usuaria), corregir `platform_check.php`, migrar por endpoint, configurar SMTP externo en 587/TLS y comprobar HTTPS.

- [x] **Paso 4: Verificar localmente**

```powershell
php artisan route:list --path=deploy
```

Esperado: aparecen las dos rutas.

- [ ] **Paso 5: Checkpoint — la usuaria revisa y hace el commit**

**No ejecutar el despliegue real hasta que la usuaria lo pida.** En ese momento se usa el skill `deploy-infinityfree`, omitiendo el paso de commit.

---

## Autorevisión del plan

- [ ] Cada requisito del spec tiene al menos una tarea.
- [ ] Las rutas, modelos, servicios y componentes usan los mismos nombres en todas las tareas.
- [ ] No hay pasos “TBD” ni código sin definir.
- [ ] Los commits quedan en manos de la usuaria.
- [ ] La compatibilidad con InfinityFree está cubierta en la tarea 19.
