# Reordenar libros dentro de una estantería — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. **Important:** the user makes all commits. Do not run `git add`, `git commit`, `git push` or `git tag`.

**Goal:** Permitir cambiar el orden de los libros dentro de su estantería arrastrándolos, con persistencia en base de datos y actualización optimista.

**Architecture:** Añadir `position` a `books`, ordenar la relación por posición y exponer `PATCH /books/reorder` con la lista completa de IDs de una estantería. En el cliente, extender `useLibraryDrag` para detectar una inserción antes/después de otro libro, calcular el nuevo orden con un helper puro y aplicar el cambio de forma optimista.

**Tech Stack:** Laravel, Inertia, Vue 3, Tailwind 4, Pest, Vitest.

---

## Task 1: Migración de posición y orden de la relación

**Files:**
- Test: `tests/Feature/BookTest.php`
- Create: `database/migrations/2026_10_27_000000_add_position_to_books_table.php`
- Modify: `app/Models/Book.php`
- Modify: `app/Models/Shelf.php`

- [x] **Step 1: Escribir la prueba que falla**

Añadir al final de `tests/Feature/BookTest.php`:

```php
it('ordena los libros por posición manual y usa el título como desempate', function () {
    $shelf = Shelf::factory()->for($this->user)->create();

    Book::factory()->for($this->user)->for($shelf)->create([
        'title' => 'Alfa',
        'position' => 1,
    ]);
    Book::factory()->for($this->user)->for($shelf)->create([
        'title' => 'Zeta',
        'position' => 0,
    ]);

    expect($shelf->books()->pluck('title')->all())->toBe(['Zeta', 'Alfa']);
});
```

- [x] **Step 2: Ejecutar la prueba para confirmar que falla**

Run: `php artisan test --filter="ordena los libros por posición manual"`

Expected: FAIL because the `position` column does not exist.

- [x] **Step 3: Crear la migración**

Create `database/migrations/2026_10_27_000000_add_position_to_books_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('shelf_id');
            $table->index(['shelf_id', 'position']);
        });

        $positions = [];

        DB::table('books')
            ->select('id', 'shelf_id')
            ->orderBy('shelf_id')
            ->orderBy('title')
            ->orderBy('id')
            ->get()
            ->each(function ($book) use (&$positions) {
                $positions[$book->shelf_id] = ($positions[$book->shelf_id] ?? -1) + 1;

                DB::table('books')
                    ->where('id', $book->id)
                    ->update(['position' => $positions[$book->shelf_id]]);
            });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['shelf_id', 'position']);
            $table->dropColumn('position');
        });
    }
};
```

- [x] **Step 4: Actualizar los modelos**

En `app/Models/Book.php`, añadir `position` a `$fillable`:

```php
protected $fillable = [
    'shelf_id', 'position', 'title', 'author', 'isbn', 'publisher',
    'published_year', 'cover_path', 'rating', 'notes',
];
```

En `app/Models/Shelf.php`, cambiar la relación:

```php
public function books(): HasMany
{
    return $this->hasMany(Book::class)
        ->orderBy('position')
        ->orderBy('title');
}
```

- [x] **Step 5: Ejecutar la prueba**

Run: `php artisan test --filter="ordena los libros por posición manual"`

Expected: PASS.

---

## Task 2: Posición al crear, editar y mover libros

**Files:**
- Test: `tests/Feature/BookTest.php`
- Modify: `app/Http/Controllers/BookController.php`

- [x] **Step 1: Añadir las pruebas que fallan**

Añadir al final de `tests/Feature/BookTest.php`:

```php
it('coloca un libro nuevo al final de su estantería', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    Book::factory()->for($this->user)->for($shelf)->create(['position' => 0]);
    Book::factory()->for($this->user)->for($shelf)->create(['position' => 1]);

    $this->post(route('books.store'), [
        'title' => 'Nuevo',
        'shelf_id' => $shelf->id,
    ])->assertRedirect();

    expect($this->user->books()->where('title', 'Nuevo')->first()->position)->toBe(2);
});

it('coloca al final un libro movido a otra estantería', function () {
    $from = Shelf::factory()->for($this->user)->create();
    $to = Shelf::factory()->for($this->user)->create();
    Book::factory()->for($this->user)->for($to)->create(['position' => 0]);
    $book = Book::factory()->for($this->user)->for($from)->create(['position' => 4]);

    $this->patch(route('books.move', $book), ['shelf_id' => $to->id])
        ->assertRedirect();

    expect($book->fresh()->position)->toBe(1);
});

it('coloca al final un libro al cambiar de estantería desde la ficha', function () {
    $from = Shelf::factory()->for($this->user)->create();
    $to = Shelf::factory()->for($this->user)->create();
    Book::factory()->for($this->user)->for($to)->create(['position' => 0]);
    $book = Book::factory()->for($this->user)->for($from)->create([
        'title' => 'Mudanza',
        'position' => 0,
    ]);

    $this->patch(route('books.update', $book), [
        'title' => 'Mudanza',
        'shelf_id' => $to->id,
    ])->assertRedirect();

    expect($book->fresh()->shelf_id)->toBe($to->id)
        ->and($book->fresh()->position)->toBe(1);
});
```

- [x] **Step 2: Ejecutar las pruebas para confirmar que fallan**

Run: `php artisan test --filter="al final"`

Expected: FAIL because the controller does not assign `position`.

- [x] **Step 3: Implementar la asignación de posiciones**

En `app/Http/Controllers/BookController.php`, añadir el import:

```php
use Illuminate\Support\Facades\DB;
```

En `store()`, antes de `$request->user()->books()->create($data);`, añadir:

```php
$data['position'] = $this->nextPosition((int) $data['shelf_id']);
```

En `update()`, sustituir el bloque `$book->update($data);` por:

```php
$oldShelfId = (int) $book->shelf_id;
$shelfChanged = isset($data['shelf_id']) && (int) $data['shelf_id'] !== $oldShelfId;

if ($shelfChanged) {
    $data['position'] = $this->nextPosition((int) $data['shelf_id']);
}

$book->update($data);

if ($shelfChanged) {
    $this->resequenceShelf($oldShelfId);
    $this->resequenceShelf((int) $book->shelf_id);
}
```

En `move()`, sustituir `$book->update(['shelf_id' => $data['shelf_id']]);` por:

```php
$sourceShelfId = (int) $book->shelf_id;
$targetShelfId = (int) $data['shelf_id'];

DB::transaction(function () use ($book, $sourceShelfId, $targetShelfId) {
    $book->update([
        'shelf_id' => $targetShelfId,
        'position' => $this->nextPosition($targetShelfId),
    ]);

    $this->resequenceShelf($sourceShelfId);
    $this->resequenceShelf($targetShelfId);
});
```

Añadir estos métodos privados antes de `ensureShelfOwnership()`:

```php
private function nextPosition(int $shelfId): int
{
    $max = Book::where('shelf_id', $shelfId)->max('position');

    return $max === null ? 0 : (int) $max + 1;
}

private function resequenceShelf(int $shelfId): void
{
    Book::where('shelf_id', $shelfId)
        ->orderBy('position')
        ->orderBy('title')
        ->orderBy('id')
        ->get(['id'])
        ->each(fn (Book $book, int $position) => $book->update(['position' => $position]));
}
```

- [x] **Step 4: Ejecutar las pruebas**

Run: `php artisan test --filter="al final"`

Expected: PASS.

---

## Task 3: Endpoint de reordenamiento

**Files:**
- Test: `tests/Feature/BookTest.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/BookController.php`

- [x] **Step 1: Escribir las pruebas que fallan**

Añadir al final de `tests/Feature/BookTest.php`:

```php
it('reordena los libros de una estantería propia', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $a = Book::factory()->for($this->user)->for($shelf)->create(['title' => 'A', 'position' => 0]);
    $b = Book::factory()->for($this->user)->for($shelf)->create(['title' => 'B', 'position' => 1]);
    $c = Book::factory()->for($this->user)->for($shelf)->create(['title' => 'C', 'position' => 2]);

    $this->patch(route('books.reorder'), [
        'shelf_id' => $shelf->id,
        'ids' => [$c->id, $a->id, $b->id],
    ])->assertRedirect();

    expect($shelf->books()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('no permite reordenar una estantería ajena', function () {
    $shelf = Shelf::factory()->for(User::factory())->create();
    $book = Book::factory()->for($shelf->user)->for($shelf)->create();

    $this->patch(route('books.reorder'), [
        'shelf_id' => $shelf->id,
        'ids' => [$book->id],
    ])->assertForbidden();
});

it('rechaza una lista que no contiene todos los libros de la estantería', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $a = Book::factory()->for($this->user)->for($shelf)->create();
    Book::factory()->for($this->user)->for($shelf)->create();

    $this->patch(route('books.reorder'), [
        'shelf_id' => $shelf->id,
        'ids' => [$a->id],
    ])->assertStatus(422);
});

it('rechaza IDs repetidos al reordenar', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $book = Book::factory()->for($this->user)->for($shelf)->create();

    $this->patch(route('books.reorder'), [
        'shelf_id' => $shelf->id,
        'ids' => [$book->id, $book->id],
    ])->assertSessionHasErrors('ids.1');
});
```

- [x] **Step 2: Ejecutar las pruebas para confirmar que fallan**

Run: `php artisan test --filter="reordena|reordenar|IDs repetidos"`

Expected: FAIL because the route does not exist.

- [x] **Step 3: Declarar la ruta antes de `{book}`**

En `routes/web.php`, insertar **antes** de `Route::patch('/books/{book}', ...)`:

```php
Route::patch('/books/reorder', [BookController::class, 'reorder'])->name('books.reorder');
```

- [x] **Step 4: Implementar el método `reorder()`**

En `app/Http/Controllers/BookController.php`, añadir el import:

```php
use Illuminate\Validation\ValidationException;
```

Añadir el método antes de `destroy()`:

```php
public function reorder(Request $request): RedirectResponse
{
    $data = $request->validate([
        'shelf_id' => ['required', 'integer'],
        'ids' => ['required', 'array', 'min:1'],
        'ids.*' => ['required', 'integer', 'distinct'],
    ]);

    $this->ensureShelfOwnership($request, $data['shelf_id']);

    $shelf = $request->user()->shelves()->findOrFail($data['shelf_id']);
    $expected = $shelf->books()->pluck('id')->sort()->values()->all();
    $submitted = collect($data['ids'])->sort()->values()->all();

    if ($expected !== $submitted) {
        throw ValidationException::withMessages([
            'ids' => __('ui.book_reorder_error'),
        ]);
    }

    DB::transaction(function () use ($data, $shelf) {
        foreach ($data['ids'] as $position => $id) {
            Book::where('id', $id)
                ->where('shelf_id', $shelf->id)
                ->update(['position' => $position]);
        }
    });

    return back();
}
```

- [x] **Step 5: Ejecutar las pruebas**

Run: `php artisan test --filter="reordena|reordenar|IDs repetidos"`

Expected: PASS.

---

## Task 4: Eliminar una estantería conservando el orden de los libros

**Files:**
- Test: `tests/Feature/ShelfTest.php`
- Modify: `app/Http/Controllers/ShelfController.php`

- [x] **Step 1: Añadir la prueba que falla**

Añadir al final de `tests/Feature/ShelfTest.php`:

```php
it('coloca los libros al final del respaldo al eliminar una estantería', function () {
    $pendientes = Shelf::factory()->for($this->user)->create([
        'name' => 'Pendientes',
        'position' => 0,
    ]);
    $otra = Shelf::factory()->for($this->user)->create(['position' => 1]);

    Book::factory()->for($this->user)->for($pendientes)->create([
        'title' => 'Ya estaba',
        'position' => 0,
    ]);
    $primero = Book::factory()->for($this->user)->for($otra)->create([
        'title' => 'Primero',
        'position' => 0,
    ]);
    $segundo = Book::factory()->for($this->user)->for($otra)->create([
        'title' => 'Segundo',
        'position' => 1,
    ]);

    $this->delete(route('shelves.destroy', $otra))->assertRedirect();

    expect($primero->fresh()->position)->toBe(1)
        ->and($segundo->fresh()->position)->toBe(2)
        ->and($pendientes->books()->pluck('id')->all())->toBe([
            $pendientes->books()->first()->id,
            $primero->id,
            $segundo->id,
        ]);
});
```

- [x] **Step 2: Ejecutar la prueba para confirmar que falla**

Run: `php artisan test --filter="al final del respaldo"`

Expected: FAIL because the books keep their old positions.

- [x] **Step 3: Reindexar el respaldo al eliminar**

En `app/Http/Controllers/ShelfController.php`, sustituir el bloque desde `$fallback = ...` hasta `$shelf->delete();` por:

```php
$fallback = $request->user()->shelves()
    ->where('id', '!=', $shelf->id)
    ->where('name', 'Pendientes')
    ->first()
    ?? $request->user()->shelves()
        ->where('id', '!=', $shelf->id)
        ->orderBy('position')
        ->first();

if (! $fallback) {
    $fallback = $request->user()->shelves()->create([
        'name' => 'Pendientes',
        'position' => 0,
        'view_mode' => 'covers',
    ]);
}

$start = $fallback->books()->max('position');
$start = $start === null ? 0 : (int) $start + 1;

$shelf->books()
    ->orderBy('position')
    ->orderBy('title')
    ->get()
    ->each(function ($book, int $index) use ($fallback, $start) {
        $book->update([
            'shelf_id' => $fallback->id,
            'position' => $start + $index,
        ]);
    });

$shelf->delete();

$fallback->books()
    ->orderBy('position')
    ->orderBy('title')
    ->get()
    ->each(fn ($book, int $position) => $book->update(['position' => $position]));
```

- [x] **Step 4: Ejecutar la prueba**

Run: `php artisan test --filter="al final del respaldo"`

Expected: PASS.

---

## Task 5: Helper puro de reordenamiento en el cliente

**Files:**
- Create: `resources/js/libraryOrder.js`
- Test: `resources/js/libraryOrder.test.js`

- [x] **Step 1: Escribir la prueba que falla**

Create `resources/js/libraryOrder.test.js`:

```js
import { describe, expect, it } from 'vitest';
import { moveBookWithin, sameBookOrder } from './libraryOrder';

const books = [
    { id: 1 },
    { id: 2 },
    { id: 3 },
    { id: 4 },
    { id: 5 },
];

describe('moveBookWithin', () => {
    it('mueve el primero al cuarto lugar', () => {
        const result = moveBookWithin(books, 1, 4, 'before');

        expect(result.map((book) => book.id)).toEqual([2, 3, 4, 1, 5]);
    });

    it('mueve un libro al final', () => {
        const result = moveBookWithin(books, 1, 5, 'after');

        expect(result.map((book) => book.id)).toEqual([2, 3, 4, 5, 1]);
    });

    it('mueve un libro antes de otro', () => {
        const result = moveBookWithin(books, 5, 2, 'before');

        expect(result.map((book) => book.id)).toEqual([1, 5, 2, 3, 4]);
    });

    it('no cambia el orden al soltar en la misma posición', () => {
        const result = moveBookWithin(books, 3, 3, 'before');

        expect(result.map((book) => book.id)).toEqual([1, 2, 3, 4, 5]);
        expect(sameBookOrder(books, result)).toBe(true);
    });

    it('no pierde ni duplica libros', () => {
        const result = moveBookWithin(books, 2, 4, 'after');

        expect(result).toHaveLength(books.length);
        expect(new Set(result.map((book) => book.id)).size).toBe(books.length);
    });
});
```

- [x] **Step 2: Ejecutar la prueba para confirmar que falla**

Run: `npm run test -- resources/js/libraryOrder.test.js`

Expected: FAIL because `libraryOrder.js` does not exist.

- [x] **Step 3: Implementar el helper**

Create `resources/js/libraryOrder.js`:

```js
export function moveBookWithin(books, bookId, targetId, place = 'before') {
    const from = books.findIndex((book) => book.id === bookId);
    const target = books.findIndex((book) => book.id === targetId);

    if (from === -1 || target === -1) {
        return books;
    }

    const next = books.slice();
    const [moved] = next.splice(from, 1);
    let insertAt = target + (place === 'after' ? 1 : 0);

    if (from < insertAt) {
        insertAt--;
    }

    insertAt = Math.max(0, Math.min(next.length, insertAt));
    next.splice(insertAt, 0, moved);

    return next;
}

export function sameBookOrder(a, b) {
    return a.length === b.length && a.every((book, index) => book.id === b[index].id);
}
```

- [x] **Step 4: Ejecutar la prueba**

Run: `npm run test -- resources/js/libraryOrder.test.js`

Expected: PASS (5 tests).

---

## Task 6: Detección de inserción y marcador visual

**Files:**
- Modify: `resources/js/useLibraryDrag.js`
- Modify: `resources/js/Components/Shelf.vue`
- Modify: `resources/js/Components/BookCard.vue`
- Modify: `resources/js/Components/BookSpine.vue`
- Modify: `resources/css/textures.css`
- Test: `resources/js/Components/__tests__/Shelf.test.js`
- Test: `resources/js/Components/__tests__/BookCard.test.js`
- Test: `resources/js/Components/__tests__/BookSpine.test.js`

- [x] **Step 1: Añadir las pruebas que fallan**

En `resources/js/Components/__tests__/Shelf.test.js`, añadir:

```js
it('marca la línea de inserción antes o después del libro indicado', () => {
    const wrapper = mount(Shelf, {
        props: {
            shelf: makeShelf(),
            dragActive: true,
            insertion: { shelfId: 7, bookId: 3, position: 'before' },
            draggingBookId: 3,
        },
    });

    expect(wrapper.find('[data-book-id="3"]').classes()).toContain('book-drop-before');

    const after = mount(Shelf, {
        props: {
            shelf: makeShelf(),
            dragActive: true,
            insertion: { shelfId: 7, bookId: 3, position: 'after' },
            draggingBookId: 3,
        },
    });

    expect(after.find('[data-book-id="3"]').classes()).toContain('book-drop-after');
});
```

En `resources/js/Components/__tests__/BookCard.test.js`, añadir:

```js
it('muestra el marcador de destino antes o después', () => {
    const before = mount(BookCard, {
        props: {
            book: { id: 1, title: 'Rayuela' },
            dropPosition: 'before',
        },
    });
    const after = mount(BookCard, {
        props: {
            book: { id: 1, title: 'Rayuela' },
            dropPosition: 'after',
        },
    });

    expect(before.classes()).toContain('book-drop-before');
    expect(after.classes()).toContain('book-drop-after');
});
```

En `resources/js/Components/__tests__/BookSpine.test.js`, añadir:

```js
it('muestra el marcador de destino antes o después', () => {
    const before = mount(BookSpine, {
        props: {
            book: { id: 1, title: 'Rayuela' },
            dropPosition: 'before',
        },
    });
    const after = mount(BookSpine, {
        props: {
            book: { id: 1, title: 'Rayuela' },
            dropPosition: 'after',
        },
    });

    expect(before.classes()).toContain('book-drop-before');
    expect(after.classes()).toContain('book-drop-after');
});
```

- [x] **Step 2: Ejecutar las pruebas para confirmar que fallan**

Run: `npm run test -- resources/js/Components/__tests__/Shelf.test.js resources/js/Components/__tests__/BookCard.test.js resources/js/Components/__tests__/BookSpine.test.js`

Expected: FAIL because the components do not accept or render `insertion` / `dropPosition`.

- [x] **Step 3: Extender `useLibraryDrag`**

En `resources/js/useLibraryDrag.js`, añadir el ref junto a `dropTargetId`:

```js
const insertion = ref(null);
```

Sustituir `updatePointer()` por:

```js
function updatePointer(x, y) {
    lastPoint = { x, y };
    pointer.value = { x, y };

    const target = shelfIdAt(x, y);

    if (!target || !sourceShelf) {
        dropTargetId.value = null;
        insertion.value = null;

        return;
    }

    if (target !== sourceShelf.id) {
        dropTargetId.value = target;
        insertion.value = null;

        return;
    }

    dropTargetId.value = null;

    const element = document.elementFromPoint(x, y)?.closest?.('[data-book-id]');

    if (!element) {
        const last = sourceShelf.books[sourceShelf.books.length - 1];
        insertion.value = last
            ? { shelfId: target, bookId: last.id, position: 'after' }
            : null;

        return;
    }

    const targetBookId = Number(element.dataset.bookId);

    if (targetBookId === draggedBook.value?.id) {
        insertion.value = null;

        return;
    }

    const rect = element.getBoundingClientRect();
    insertion.value = {
        shelfId: target,
        bookId: targetBookId,
        position: x >= rect.left + rect.width / 2 ? 'after' : 'before',
    };
}
```

Sustituir `finishDrag()` por:

```js
function finishDrag(x, y) {
    const targetId = shelfIdAt(x, y);
    const book = draggedBook.value;
    const currentInsertion = insertion.value;
    const shouldMove = Boolean(targetId && sourceShelf && targetId !== sourceShelf.id);
    const shouldReorder = Boolean(
        targetId && sourceShelf && targetId === sourceShelf.id && currentInsertion
    );

    cleanup();

    if (!book) {
        return;
    }

    if (shouldMove) {
        onDrop(book, targetId);
    } else if (shouldReorder) {
        onDrop(book, targetId, currentInsertion);
    }
}
```

En `cleanup()`, añadir:

```js
insertion.value = null;
```

En el objeto devuelto, añadir:

```js
insertion,
```

- [x] **Step 4: Pasar la inserción a los libros**

En `resources/js/Components/Shelf.vue`:

- Añadir la prop:

```js
insertion: { type: Object, default: null },
```

- Añadir esta función antes de `toggleView()`:

```js
function dropPositionFor(book) {
    if (
        props.insertion?.shelfId !== props.shelf.id ||
        props.insertion?.bookId !== book.id
    ) {
        return null;
    }

    return props.insertion.position;
}
```

- Pasar la prop a `BookCard`:

```vue
:drop-position="dropPositionFor(book)"
```

- Pasar la prop a `BookSpine`:

```vue
:drop-position="dropPositionFor(book)"
```

- [x] **Step 5: Añadir la prop y las clases en los libros**

En `resources/js/Components/BookCard.vue`:

- Añadir la prop:

```js
dropPosition: { type: String, default: null },
```

- Cambiar la clase del enlace para incluir `relative` y las clases de destino:

```vue
class="book-drag-source group relative block w-full cursor-grab"
:class="{
    'opacity-30': dragging,
    'book-drop-before': dropPosition === 'before',
    'book-drop-after': dropPosition === 'after',
}"
```

En `resources/js/Components/BookSpine.vue`:

- Añadir la prop:

```js
dropPosition: { type: String, default: null },
```

- Cambiar la clase del enlace para incluir `relative` y las clases de destino:

```vue
class="book-drag-source book-glow relative flex h-40 cursor-grab items-center justify-center overflow-hidden rounded-sm text-crema shadow-sm ring-1 ring-tinta/10"
:class="[
    colorClass,
    {
        'opacity-30': dragging,
        'book-drop-before': dropPosition === 'before',
        'book-drop-after': dropPosition === 'after',
    },
]"
```

- [x] **Step 6: Añadir el estilo de la línea**

Al final de `resources/css/textures.css`, antes del bloque de `prefers-reduced-motion` si se prefiere, añadir:

```css
.book-drop-before::before,
.book-drop-after::after {
  content: "";
  position: absolute;
  inset-block: -6px;
  width: 2px;
  border-radius: 9999px;
  background: var(--color-magic);
  box-shadow: 0 0 10px 2px color-mix(in srgb, var(--color-magic) 45%, transparent);
  pointer-events: none;
  z-index: 5;
}

.book-drop-before::before {
  inset-inline-start: -6px;
}

.book-drop-after::after {
  inset-inline-end: -6px;
}
```

- [x] **Step 7: Ejecutar las pruebas**

Run: `npm run test -- resources/js/Components/__tests__/Shelf.test.js resources/js/Components/__tests__/BookCard.test.js resources/js/Components/__tests__/BookSpine.test.js`

Expected: PASS.

---

## Task 7: Reordenamiento optimista en la Biblioteca y textos

**Files:**
- Modify: `resources/js/Pages/Library/Index.vue`
- Modify: `lang/es.json`, `lang/en.json`, `lang/ca.json`, `lang/gl.json`, `lang/pt.json`, `lang/fr.json`, `lang/de.json`, `lang/it.json`

- [x] **Step 1: Conectar el nuevo orden en `Library/Index.vue`**

Añadir el import:

```js
import { moveBookWithin, sameBookOrder } from '@/libraryOrder';
```

Añadir `insertion` a la desestructuración de `useLibraryDrag`:

```js
const {
    draggedBook,
    dragMode,
    pointer,
    isDragging,
    dropTargetId,
    insertion,
    onPointerDown,
    onTouchStart,
    onContextMenu,
    onClickCapture,
} = useLibraryDrag(() => localShelves.value, moveBook);
```

Añadir esta función antes de `onBeforeUnmount()`:

```js
function reorderBook(book, shelfId, target) {
    const shelf = localShelves.value.find((item) => item.id === shelfId);

    if (!shelf) {
        return;
    }

    const nextBooks = moveBookWithin(
        shelf.books,
        book.id,
        target.bookId,
        target.position
    );

    if (sameBookOrder(shelf.books, nextBooks)) {
        return;
    }

    const snapshot = cloneShelves(localShelves.value);
    shelf.books = nextBooks;

    let settled = false;

    router.patch(
        '/books/reorder',
        {
            shelf_id: shelf.id,
            ids: nextBooks.map((item) => item.id),
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                settled = true;
                showNotice('success', t('ui.book_reordered'));
            },
            onError: () => {
                settled = true;
                localShelves.value = snapshot;
                showNotice('error', t('ui.book_reorder_error'));
            },
            onFinish: () => {
                if (settled) {
                    return;
                }

                localShelves.value = snapshot;
                showNotice('error', t('ui.book_reorder_error'));
            },
        }
    );
}
```

Añadir esta función antes de `reorderBook()` para decidir entre mover y reordenar:

```js
function handleDrop(book, targetShelfId, target = null) {
    if (target) {
        reorderBook(book, targetShelfId, target);

        return;
    }

    moveBook(book, targetShelfId);
}
```

Pasar `handleDrop` al composable:

```js
} = useLibraryDrag(() => localShelves.value, handleDrop);
```

Pasar la inserción a `Shelf` en la plantilla:

```vue
:insertion="insertion"
```

- [x] **Step 2: Añadir los textos a los ocho idiomas**

En cada archivo `lang/*.json`, añadir junto a las claves de arrastre:

```json
"book_reordered": "Libro reordenado.",
"book_reorder_error": "No se pudo cambiar el orden. Inténtalo de nuevo."
```

Traducciones:

- `en`: `"Book reordered."`, `"Could not change the order. Try again."`
- `ca`: `"Llibre reordenat."`, `"No s'ha pogut canviar l'ordre. Torna-ho a provar."`
- `gl`: `"Libro reordenado."`, `"Non se puido cambiar a orde. Téntao de novo."`
- `pt`: `"Livro reordenado."`, `"Não foi possível alterar a ordem. Tente novamente."`
- `fr`: `"Livre réorganisé."`, `"Impossible de modifier l'ordre. Réessayez."`
- `de`: `"Buch neu angeordnet."`, `"Die Reihenfolge konnte nicht geändert werden. Versuche es erneut."`
- `it`: `"Libro riordinato."`, `"Impossibile cambiare l'ordine. Riprova."`

Actualizar `drag_hint` en cada idioma para mencionar también el cambio de posición. Español:

```json
"drag_hint": "Arrastra un libro para moverlo a otra estantería o cambiar su posición dentro de la misma."
```

Traducciones:

- `en`: `"Drag a book to move it to another shelf or change its position within the same shelf."`
- `ca`: `"Arrossega un llibre per moure'l a una altra prestatgeria o canviar-ne la posició dins de la mateixa."`
- `gl`: `"Arrastra un libro para movelo a outra estantería ou cambiar a súa posición dentro da mesma."`
- `pt`: `"Arraste um livro para movê-lo para outra estante ou alterar a posição dentro da mesma estante."`
- `fr`: `"Faites glisser un livre pour le déplacer vers une autre étagère ou changer sa position dans la même étagère."`
- `de`: `"Ziehe ein Buch, um es in ein anderes Regal zu verschieben oder seine Position im selben Regal zu ändern."`
- `it`: `"Trascina un libro per spostarlo in un'altra mensola o cambiarne la posizione nella stessa mensola."`

- [x] **Step 3: Ejecutar las pruebas**

Run: `npm run test`

Expected: PASS, incluidas las 5 pruebas nuevas de `libraryOrder` y las de marcador.

---

## Task 8: Documentación y verificación final

**Files:**
- Modify: `docs/DISENO.md`

- [x] **Step 1: Documentar la interacción**

En `docs/DISENO.md`, después de la sección **Arrastrar libros entre estanterías**, añadir:

```markdown
### Reordenar libros dentro de una estantería

- Al arrastrar dentro de la misma estantería, aparece una línea vertical de 2 px en `magic` con resplandor suave en el hueco donde quedará el libro.
- La línea se dibuja antes o después del libro bajo el puntero según su mitad horizontal. Si se suelta en la zona vacía, el libro va al final.
- Soltar en la misma posición no hace ninguna petición.
- El orden se guarda por libro y se mantiene al recargar. Los libros nuevos y los que llegan de otra estantería se añaden al final.
- El cambio es optimista y reversible: si falla, se restaura el orden anterior y se muestra un aviso `danger`.
- La reordenación se hace solo por arrastre; no se añade un campo de posición en la ficha.
```

Actualizar el punto 4 de la sección **Biblioteca (`Library/Index`)**:

```markdown
4. Arrastrar una portada o un lomo a otra estantería para mover el libro, o dentro de la misma estantería para cambiar su posición; en móvil, pulsación larga y arrastre.
```

Añadir al final de la tabla **Registro de cambios**:

```markdown
| 2026-10-27 | Reordenar libros dentro de una estantería: línea de inserción `magic`, cálculo antes/después, orden persistente por posición, actualización optimista y reversión con aviso. Los libros nuevos y los movidos a otra estantería se añaden al final. | Agente |
```

- [x] **Step 2: Ejecutar toda la verificación**

Run:

```bash
php artisan test
npm run test
./vendor/bin/pint --test
npm run build
```

Expected: all tests pass, Pint passes and Vite build succeeds.

- [x] **Step 3: Verificar en navegador real**

Con los servidores locales levantados (`npm run dev` y `php artisan serve`), comprobar:

- Arrastrar una portada del primer lugar al cuarto o quinto y confirmar que la línea aparece en el hueco correcto.
- Repetir en modo lomos.
- Soltar en la zona vacía de la estantería: el libro va al final.
- Soltar en el mismo lugar: no hay petición ni aviso.
- Simular fallo de red: el orden vuelve al anterior y aparece el aviso de error.
- Mover un libro a otra estantería: se añade al final y sigue funcionando el aviso de movimiento.
- A 390 px con gesto táctil: pulsación larga, reordenar y soltar.
- Recargar: el orden manual se conserva.

- [x] **Step 4: Informar a la usuaria y dejar el commit a su cargo**

No ejecutar operaciones de git. Informar de los archivos modificados y del resultado de las pruebas.

---

## Self-review

- **Cobertura del spec:** migración y orden (Task 1), altas/movimientos/edición (Task 2), endpoint (Task 3), borrado de estantería (Task 4), helper de orden (Task 5), línea e interacción (Task 6), optimismo y textos (Task 7), documentación y verificación (Task 8).
- **Sin placeholders:** cada tarea incluye pruebas, código y comandos concretos.
- **Consistencia:** `position` es 0-based en servidor; `moveBookWithin(books, bookId, targetId, place)` se usa en Task 5 y Task 7; `insertion` tiene `{ shelfId, bookId, position }` en Task 6 y Task 7.
