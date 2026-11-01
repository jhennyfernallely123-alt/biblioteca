# Objetos decorativos en las estanterías — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permitir decorar las estanterías con 15 tipos de objetos (tazas, flores, velas…) que se añaden desde Ajustes, se intercalan con los libros y se arrastran dentro de una estantería o a otra.

**Architecture:** Tabla `decorations` con un ancla opcional a un libro (`after_book_id`) y un orden dentro del hueco (`sort`). El orden de la balda se compone en el cliente con `mergeShelfItems`. El arrastre reutiliza `useLibraryDrag` generalizado a libros y objetos. Sin cambios en la API de libros.

**Tech Stack:** Laravel 13 (PHP 8.3), Inertia 2, Vue 3 `<script setup>`, Tailwind CSS 4, Pest, Vitest, MySQL.

**Reglas del repo (AGENTS.md):** la usuaria hace **todos** los commits. No ejecutar `git add`, `git commit`, `git push` ni `git tag`. Cada tarea termina con las pruebas en verde y los archivos listos, nunca con un commit.

**Shell:** Windows PowerShell 5.1. Los comandos de este plan se ejecutan con el envoltorio `cmd.exe /C '...'` desde la raíz `D:\Documentos\Proyectos_jhenny2\biblioteca`. No usar `&&` ni tuberías dentro de PowerShell.

---

## Estructura de archivos

**Backend**
- `database/migrations/2026_10_30_000003_create_decorations_table.php` — tabla nueva.
- `app/Models/Decoration.php` — modelo, listas `KINDS` y `COLORS`, relaciones.
- `database/factories/DecorationFactory.php` — factoría de pruebas.
- `app/Policies/DecorationPolicy.php` — autorización por dueño.
- `app/Http/Controllers/DecorationController.php` — crear, mover, quitar uno, quitar todos.
- `app/Models/User.php` — relación `decorations()`.
- `app/Models/Shelf.php` — relación `decorations()` ordenada.
- `app/Http/Controllers/LibraryController.php` — carga decoraciones.
- `app/Http/Controllers/SettingsController.php` — envía decoraciones a Ajustes.
- `routes/web.php` — cuatro rutas nuevas.

**Frontend**
- `resources/js/shelfItems.js` — `mergeShelfItems` y `resolveDecorationAnchor` (puro, testeable).
- `resources/js/Components/DecorationArt.vue` — 15 dibujos SVG.
- `resources/js/Components/ShelfDecoration.vue` — objeto en la balda con su botón ×.
- `resources/js/Components/DecorationSettings.vue` — sección de Ajustes.
- `resources/js/Components/DecorationDragGhost.vue` — miniatura al arrastrar.
- `resources/js/Components/Shelf.vue` — fila mixta.
- `resources/js/useLibraryDrag.js` — arrastre de libros y objetos.
- `resources/js/Pages/Library/Index.vue` — mover objetos con actualización optimista.
- `resources/js/Pages/Settings/Index.vue` — monta la sección nueva.
- `resources/css/textures.css` — clase `.decoration-drag-source`.

**Idiomas y docs**
- `lang/{es,en,fr,de,pt,it,gl,ca}.json` — 35 claves nuevas y `ui.drag_hint` ampliado.
- `docs/DISENO.md` — sección de componentes y registro de cambios.

**Pruebas**
- `tests/Feature/DecorationTest.php`
- `resources/js/shelfItems.test.js`
- `resources/js/Components/__tests__/DecorationArt.test.js`
- `resources/js/Components/__tests__/ShelfDecoration.test.js`
- `resources/js/Components/__tests__/DecorationSettings.test.js`
- `resources/js/Components/__tests__/Shelf.test.js` (ampliar)

---

## Task 1: Tabla, modelo, relaciones y factoría

**Files:**
- Create: `database/migrations/2026_10_30_000003_create_decorations_table.php`
- Create: `app/Models/Decoration.php`
- Create: `database/factories/DecorationFactory.php`
- Modify: `app/Models/User.php` (añadir relación)
- Modify: `app/Models/Shelf.php` (añadir relación)
- Test: `tests/Feature/DecorationTest.php`

- [ ] **Step 1: Escribir la prueba que falla**

Crear `tests/Feature/DecorationTest.php`:

```php
<?php

use App\Models\Book;
use App\Models\Decoration;
use App\Models\Shelf;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('guarda objetos con su estantería y su ancla', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $book = Book::factory()->for($this->user)->for($shelf)->create();

    $decoration = $this->user->decorations()->create([
        'shelf_id' => $shelf->id,
        'kind' => 'cup',
        'color' => 'rose',
        'after_book_id' => $book->id,
        'sort' => 0,
    ]);

    expect($decoration->fresh()->kind)->toBe('cup')
        ->and($decoration->shelf->id)->toBe($shelf->id)
        ->and($decoration->afterBook->id)->toBe($book->id);
});

it('ordena los objetos de una estantería por sort y luego por id', function () {
    $shelf = Shelf::factory()->for($this->user)->create();

    $second = Decoration::factory()->for($this->user)->for($shelf)->create(['sort' => 1]);
    $first = Decoration::factory()->for($this->user)->for($shelf)->create(['sort' => 0]);

    expect($shelf->decorations()->pluck('id')->all())->toBe([$first->id, $second->id]);
});

it('borra los objetos al borrar la estantería', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $decoration = Decoration::factory()->for($this->user)->for($shelf)->create();

    $shelf->delete();

    expect(Decoration::find($decoration->id))->toBeNull();
});

it('deja sin ancla el objeto al borrar el libro', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $book = Book::factory()->for($this->user)->for($shelf)->create();
    $decoration = Decoration::factory()->for($this->user)->for($shelf)->create([
        'after_book_id' => $book->id,
    ]);

    $book->delete();

    expect($decoration->fresh()->after_book_id)->toBeNull();
});
```

- [ ] **Step 2: Ejecutar y ver el fallo**

Run: `cmd.exe /C 'php artisan test --filter=DecorationTest'`
Expected: FAIL — `Class "App\Models\Decoration" not found`.

- [ ] **Step 3: Crear la migración**

`database/migrations/2026_10_30_000003_create_decorations_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decorations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shelf_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 32);
            $table->string('color', 16);
            $table->foreignId('after_book_id')->nullable()->constrained('books')->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['shelf_id', 'after_book_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decorations');
    }
};
```

- [ ] **Step 4: Crear el modelo**

`app/Models/Decoration.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Decoration extends Model
{
    use HasFactory;

    public const KINDS = [
        'cup', 'roses', 'plant', 'candle', 'hourglass', 'crystal_ball', 'lamp',
        'cat', 'skull', 'cage', 'astrolabe', 'tea_set', 'music_box', 'quill',
        'photo_frame',
    ];

    public const COLORS = ['rose', 'cream', 'green', 'forest', 'gold', 'clay'];

    protected $fillable = ['shelf_id', 'kind', 'color', 'after_book_id', 'sort'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }

    public function afterBook(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'after_book_id');
    }
}
```

- [ ] **Step 5: Añadir las relaciones**

En `app/Models/User.php`, después de `books()`:

```php
    public function decorations(): HasMany
    {
        return $this->hasMany(Decoration::class);
    }
```

En `app/Models/Shelf.php`, después de `books()`:

```php
    public function decorations(): HasMany
    {
        return $this->hasMany(Decoration::class)
            ->orderBy('sort')
            ->orderBy('id');
    }
```

- [ ] **Step 6: Crear la factoría**

`database/factories/DecorationFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Decoration;
use App\Models\Shelf;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Decoration>
 */
class DecorationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shelf_id' => Shelf::factory(),
            'kind' => fake()->randomElement(Decoration::KINDS),
            'color' => fake()->randomElement(Decoration::COLORS),
            'after_book_id' => null,
            'sort' => 0,
        ];
    }
}
```

- [ ] **Step 7: Migrar y ejecutar las pruebas**

Run: `cmd.exe /C 'php artisan migrate'`
Run: `cmd.exe /C 'php artisan test --filter=DecorationTest'`
Expected: 4 passed.

---

## Task 2: Claves de idioma en los 8 catálogos

**Files:**
- Modify: `lang/es.json`, `lang/en.json`, `lang/fr.json`, `lang/de.json`, `lang/pt.json`, `lang/it.json`, `lang/gl.json`, `lang/ca.json`

- [ ] **Step 1: Insertar las claves**

En cada archivo, añade las líneas justo después de la entrada `ui.switch_view` y sustituye el valor de `ui.drag_hint`.

`lang/es.json`:

```json
    "ui.decorations_heading": "Decoración",
    "ui.decorations_help": "Añade objetos a tu biblioteca y arrástralos por las estanterías.",
    "ui.decoration_add": "Añadir",
    "ui.decorations_remove_all": "Quitar todas",
    "ui.decorations_remove_all_confirm": "¿Quitar todos los objetos de tus estanterías?",
    "ui.decorations_placed": "Tus objetos",
    "ui.decorations_empty": "Aún no has añadido ningún objeto.",
    "ui.decoration_remove": "Quitar objeto",
    "ui.decoration_color": "Color",
    "ui.decoration_added": "«:name» añadido a «:shelf».",
    "ui.decoration_moved": "«:name» se movió a «:shelf».",
    "ui.decoration_move_error": "No se pudo mover el objeto. Inténtalo de nuevo.",
    "ui.decoration_removed": "«:name» se quitó de la biblioteca.",
    "ui.decorations_cleared": "Se quitaron todos los objetos.",
    "ui.decoration_cup": "Taza",
    "ui.decoration_roses": "Rosas",
    "ui.decoration_plant": "Planta",
    "ui.decoration_candle": "Vela",
    "ui.decoration_hourglass": "Reloj de arena",
    "ui.decoration_crystal_ball": "Bola de cristal",
    "ui.decoration_lamp": "Lámpara",
    "ui.decoration_cat": "Gato",
    "ui.decoration_skull": "Calavera",
    "ui.decoration_cage": "Jaula",
    "ui.decoration_astrolabe": "Astrolabio",
    "ui.decoration_tea_set": "Set de té",
    "ui.decoration_music_box": "Caja de música",
    "ui.decoration_quill": "Pluma y tintero",
    "ui.decoration_photo_frame": "Marco de fotos",
    "ui.color_rose": "Rosa",
    "ui.color_cream": "Crema",
    "ui.color_green": "Verde",
    "ui.color_forest": "Bosque",
    "ui.color_gold": "Dorado",
    "ui.color_clay": "Terracota",
```

`ui.drag_hint` en `es.json`:

```json
    "ui.drag_hint": "Arrastra un libro o un objeto para moverlo de estantería o cambiar su posición dentro de la misma. En el móvil, mantén pulsado y arrastra.",
```

`lang/en.json`:

```json
    "ui.decorations_heading": "Decoration",
    "ui.decorations_help": "Add objects to your library and drag them around the shelves.",
    "ui.decoration_add": "Add",
    "ui.decorations_remove_all": "Remove all",
    "ui.decorations_remove_all_confirm": "Remove all objects from your shelves?",
    "ui.decorations_placed": "Your objects",
    "ui.decorations_empty": "You haven't added any objects yet.",
    "ui.decoration_remove": "Remove object",
    "ui.decoration_color": "Colour",
    "ui.decoration_added": "“:name” added to “:shelf”.",
    "ui.decoration_moved": "“:name” moved to “:shelf”.",
    "ui.decoration_move_error": "The object could not be moved. Try again.",
    "ui.decoration_removed": "“:name” was removed from the library.",
    "ui.decorations_cleared": "All objects were removed.",
    "ui.decoration_cup": "Cup",
    "ui.decoration_roses": "Roses",
    "ui.decoration_plant": "Plant",
    "ui.decoration_candle": "Candle",
    "ui.decoration_hourglass": "Hourglass",
    "ui.decoration_crystal_ball": "Crystal ball",
    "ui.decoration_lamp": "Lamp",
    "ui.decoration_cat": "Cat",
    "ui.decoration_skull": "Skull",
    "ui.decoration_cage": "Cage",
    "ui.decoration_astrolabe": "Astrolabe",
    "ui.decoration_tea_set": "Tea set",
    "ui.decoration_music_box": "Music box",
    "ui.decoration_quill": "Quill and ink",
    "ui.decoration_photo_frame": "Photo frame",
    "ui.color_rose": "Rose",
    "ui.color_cream": "Cream",
    "ui.color_green": "Green",
    "ui.color_forest": "Forest",
    "ui.color_gold": "Gold",
    "ui.color_clay": "Terracotta",
```

`ui.drag_hint` en `en.json`:

```json
    "ui.drag_hint": "Drag a book or an object to move it between shelves or change its position on the same shelf. On mobile, press and hold, then drag.",
```

`lang/fr.json`:

```json
    "ui.decorations_heading": "Décoration",
    "ui.decorations_help": "Ajoutez des objets à votre bibliothèque et faites-les glisser sur les étagères.",
    "ui.decoration_add": "Ajouter",
    "ui.decorations_remove_all": "Tout retirer",
    "ui.decorations_remove_all_confirm": "Retirer tous les objets de vos étagères ?",
    "ui.decorations_placed": "Vos objets",
    "ui.decorations_empty": "Vous n'avez encore ajouté aucun objet.",
    "ui.decoration_remove": "Retirer l'objet",
    "ui.decoration_color": "Couleur",
    "ui.decoration_added": "« :name » ajouté à « :shelf ».",
    "ui.decoration_moved": "« :name » a été déplacé vers « :shelf ».",
    "ui.decoration_move_error": "Impossible de déplacer l'objet. Réessayez.",
    "ui.decoration_removed": "« :name » a été retiré de la bibliothèque.",
    "ui.decorations_cleared": "Tous les objets ont été retirés.",
    "ui.decoration_cup": "Tasse",
    "ui.decoration_roses": "Roses",
    "ui.decoration_plant": "Plante",
    "ui.decoration_candle": "Bougie",
    "ui.decoration_hourglass": "Sablier",
    "ui.decoration_crystal_ball": "Boule de cristal",
    "ui.decoration_lamp": "Lampe",
    "ui.decoration_cat": "Chat",
    "ui.decoration_skull": "Crâne",
    "ui.decoration_cage": "Cage",
    "ui.decoration_astrolabe": "Astrolabe",
    "ui.decoration_tea_set": "Service à thé",
    "ui.decoration_music_box": "Boîte à musique",
    "ui.decoration_quill": "Plume et encrier",
    "ui.decoration_photo_frame": "Cadre photo",
    "ui.color_rose": "Rose",
    "ui.color_cream": "Crème",
    "ui.color_green": "Vert",
    "ui.color_forest": "Forêt",
    "ui.color_gold": "Doré",
    "ui.color_clay": "Terracotta",
```

`ui.drag_hint` en `fr.json`:

```json
    "ui.drag_hint": "Faites glisser un livre ou un objet pour le déplacer d'une étagère à l'autre ou changer sa position sur la même étagère. Sur mobile, maintenez appuyé puis faites glisser.",
```

`lang/de.json`:

```json
    "ui.decorations_heading": "Dekoration",
    "ui.decorations_help": "Füge deiner Bibliothek Objekte hinzu und ziehe sie über die Regale.",
    "ui.decoration_add": "Hinzufügen",
    "ui.decorations_remove_all": "Alle entfernen",
    "ui.decorations_remove_all_confirm": "Alle Objekte aus deinen Regalen entfernen?",
    "ui.decorations_placed": "Deine Objekte",
    "ui.decorations_empty": "Du hast noch keine Objekte hinzugefügt.",
    "ui.decoration_remove": "Objekt entfernen",
    "ui.decoration_color": "Farbe",
    "ui.decoration_added": "„:name“ zu „:shelf“ hinzugefügt.",
    "ui.decoration_moved": "„:name“ wurde nach „:shelf“ verschoben.",
    "ui.decoration_move_error": "Das Objekt konnte nicht verschoben werden. Versuche es erneut.",
    "ui.decoration_removed": "„:name“ wurde aus der Bibliothek entfernt.",
    "ui.decorations_cleared": "Alle Objekte wurden entfernt.",
    "ui.decoration_cup": "Tasse",
    "ui.decoration_roses": "Rosen",
    "ui.decoration_plant": "Pflanze",
    "ui.decoration_candle": "Kerze",
    "ui.decoration_hourglass": "Sanduhr",
    "ui.decoration_crystal_ball": "Glaskugel",
    "ui.decoration_lamp": "Lampe",
    "ui.decoration_cat": "Katze",
    "ui.decoration_skull": "Schädel",
    "ui.decoration_cage": "Käfig",
    "ui.decoration_astrolabe": "Astrolabium",
    "ui.decoration_tea_set": "Teeservice",
    "ui.decoration_music_box": "Spieluhr",
    "ui.decoration_quill": "Feder und Tinte",
    "ui.decoration_photo_frame": "Bilderrahmen",
    "ui.color_rose": "Rosa",
    "ui.color_cream": "Creme",
    "ui.color_green": "Grün",
    "ui.color_forest": "Wald",
    "ui.color_gold": "Gold",
    "ui.color_clay": "Terrakotta",
```

`ui.drag_hint` en `de.json`:

```json
    "ui.drag_hint": "Ziehe ein Buch oder ein Objekt, um es zwischen Regalen zu verschieben oder seine Position im selben Regal zu ändern. Auf dem Handy gedrückt halten und ziehen.",
```

`lang/pt.json`:

```json
    "ui.decorations_heading": "Decoração",
    "ui.decorations_help": "Adicione objetos à sua biblioteca e arraste-os pelas estantes.",
    "ui.decoration_add": "Adicionar",
    "ui.decorations_remove_all": "Remover todas",
    "ui.decorations_remove_all_confirm": "Remover todos os objetos das suas estantes?",
    "ui.decorations_placed": "Seus objetos",
    "ui.decorations_empty": "Ainda não adicionou nenhum objeto.",
    "ui.decoration_remove": "Remover objeto",
    "ui.decoration_color": "Cor",
    "ui.decoration_added": "«:name» adicionado a «:shelf».",
    "ui.decoration_moved": "«:name» foi movido para «:shelf».",
    "ui.decoration_move_error": "Não foi possível mover o objeto. Tente novamente.",
    "ui.decoration_removed": "«:name» foi removido da biblioteca.",
    "ui.decorations_cleared": "Todos os objetos foram removidos.",
    "ui.decoration_cup": "Xícara",
    "ui.decoration_roses": "Rosas",
    "ui.decoration_plant": "Planta",
    "ui.decoration_candle": "Vela",
    "ui.decoration_hourglass": "Ampulheta",
    "ui.decoration_crystal_ball": "Bola de cristal",
    "ui.decoration_lamp": "Lâmpada",
    "ui.decoration_cat": "Gato",
    "ui.decoration_skull": "Caveira",
    "ui.decoration_cage": "Gaiola",
    "ui.decoration_astrolabe": "Astrolábio",
    "ui.decoration_tea_set": "Conjunto de chá",
    "ui.decoration_music_box": "Caixa de música",
    "ui.decoration_quill": "Pena e tinteiro",
    "ui.decoration_photo_frame": "Porta-retratos",
    "ui.color_rose": "Rosa",
    "ui.color_cream": "Creme",
    "ui.color_green": "Verde",
    "ui.color_forest": "Bosque",
    "ui.color_gold": "Dourado",
    "ui.color_clay": "Terracota",
```

`ui.drag_hint` en `pt.json`:

```json
    "ui.drag_hint": "Arraste um livro ou um objeto para movê-lo entre estantes ou mudar a sua posição na mesma estante. No telemóvel, mantenha premido e arraste.",
```

`lang/it.json`:

```json
    "ui.decorations_heading": "Decorazione",
    "ui.decorations_help": "Aggiungi oggetti alla tua biblioteca e trascinali sugli scaffali.",
    "ui.decoration_add": "Aggiungi",
    "ui.decorations_remove_all": "Rimuovi tutte",
    "ui.decorations_remove_all_confirm": "Rimuovere tutti gli oggetti dai tuoi scaffali?",
    "ui.decorations_placed": "I tuoi oggetti",
    "ui.decorations_empty": "Non hai ancora aggiunto nessun oggetto.",
    "ui.decoration_remove": "Rimuovi oggetto",
    "ui.decoration_color": "Colore",
    "ui.decoration_added": "«:name» aggiunto a «:shelf».",
    "ui.decoration_moved": "«:name» spostato in «:shelf».",
    "ui.decoration_move_error": "Impossibile spostare l'oggetto. Riprova.",
    "ui.decoration_removed": "«:name» è stato rimosso dalla biblioteca.",
    "ui.decorations_cleared": "Tutti gli oggetti sono stati rimossi.",
    "ui.decoration_cup": "Tazza",
    "ui.decoration_roses": "Rose",
    "ui.decoration_plant": "Pianta",
    "ui.decoration_candle": "Candela",
    "ui.decoration_hourglass": "Clessidra",
    "ui.decoration_crystal_ball": "Sfera di cristallo",
    "ui.decoration_lamp": "Lampada",
    "ui.decoration_cat": "Gatto",
    "ui.decoration_skull": "Teschio",
    "ui.decoration_cage": "Gabbia",
    "ui.decoration_astrolabe": "Astrolabio",
    "ui.decoration_tea_set": "Servizio da tè",
    "ui.decoration_music_box": "Carillon",
    "ui.decoration_quill": "Penna e calamaio",
    "ui.decoration_photo_frame": "Cornice",
    "ui.color_rose": "Rosa",
    "ui.color_cream": "Crema",
    "ui.color_green": "Verde",
    "ui.color_forest": "Bosco",
    "ui.color_gold": "Oro",
    "ui.color_clay": "Terracotta",
```

`ui.drag_hint` en `it.json`:

```json
    "ui.drag_hint": "Trascina un libro o un oggetto per spostarlo tra gli scaffali o cambiarne la posizione nello stesso scaffale. Sul telefono, tieni premuto e trascina.",
```

`lang/gl.json`:

```json
    "ui.decorations_heading": "Decoración",
    "ui.decorations_help": "Engade obxectos á túa biblioteca e arrástraos polas estanterías.",
    "ui.decoration_add": "Engadir",
    "ui.decorations_remove_all": "Quitar todas",
    "ui.decorations_remove_all_confirm": "Quitar todos os obxectos das túas estanterías?",
    "ui.decorations_placed": "Os teus obxectos",
    "ui.decorations_empty": "Aínda non engadiches ningún obxecto.",
    "ui.decoration_remove": "Quitar obxecto",
    "ui.decoration_color": "Cor",
    "ui.decoration_added": "«:name» engadiuse a «:shelf».",
    "ui.decoration_moved": "«:name» moveuse a «:shelf».",
    "ui.decoration_move_error": "Non se puido mover o obxecto. Inténtao de novo.",
    "ui.decoration_removed": "«:name» quitouse da biblioteca.",
    "ui.decorations_cleared": "Quitáronse todos os obxectos.",
    "ui.decoration_cup": "Cunca",
    "ui.decoration_roses": "Rosas",
    "ui.decoration_plant": "Planta",
    "ui.decoration_candle": "Vela",
    "ui.decoration_hourglass": "Reloxo de area",
    "ui.decoration_crystal_ball": "Bóla de cristal",
    "ui.decoration_lamp": "Lámpada",
    "ui.decoration_cat": "Gato",
    "ui.decoration_skull": "Caveira",
    "ui.decoration_cage": "Gaiola",
    "ui.decoration_astrolabe": "Astrolabio",
    "ui.decoration_tea_set": "Xogo de té",
    "ui.decoration_music_box": "Caixa de música",
    "ui.decoration_quill": "Pluma e tinteiro",
    "ui.decoration_photo_frame": "Marco de fotos",
    "ui.color_rose": "Rosa",
    "ui.color_cream": "Crema",
    "ui.color_green": "Verde",
    "ui.color_forest": "Bosque",
    "ui.color_gold": "Dourado",
    "ui.color_clay": "Terracota",
```

`ui.drag_hint` en `gl.json`:

```json
    "ui.drag_hint": "Arrastra un libro ou un obxecto para movelo de estante ou cambiar a súa posición na mesma estante. No móbil, mantén premido e arrastra.",
```

`lang/ca.json`:

```json
    "ui.decorations_heading": "Decoració",
    "ui.decorations_help": "Afegeix objectes a la teva biblioteca i arrossega'ls per les prestatgeries.",
    "ui.decoration_add": "Afegeix",
    "ui.decorations_remove_all": "Treu-les tots",
    "ui.decorations_remove_all_confirm": "Vols treure tots els objectes de les teves prestatgeries?",
    "ui.decorations_placed": "Els teus objectes",
    "ui.decorations_empty": "Encara no has afegit cap objecte.",
    "ui.decoration_remove": "Treu l'objecte",
    "ui.decoration_color": "Color",
    "ui.decoration_added": "«:name» afegit a «:shelf».",
    "ui.decoration_moved": "«:name» s'ha mogut a «:shelf».",
    "ui.decoration_move_error": "No s'ha pogut moure l'objecte. Torna-ho a provar.",
    "ui.decoration_removed": "«:name» s'ha tret de la biblioteca.",
    "ui.decorations_cleared": "S'han tret tots els objectes.",
    "ui.decoration_cup": "Tassa",
    "ui.decoration_roses": "Roses",
    "ui.decoration_plant": "Planta",
    "ui.decoration_candle": "Espelma",
    "ui.decoration_hourglass": "Rellotge de sorra",
    "ui.decoration_crystal_ball": "Bola de vidre",
    "ui.decoration_lamp": "Llum",
    "ui.decoration_cat": "Gat",
    "ui.decoration_skull": "Calavera",
    "ui.decoration_cage": "Gàbia",
    "ui.decoration_astrolabe": "Astrolabi",
    "ui.decoration_tea_set": "Joc de te",
    "ui.decoration_music_box": "Caixa de música",
    "ui.decoration_quill": "Ploma i tinter",
    "ui.decoration_photo_frame": "Marc de fotos",
    "ui.color_rose": "Rosa",
    "ui.color_cream": "Crema",
    "ui.color_green": "Verd",
    "ui.color_forest": "Bosc",
    "ui.color_gold": "Daurat",
    "ui.color_clay": "Terracota",
```

`ui.drag_hint` en `ca.json`:

```json
    "ui.drag_hint": "Arrossega un llibre o un objecte per moure'l d'estanteria o canviar-ne la posició dins de la mateixa. Al mòbil, mantén premut i arrossega.",
```

- [ ] **Step 2: Comprobar que los 8 archivos son JSON válido y tienen paridad**

Run: `cmd.exe /C 'php artisan test --filter=CatalogTest'`
Expected: 3 passed (incluye «tiene todas las claves en los idiomas revisados»).

Si falla por JSON inválido, ejecuta:

Run: `cmd.exe /C 'php -r "foreach (glob(\"lang/*.json\") as $f) { json_decode(file_get_contents($f), true); echo $f, \": \", json_last_error_msg(), PHP_EOL; }"'`
Expected: `No error` en los ocho.

---

## Task 3: Crear un objeto desde Ajustes (POST)

**Files:**
- Create: `app/Policies/DecorationPolicy.php`
- Create: `app/Http/Controllers/DecorationController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/DecorationTest.php`

- [ ] **Step 1: Escribir las pruebas que fallan**

Añadir al final de `tests/Feature/DecorationTest.php`:

```php
it('añade un objeto al final de la primera estantería', function () {
    $first = Shelf::factory()->for($this->user)->create(['name' => 'Primera', 'position' => 0]);
    Shelf::factory()->for($this->user)->create(['name' => 'Segunda', 'position' => 1]);
    $last = Book::factory()->for($this->user)->for($first)->create(['title' => 'Zeta', 'position' => 1]);
    Book::factory()->for($this->user)->for($first)->create(['title' => 'Alfa', 'position' => 0]);

    $this->post(route('decorations.store'), ['kind' => 'cup', 'color' => 'rose'])
        ->assertRedirect()
        ->assertSessionHas('success', __('ui.decoration_added', [
            'name' => __('ui.decoration_cup'),
            'shelf' => 'Primera',
        ]));

    $decoration = $this->user->decorations()->sole();

    expect($decoration->shelf_id)->toBe($first->id)
        ->and($decoration->after_book_id)->toBe($last->id)
        ->and($decoration->sort)->toBe(0)
        ->and($decoration->kind)->toBe('cup')
        ->and($decoration->color)->toBe('rose');
});

it('apila varios objetos en el mismo hueco', function () {
    $shelf = Shelf::factory()->for($this->user)->create(['position' => 0]);
    $book = Book::factory()->for($this->user)->for($shelf)->create(['position' => 0]);

    $this->post(route('decorations.store'), ['kind' => 'cup', 'color' => 'rose'])->assertRedirect();
    $this->post(route('decorations.store'), ['kind' => 'roses', 'color' => 'green'])->assertRedirect();

    expect($shelf->decorations()->pluck('sort')->all())->toBe([0, 1]);
});

it('rechaza un objeto o un color desconocido', function () {
    Shelf::factory()->for($this->user)->create();

    $this->post(route('decorations.store'), ['kind' => 'dragon', 'color' => 'rose'])
        ->assertSessionHasErrors('kind');

    $this->post(route('decorations.store'), ['kind' => 'cup', 'color' => 'neon'])
        ->assertSessionHasErrors('color');
});
```

- [ ] **Step 2: Ejecutar y ver el fallo**

Run: `cmd.exe /C 'php artisan test --filter=DecorationTest'`
Expected: FAIL — `Route [decorations.store] not defined`.

- [ ] **Step 3: Crear la policy**

`app/Policies/DecorationPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\Decoration;
use App\Models\User;

class DecorationPolicy
{
    public function update(User $user, Decoration $decoration): bool
    {
        return $user->id === $decoration->user_id;
    }

    public function delete(User $user, Decoration $decoration): bool
    {
        return $user->id === $decoration->user_id;
    }
}
```

- [ ] **Step 4: Crear el controlador con `store`**

`app/Http/Controllers/DecorationController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Decoration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DecorationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(Decoration::KINDS)],
            'color' => ['required', Rule::in(Decoration::COLORS)],
        ]);

        $shelf = $request->user()->shelves()
            ->orderBy('position')
            ->orderBy('id')
            ->firstOrFail();

        $lastBook = $shelf->books()
            ->reorder()
            ->orderByDesc('position')
            ->orderByDesc('title')
            ->first();

        $request->user()->decorations()->create([
            'shelf_id' => $shelf->id,
            'kind' => $data['kind'],
            'color' => $data['color'],
            'after_book_id' => $lastBook?->id,
            'sort' => $shelf->decorations()->where('after_book_id', $lastBook?->id)->count(),
        ]);

        return back()->with('success', __('ui.decoration_added', [
            'name' => __('ui.decoration_'.$data['kind']),
            'shelf' => $shelf->name,
        ]));
    }
}
```

- [ ] **Step 5: Registrar la ruta**

En `routes/web.php`, añade `use App\Http\Controllers\DecorationController;` con los demás `use`, y dentro del grupo `Route::middleware(['auth', 'nickname'])`, después de las rutas de `/books`:

```php
    Route::post('/decorations', [DecorationController::class, 'store'])->name('decorations.store');
```

- [ ] **Step 6: Ejecutar y ver el verde**

Run: `cmd.exe /C 'php artisan test --filter=DecorationTest'`
Expected: 7 passed.

---

## Task 4: Mover y reanclar (PATCH)

**Files:**
- Modify: `app/Http/Controllers/DecorationController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/DecorationTest.php`

- [ ] **Step 1: Escribir las pruebas que fallan**

Añadir al final de `tests/Feature/DecorationTest.php`:

```php
it('mueve un objeto a otra estantería y lo ancla a un libro', function () {
    $from = Shelf::factory()->for($this->user)->create(['position' => 0]);
    $to = Shelf::factory()->for($this->user)->create(['name' => 'Destino', 'position' => 1]);
    $book = Book::factory()->for($this->user)->for($to)->create();
    $decoration = Decoration::factory()->for($this->user)->for($from)->create([
        'kind' => 'candle',
        'sort' => 0,
    ]);

    $this->patch(route('decorations.update', $decoration), [
        'shelf_id' => $to->id,
        'after_book_id' => $book->id,
        'sort' => 0,
    ])->assertRedirect()
        ->assertSessionHas('success', __('ui.decoration_moved', [
            'name' => __('ui.decoration_candle'),
            'shelf' => 'Destino',
        ]));

    expect($decoration->fresh()->shelf_id)->toBe($to->id)
        ->and($decoration->fresh()->after_book_id)->toBe($book->id)
        ->and($decoration->fresh()->sort)->toBe(0);
});

it('reordena los objetos del mismo hueco', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $book = Book::factory()->for($this->user)->for($shelf)->create();
    $a = Decoration::factory()->for($this->user)->for($shelf)->create(['after_book_id' => $book->id, 'sort' => 0]);
    $b = Decoration::factory()->for($this->user)->for($shelf)->create(['after_book_id' => $book->id, 'sort' => 1]);
    $c = Decoration::factory()->for($this->user)->for($shelf)->create(['after_book_id' => $book->id, 'sort' => 2]);

    $this->patch(route('decorations.update', $c), [
        'shelf_id' => $shelf->id,
        'after_book_id' => $book->id,
        'sort' => 0,
    ])->assertRedirect();

    expect($shelf->decorations()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('no permite mover un objeto ajeno', function () {
    $decoration = Decoration::factory()->for(User::factory())->create();
    $shelf = Shelf::factory()->for($this->user)->create();

    $this->patch(route('decorations.update', $decoration), [
        'shelf_id' => $shelf->id,
        'after_book_id' => null,
        'sort' => 0,
    ])->assertForbidden();
});

it('rechaza un ancla que no está en la estantería destino', function () {
    $shelf = Shelf::factory()->for($this->user)->create();
    $other = Shelf::factory()->for($this->user)->create();
    $book = Book::factory()->for($this->user)->for($other)->create();
    $decoration = Decoration::factory()->for($this->user)->for($shelf)->create();

    $this->patch(route('decorations.update', $decoration), [
        'shelf_id' => $shelf->id,
        'after_book_id' => $book->id,
        'sort' => 0,
    ])->assertStatus(422);
});

it('rechaza una estantería ajena', function () {
    $decoration = Decoration::factory()->for($this->user)->create();
    $shelf = Shelf::factory()->for(User::factory())->create();

    $this->patch(route('decorations.update', $decoration), [
        'shelf_id' => $shelf->id,
        'after_book_id' => null,
        'sort' => 0,
    ])->assertNotFound();
});
```

- [ ] **Step 2: Ejecutar y ver el fallo**

Run: `cmd.exe /C 'php artisan test --filter=DecorationTest'`
Expected: FAIL — `Route [decorations.update] not defined`.

- [ ] **Step 3: Implementar `update` y el helper de colocación**

Añade a `DecorationController` (importa `App\Models\Book` si lo necesitas y `Illuminate\Support\Facades\DB`):

```php
    public function update(Request $request, Decoration $decoration): RedirectResponse
    {
        $this->authorize('update', $decoration);

        $data = $request->validate([
            'shelf_id' => ['required', 'integer'],
            'after_book_id' => ['nullable', 'integer'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);

        $shelf = $request->user()->shelves()->findOrFail($data['shelf_id']);
        $afterBookId = $data['after_book_id'] ?? null;

        if ($afterBookId !== null) {
            abort_unless(
                $shelf->books()->whereKey($afterBookId)->exists(),
                422,
                'El libro ancla no pertenece a esa estantería.'
            );
        }

        DB::transaction(function () use ($decoration, $shelf, $afterBookId, $data) {
            $oldShelfId = (int) $decoration->shelf_id;
            $oldAnchor = $decoration->after_book_id;

            $this->place($decoration, (int) $shelf->id, $afterBookId, (int) ($data['sort'] ?? 0));

            $this->resequence($oldShelfId, $oldAnchor);
        });

        return back()->with('success', __('ui.decoration_moved', [
            'name' => __('ui.decoration_'.$decoration->kind),
            'shelf' => $shelf->name,
        ]));
    }

    private function place(Decoration $decoration, int $shelfId, ?int $afterBookId, int $index): void
    {
        $siblings = Decoration::query()
            ->where('shelf_id', $shelfId)
            ->where('after_book_id', $afterBookId)
            ->whereKeyNot($decoration->id)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->all();

        $index = max(0, min($index, count($siblings)));

        array_splice($siblings, $index, 0, [$decoration]);

        $decoration->update([
            'shelf_id' => $shelfId,
            'after_book_id' => $afterBookId,
        ]);

        foreach ($siblings as $position => $item) {
            $item->update(['sort' => $position]);
        }
    }

    private function resequence(int $shelfId, ?int $afterBookId): void
    {
        Decoration::query()
            ->where('shelf_id', $shelfId)
            ->where('after_book_id', $afterBookId)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->each(fn (Decoration $item, int $position) => $item->update(['sort' => $position]));
    }
```

- [ ] **Step 4: Registrar la ruta**

En `routes/web.php`, junto a la anterior:

```php
    Route::patch('/decorations/{decoration}', [DecorationController::class, 'update'])->name('decorations.update');
```

- [ ] **Step 5: Ejecutar y ver el verde**

Run: `cmd.exe /C 'php artisan test --filter=DecorationTest'`
Expected: 12 passed.

---

## Task 5: Quitar uno y quitar todos (DELETE)

**Files:**
- Modify: `app/Http/Controllers/DecorationController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/DecorationTest.php`

- [ ] **Step 1: Escribir las pruebas que fallan**

Añadir al final de `tests/Feature/DecorationTest.php`:

```php
it('quita un objeto propio', function () {
    $decoration = Decoration::factory()->for($this->user)->create(['kind' => 'lamp']);

    $this->delete(route('decorations.destroy', $decoration))
        ->assertRedirect()
        ->assertSessionHas('success', __('ui.decoration_removed', [
            'name' => __('ui.decoration_lamp'),
        ]));

    expect(Decoration::find($decoration->id))->toBeNull();
});

it('no permite quitar un objeto ajeno', function () {
    $decoration = Decoration::factory()->for(User::factory())->create();

    $this->delete(route('decorations.destroy', $decoration))->assertForbidden();
});

it('quita todos los objetos del usuario', function () {
    $mine = Decoration::factory()->for($this->user)->create();
    $theirs = Decoration::factory()->for(User::factory())->create();

    $this->delete(route('decorations.clear'))
        ->assertRedirect()
        ->assertSessionHas('success', __('ui.decorations_cleared'));

    expect(Decoration::find($mine->id))->toBeNull()
        ->and(Decoration::find($theirs->id))->not->toBeNull();
});
```

- [ ] **Step 2: Ejecutar y ver el fallo**

Run: `cmd.exe /C 'php artisan test --filter=DecorationTest'`
Expected: FAIL — `Route [decorations.destroy] not defined`.

- [ ] **Step 3: Implementar las acciones**

Añade a `DecorationController`:

```php
    public function destroy(Decoration $decoration): RedirectResponse
    {
        $this->authorize('delete', $decoration);

        $name = __('ui.decoration_'.$decoration->kind);

        $decoration->delete();

        return back()->with('success', __('ui.decoration_removed', ['name' => $name]));
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->user()->decorations()->delete();

        return back()->with('success', __('ui.decorations_cleared'));
    }
```

- [ ] **Step 4: Registrar las rutas**

En `routes/web.php`, después de la de crear:

```php
    Route::delete('/decorations', [DecorationController::class, 'clear'])->name('decorations.clear');
    Route::delete('/decorations/{decoration}', [DecorationController::class, 'destroy'])->name('decorations.destroy');
```

- [ ] **Step 5: Ejecutar y ver el verde**

Run: `cmd.exe /C 'php artisan test --filter=DecorationTest'`
Expected: 15 passed.

---

## Task 6: Enviar las decoraciones a la biblioteca y a Ajustes

**Files:**
- Modify: `app/Http/Controllers/LibraryController.php:14-25`
- Modify: `app/Http/Controllers/SettingsController.php:13-21`
- Test: `tests/Feature/DecorationTest.php`

- [ ] **Step 1: Escribir las pruebas que fallan**

Añadir al final de `tests/Feature/DecorationTest.php`:

```php
it('entrega las decoraciones en la biblioteca', function () {
    $shelf = Shelf::factory()->for($this->user)->create(['position' => 0]);
    Decoration::factory()->for($this->user)->for($shelf)->create(['kind' => 'cup']);

    $this->get(route('library.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('shelves.0.decorations', 1)
            ->where('shelves.0.decorations.0.kind', 'cup'));
});

it('entrega las decoraciones con su estantería en ajustes', function () {
    $shelf = Shelf::factory()->for($this->user)->create(['name' => 'Mi estantería']);
    Decoration::factory()->for($this->user)->for($shelf)->create(['kind' => 'plant']);

    $this->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('decorations', 1)
            ->where('decorations.0.shelf.name', 'Mi estantería'));
});
```

- [ ] **Step 2: Ejecutar y ver el fallo**

Run: `cmd.exe /C 'php artisan test --filter=DecorationTest'`
Expected: FAIL — `shelves.0.decorations` no existe.

- [ ] **Step 3: Cargar las relaciones**

En `LibraryController@index`, cambia `->with('books')` por:

```php
            ->with(['books', 'decorations'])
```

En `SettingsController@index`, añade después de `'shelves' => ...`:

```php
            'decorations' => $request->user()->decorations()->with('shelf:id,name')->get(),
```

- [ ] **Step 4: Ejecutar y ver el verde**

Run: `cmd.exe /C 'php artisan test --filter=DecorationTest'`
Expected: 17 passed.

---

## Task 7: Helpers de orden y ancla (JS)

**Files:**
- Create: `resources/js/shelfItems.js`
- Test: `resources/js/shelfItems.test.js`

- [ ] **Step 1: Escribir la prueba que falla**

`resources/js/shelfItems.test.js`:

```js
import { describe, expect, it } from 'vitest';
import { mergeShelfItems, resolveDecorationAnchor } from './shelfItems';

const books = [
    { id: 1, title: 'Alfa' },
    { id: 2, title: 'Beta' },
];

function ids(items) {
    return items.map((item) => (item.type === 'book' ? item.book.id : item.decoration.id));
}

describe('mergeShelfItems', () => {
    it('coloca los objetos sin ancla antes del primer libro', () => {
        const items = mergeShelfItems(books, [
            { id: 10, after_book_id: null, sort: 0 },
        ]);

        expect(ids(items)).toEqual([10, 1, 2]);
    });

    it('coloca los objetos anclados después de su libro', () => {
        const items = mergeShelfItems(books, [
            { id: 10, after_book_id: 1, sort: 0 },
            { id: 11, after_book_id: 1, sort: 1 },
            { id: 12, after_book_id: 2, sort: 0 },
        ]);

        expect(ids(items)).toEqual([1, 10, 11, 2, 12]);
    });

    it('manda al final los objetos cuyo ancla no está en la estantería', () => {
        const items = mergeShelfItems(books, [
            { id: 10, after_book_id: 99, sort: 0 },
            { id: 11, after_book_id: null, sort: 0 },
        ]);

        expect(ids(items)).toEqual([11, 1, 2, 10]);
    });

    it('respeta sort y desempata por id', () => {
        const items = mergeShelfItems(books, [
            { id: 12, after_book_id: null, sort: 1 },
            { id: 11, after_book_id: null, sort: 1 },
            { id: 10, after_book_id: null, sort: 0 },
        ]);

        expect(ids(items)).toEqual([10, 11, 12, 1, 2]);
    });
});

describe('resolveDecorationAnchor', () => {
    const decorations = [
        { id: 10, after_book_id: 1, sort: 0 },
        { id: 11, after_book_id: 1, sort: 1 },
    ];

    it('ancla al final cuando no hay elemento bajo el puntero', () => {
        expect(resolveDecorationAnchor(books, decorations, null, 'after')).toEqual({
            afterBookId: 2,
            sort: 0,
        });
    });

    it('ancla antes del libro cuando se suelta en su mitad izquierda', () => {
        expect(resolveDecorationAnchor(books, decorations, { type: 'book', id: 2 }, 'before')).toEqual({
            afterBookId: 1,
            sort: 2,
        });
    });

    it('ancla antes del primer libro cuando se suelta sobre el primero por la izquierda', () => {
        expect(resolveDecorationAnchor(books, decorations, { type: 'book', id: 1 }, 'before')).toEqual({
            afterBookId: null,
            sort: 0,
        });
    });

    it('coloca después del objeto señalado', () => {
        expect(resolveDecorationAnchor(books, decorations, { type: 'decoration', id: 10 }, 'after')).toEqual({
            afterBookId: 1,
            sort: 1,
        });
    });

    it('excluye el objeto que se está moviendo', () => {
        expect(resolveDecorationAnchor(books, decorations, { type: 'decoration', id: 11 }, 'after', 11)).toEqual({
            afterBookId: 1,
            sort: 1,
        });
    });
});
```

- [ ] **Step 2: Ejecutar y ver el fallo**

Run: `cmd.exe /C 'npx vitest run resources/js/shelfItems.test.js --reporter=dot'`
Expected: FAIL — no se puede resolver `./shelfItems`.

- [ ] **Step 3: Implementar los helpers**

`resources/js/shelfItems.js`:

```js
function sortGroup(group) {
    return group.sort((a, b) => a.sort - b.sort || a.id - b.id);
}

export function mergeShelfItems(books = [], decorations = []) {
    const anchors = new Map();

    for (const decoration of decorations) {
        const key = decoration.after_book_id ?? 0;

        if (!anchors.has(key)) {
            anchors.set(key, []);
        }

        anchors.get(key).push(decoration);
    }

    for (const group of anchors.values()) {
        sortGroup(group);
    }

    const bookIds = new Set(books.map((book) => book.id));
    const items = [];

    for (const decoration of anchors.get(0) ?? []) {
        items.push({ type: 'decoration', key: `d${decoration.id}`, decoration });
    }

    for (const book of books) {
        items.push({ type: 'book', key: `b${book.id}`, book });

        for (const decoration of anchors.get(book.id) ?? []) {
            items.push({ type: 'decoration', key: `d${decoration.id}`, decoration });
        }
    }

    const orphans = [];

    for (const [anchorId, group] of anchors) {
        if (anchorId === 0 || bookIds.has(anchorId)) {
            continue;
        }

        orphans.push(...group);
    }

    for (const decoration of sortGroup(orphans)) {
        items.push({ type: 'decoration', key: `d${decoration.id}`, decoration });
    }

    return items;
}

export function resolveDecorationAnchor(books = [], decorations = [], hit = null, place = 'after', movingId = null) {
    const pool = decorations.filter((decoration) => decoration.id !== movingId);

    const countAt = (afterBookId) =>
        pool.filter((decoration) => (decoration.after_book_id ?? null) === afterBookId).length;

    const atEnd = () => {
        const last = books[books.length - 1] ?? null;
        const afterBookId = last?.id ?? null;

        return { afterBookId, sort: countAt(afterBookId) };
    };

    if (!hit) {
        return atEnd();
    }

    if (hit.type === 'book') {
        const index = books.findIndex((book) => book.id === hit.id);

        if (index === -1) {
            return atEnd();
        }

        const anchor = place === 'after' ? books[index] : books[index - 1] ?? null;
        const afterBookId = anchor?.id ?? null;

        return { afterBookId, sort: countAt(afterBookId) };
    }

    const target = pool.find((decoration) => decoration.id === hit.id);

    if (!target) {
        return atEnd();
    }

    const afterBookId = target.after_book_id ?? null;
    const group = sortGroup(pool.filter((decoration) => (decoration.after_book_id ?? null) === afterBookId));
    const index = group.findIndex((decoration) => decoration.id === target.id);

    return {
        afterBookId,
        sort: Math.max(0, index + (place === 'after' ? 1 : 0)),
    };
}
```

- [ ] **Step 4: Ejecutar y ver el verde**

Run: `cmd.exe /C 'npx vitest run resources/js/shelfItems.test.js --reporter=dot'`
Expected: 9 passed.

---

## Task 8: Dibujos de los objetos (`DecorationArt`)

**Files:**
- Create: `resources/js/Components/DecorationArt.vue`
- Modify: `resources/css/textures.css` (clase de arrastre)
- Test: `resources/js/Components/__tests__/DecorationArt.test.js`

- [ ] **Step 1: Escribir la prueba que falla**

`resources/js/Components/__tests__/DecorationArt.test.js`:

```js
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import DecorationArt from '../DecorationArt.vue';

const KINDS = [
    'cup', 'roses', 'plant', 'candle', 'hourglass', 'crystal_ball', 'lamp',
    'cat', 'skull', 'cage', 'astrolabe', 'tea_set', 'music_box', 'quill',
    'photo_frame',
];

describe('DecorationArt', () => {
    it('dibuja cada tipo de objeto', () => {
        for (const kind of KINDS) {
            const wrapper = mount(DecorationArt, { props: { kind, color: 'rose' } });

            expect(wrapper.find('svg').exists()).toBe(true);
        }
    });

    it('pinta el color elegido con los tokens existentes', () => {
        const wrapper = mount(DecorationArt, { props: { kind: 'cup', color: 'gold' } });

        expect(wrapper.find('svg').attributes('style')).toContain('--color-gold');
    });

    it('usa un color de reserva si el color no existe', () => {
        const wrapper = mount(DecorationArt, { props: { kind: 'cup', color: 'neon' } });

        expect(wrapper.find('svg').attributes('style')).toContain('--color-crema');
    });
});
```

- [ ] **Step 2: Ejecutar y ver el fallo**

Run: `cmd.exe /C 'npx vitest run resources/js/Components/__tests__/DecorationArt.test.js --reporter=dot'`
Expected: FAIL — no existe el componente.

- [ ] **Step 3: Implementar el componente**

`resources/js/Components/DecorationArt.vue`:

```vue
<script setup>
import { computed } from 'vue';

const props = defineProps({
    kind: { type: String, required: true },
    color: { type: String, default: 'cream' },
});

const TOKENS = {
    rose: '--color-orquidea',
    cream: '--color-crema',
    green: '--color-salvia',
    forest: '--color-bosque',
    gold: '--color-gold',
    clay: '--color-terracota',
};

const accent = computed(() => `var(${TOKENS[props.color] ?? TOKENS.cream})`);
const ink = 'var(--color-tinta)';
const paper = 'var(--color-pergamino)';
const wood = 'var(--color-madera)';
</script>

<template>
    <svg
        viewBox="0 0 64 80"
        fill="none"
        aria-hidden="true"
        :style="{ color: accent }"
        class="overflow-visible"
    >
        <g v-if="kind === 'cup'">
            <ellipse cx="30" cy="72" rx="18" ry="4" :fill="ink" opacity="0.25" />
            <path d="M16 40h28l-3 22a6 6 0 0 1-6 5H25a6 6 0 0 1-6-5z" fill="currentColor" />
            <path d="M16 40h28l-1 7H17z" :fill="paper" opacity="0.55" />
            <path d="M44 46a9 9 0 1 1 0 16" :stroke="ink" stroke-width="3" opacity="0.6" />
            <ellipse cx="30" cy="40" rx="14" ry="4" :fill="paper" opacity="0.8" />
        </g>

        <g v-else-if="kind === 'roses'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <path d="M22 56h20l-3 16H25z" fill="currentColor" />
            <path d="M32 56V34M32 44l-8-6M32 48l8-7" :stroke="wood" stroke-width="2.5" />
            <circle cx="24" cy="26" r="8" fill="currentColor" />
            <circle cx="40" cy="24" r="9" fill="currentColor" />
            <circle cx="32" cy="16" r="7" fill="currentColor" />
            <circle cx="24" cy="26" r="3" :fill="ink" opacity="0.35" />
            <circle cx="40" cy="24" r="3.5" :fill="ink" opacity="0.35" />
            <circle cx="32" cy="16" r="2.5" :fill="ink" opacity="0.35" />
        </g>

        <g v-else-if="kind === 'plant'">
            <ellipse cx="32" cy="74" rx="15" ry="4" :fill="ink" opacity="0.25" />
            <path d="M21 54h22l-3 18H24z" fill="currentColor" />
            <path d="M21 54h22l-1 5H22z" :fill="paper" opacity="0.5" />
            <path d="M32 54V30" :stroke="wood" stroke-width="2.5" />
            <path d="M32 44c-10 0-14-8-14-14 9 0 14 5 14 14z" fill="currentColor" opacity="0.85" />
            <path d="M32 38c10 0 14-8 14-14-9 0-14 5-14 14z" fill="currentColor" />
            <path d="M32 30c-6-4-7-12-4-17 6 3 8 10 4 17z" fill="currentColor" opacity="0.7" />
        </g>

        <g v-else-if="kind === 'candle'">
            <ellipse cx="32" cy="74" rx="14" ry="4" :fill="ink" opacity="0.25" />
            <ellipse cx="32" cy="70" rx="13" ry="4" fill="currentColor" opacity="0.8" />
            <rect x="24" y="34" width="16" height="34" rx="3" fill="currentColor" />
            <rect x="24" y="34" width="6" height="34" rx="3" :fill="paper" opacity="0.35" />
            <path d="M32 34v-5" :stroke="ink" stroke-width="2" />
            <path d="M32 12c6 6 7 10 4 14-2 3-7 3-9 0-2-4 0-8 5-14z" fill="var(--color-gold)" />
            <path d="M32 18c3 3 3 6 1 8-1 1-3 1-4-1-1-2 0-5 3-7z" :fill="paper" opacity="0.7" />
        </g>

        <g v-else-if="kind === 'hourglass'">
            <ellipse cx="32" cy="74" rx="14" ry="4" :fill="ink" opacity="0.25" />
            <rect x="18" y="12" width="28" height="4" rx="2" fill="currentColor" />
            <rect x="18" y="66" width="28" height="4" rx="2" fill="currentColor" />
            <path d="M22 16h20l-10 22z" fill="currentColor" opacity="0.9" />
            <path d="M22 66h20L32 44z" fill="currentColor" opacity="0.55" />
            <path d="M22 16h20M22 66h20" :stroke="ink" stroke-width="1.5" opacity="0.5" />
            <path d="M32 44v16" :stroke="var(--color-gold)" stroke-width="2" />
            <ellipse cx="32" cy="64" rx="6" ry="2" fill="var(--color-gold)" />
        </g>

        <g v-else-if="kind === 'crystal_ball'">
            <ellipse cx="32" cy="72" rx="18" ry="5" :fill="ink" opacity="0.25" />
            <path d="M20 64h24l4 8H16z" fill="currentColor" />
            <circle cx="32" cy="40" r="24" fill="currentColor" opacity="0.9" />
            <circle cx="32" cy="40" r="24" :stroke="ink" stroke-width="1.5" opacity="0.35" />
            <path d="M22 32c4-6 12-9 18-6" :stroke="paper" stroke-width="3" stroke-linecap="round" opacity="0.7" />
            <path d="M44 46l3 3-3 3-3-3z" fill="var(--color-gold)" />
        </g>

        <g v-else-if="kind === 'lamp'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <path d="M18 30h28l-6 18H24z" fill="currentColor" />
            <path d="M18 30h28l-1 4H19z" :fill="paper" opacity="0.45" />
            <rect x="30" y="48" width="4" height="18" :fill="ink" opacity="0.55" />
            <ellipse cx="32" cy="68" rx="12" ry="4" fill="currentColor" />
            <path d="M22 52h20" :stroke="var(--color-gold)" stroke-width="2" opacity="0.7" />
        </g>

        <g v-else-if="kind === 'cat'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <path d="M20 68c0-12 5-20 12-20s12 8 12 20z" fill="currentColor" />
            <circle cx="32" cy="34" r="13" fill="currentColor" />
            <path d="M21 25l-2-12 11 6zM43 25l2-12-11 6z" fill="currentColor" />
            <circle cx="27" cy="33" r="2" :fill="ink" />
            <circle cx="37" cy="33" r="2" :fill="ink" />
            <path d="M32 38l-2 2h4z" :fill="ink" opacity="0.7" />
            <path d="M28 42c1 2 7 2 8 0" :stroke="ink" stroke-width="1.5" opacity="0.6" />
            <path d="M44 62c6-2 8-8 6-12" :stroke="currentColor" stroke-width="4" stroke-linecap="round" />
        </g>

        <g v-else-if="kind === 'skull'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <path d="M32 10c-12 0-20 9-20 22 0 9 5 15 11 18v10h18V50c6-3 11-9 11-18 0-13-8-22-20-22z" fill="currentColor" />
            <circle cx="24" cy="34" r="5" :fill="ink" />
            <circle cx="40" cy="34" r="5" :fill="ink" />
            <path d="M32 42l-3 6h6z" :fill="ink" opacity="0.7" />
            <path d="M26 56v6M32 56v6M38 56v6" :stroke="ink" stroke-width="2" opacity="0.5" />
        </g>

        <g v-else-if="kind === 'cage'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <path d="M14 64h36" :stroke="currentColor" stroke-width="4" />
            <path d="M18 64V32a14 14 0 0 1 28 0v32" :stroke="currentColor" stroke-width="3" />
            <path d="M24 64V32M32 64V22M40 64V32" :stroke="currentColor" stroke-width="2" opacity="0.8" />
            <path d="M32 12v6" :stroke="currentColor" stroke-width="3" />
            <circle cx="32" cy="10" r="3" :fill="var(--color-gold)" />
        </g>

        <g v-else-if="kind === 'astrolabe'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <circle cx="32" cy="42" r="28" :stroke="currentColor" stroke-width="4" />
            <circle cx="32" cy="42" r="20" :stroke="currentColor" stroke-width="2" opacity="0.8" />
            <circle cx="32" cy="42" r="11" :stroke="var(--color-gold)" stroke-width="2" />
            <path d="M4 42h56M32 14v56M12 22l40 40M52 22L12 62" :stroke="currentColor" stroke-width="1.5" opacity="0.55" />
            <circle cx="32" cy="42" r="3" fill="var(--color-gold)" />
            <path d="M32 70v6" :stroke="currentColor" stroke-width="3" />
        </g>

        <g v-else-if="kind === 'tea_set'">
            <ellipse cx="32" cy="74" rx="20" ry="4" :fill="ink" opacity="0.25" />
            <path d="M12 52h30v8a10 10 0 0 1-10 10H22a10 10 0 0 1-10-10z" fill="currentColor" />
            <path d="M12 52h30v4H12z" :fill="paper" opacity="0.5" />
            <path d="M42 56h8a6 6 0 0 1 0 12h-8" :stroke="currentColor" stroke-width="4" />
            <path d="M27 52c-2-6 2-10 0-16" :stroke="currentColor" stroke-width="2" opacity="0.6" />
            <path d="M14 66h26l-4 6H18z" fill="currentColor" opacity="0.7" />
        </g>

        <g v-else-if="kind === 'music_box'">
            <ellipse cx="32" cy="74" rx="18" ry="4" :fill="ink" opacity="0.25" />
            <rect x="12" y="46" width="40" height="24" rx="3" fill="currentColor" />
            <rect x="12" y="46" width="40" height="6" rx="3" :fill="paper" opacity="0.5" />
            <path d="M14 42h36l-4-14H18z" fill="currentColor" opacity="0.85" />
            <circle cx="46" cy="58" r="3" :fill="var(--color-gold)" />
            <path d="M50 58h6" :stroke="var(--color-gold)" stroke-width="2" />
            <path d="M22 58h10M22 63h14" :stroke="ink" stroke-width="1.5" opacity="0.45" />
        </g>

        <g v-else-if="kind === 'quill'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <path d="M20 70h24l-3 4H23z" fill="currentColor" />
            <path d="M32 70V54" :stroke="currentColor" stroke-width="6" />
            <path d="M34 56c14-6 20-18 18-30-12 2-22 12-24 24z" fill="currentColor" opacity="0.85" />
            <path d="M34 56c6-8 12-16 16-26" :stroke="ink" stroke-width="1.5" opacity="0.5" />
            <path d="M26 52l-4-8 8 2z" fill="var(--color-gold)" />
        </g>

        <g v-else-if="kind === 'photo_frame'">
            <ellipse cx="32" cy="74" rx="16" ry="4" :fill="ink" opacity="0.25" />
            <rect x="14" y="14" width="36" height="46" rx="3" fill="currentColor" />
            <rect x="19" y="19" width="26" height="36" rx="2" :fill="paper" />
            <path d="M21 50l8-11 6 7 5-6 4 10z" :fill="wood" opacity="0.8" />
            <circle cx="38" cy="27" r="3" fill="var(--color-gold)" />
            <path d="M32 60v10" :stroke="currentColor" stroke-width="3" />
            <path d="M24 70h16" :stroke="currentColor" stroke-width="3" />
        </g>
    </svg>
</template>
```

- [ ] **Step 4: Añadir la clase de arrastre**

En `resources/css/textures.css`, después de `.book-drag-source` (línea ~285):

```css
.decoration-drag-source {
  -webkit-touch-callout: none;
  -webkit-user-drag: none;
  user-select: none;
  transition: opacity 140ms ease-out;
}

body.book-dragging .decoration-drag-source {
  cursor: grabbing;
}
```

- [ ] **Step 5: Ejecutar y ver el verde**

Run: `cmd.exe /C 'npx vitest run resources/js/Components/__tests__/DecorationArt.test.js --reporter=dot'`
Expected: 3 passed.

---

## Task 9: El objeto en la balda (`ShelfDecoration`) y la fila mixta

**Files:**
- Create: `resources/js/Components/ShelfDecoration.vue`
- Modify: `resources/js/Components/Shelf.vue`
- Test: `resources/js/Components/__tests__/ShelfDecoration.test.js`
- Test: `resources/js/Components/__tests__/Shelf.test.js`

- [ ] **Step 1: Escribir la prueba que falla**

`resources/js/Components/__tests__/ShelfDecoration.test.js`:

```js
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/vue3';
import ShelfDecoration from '../ShelfDecoration.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal();

    return { ...original, router: { delete: vi.fn() } };
});

function makeDecoration(overrides = {}) {
    return { id: 9, kind: 'cup', color: 'rose', shelf_id: 7, after_book_id: 3, sort: 0, ...overrides };
}

describe('ShelfDecoration', () => {
    it('se identifica para el arrastre y anuncia su nombre', () => {
        const wrapper = mount(ShelfDecoration, { props: { decoration: makeDecoration() } });

        expect(wrapper.find('[data-decoration-id="9"]').exists()).toBe(true);
        expect(wrapper.find('[role="img"]').attributes('aria-label')).toBe('Taza');
    });

    it('atenúa el objeto mientras se arrastra', () => {
        const wrapper = mount(ShelfDecoration, {
            props: { decoration: makeDecoration(), dragging: true },
        });

        expect(wrapper.classes()).toContain('opacity-30');
    });

    it('quita el objeto con el botón ×', async () => {
        const wrapper = mount(ShelfDecoration, { props: { decoration: makeDecoration() } });

        await wrapper.find('button').trigger('click');

        expect(router.delete).toHaveBeenCalledWith('/decorations/9', {
            preserveScroll: true,
            preserveState: true,
        });
    });
});
```

Ampliar `resources/js/Components/__tests__/Shelf.test.js`:

1. Cambia el mock del router para incluir `delete`:

```js
    return { ...original, router: { patch: vi.fn(), delete: vi.fn() } };
```

2. Añade `decorations: []` a `makeShelf`:

```js
function makeShelf(overrides = {}) {
    return {
        id: 7,
        name: 'Favoritos',
        books: [
            { id: 3, title: 'Rayuela', author: 'Julio Cortázar', cover_path: null, shelf_id: 7, view_mode: 'covers' },
        ],
        decorations: [],
        ...overrides,
    };
}
```

3. Añade estas pruebas al final del `describe`:

```js
    it('intercala los objetos con los libros según su ancla', () => {
        const wrapper = mount(Shelf, {
            props: {
                shelf: makeShelf({
                    books: [
                        { id: 3, title: 'Rayuela', author: 'Julio Cortázar', cover_path: null, shelf_id: 7, view_mode: 'covers' },
                        { id: 4, title: 'Ficciones', author: 'Jorge Luis Borges', cover_path: null, shelf_id: 7, view_mode: 'spines' },
                    ],
                    decorations: [
                        { id: 9, kind: 'cup', color: 'rose', shelf_id: 7, after_book_id: 3, sort: 0 },
                        { id: 10, kind: 'roses', color: 'green', shelf_id: 7, after_book_id: null, sort: 0 },
                    ],
                }),
            },
        });

        const order = wrapper
            .findAll('[data-book-id], [data-decoration-id]')
            .map((element) => element.attributes('data-book-id') ?? `d${element.attributes('data-decoration-id')}`);

        expect(order).toEqual(['d10', '3', 'd9', '4']);
    });

    it('marca la línea de inserción en un objeto', () => {
        const wrapper = mount(Shelf, {
            props: {
                shelf: makeShelf({
                    decorations: [
                        { id: 9, kind: 'cup', color: 'rose', shelf_id: 7, after_book_id: 3, sort: 0 },
                    ],
                }),
                dragActive: true,
                insertion: { shelfId: 7, decorationId: 9, position: 'before' },
                draggingDecorationId: 9,
            },
        });

        expect(wrapper.find('[data-decoration-id="9"]').classes()).toContain('book-drop-before');
    });
```

- [ ] **Step 2: Ejecutar y ver el fallo**

Run: `cmd.exe /C 'npx vitest run resources/js/Components/__tests__/Shelf.test.js resources/js/Components/__tests__/ShelfDecoration.test.js --reporter=dot'`
Expected: FAIL — no existe `ShelfDecoration.vue` y los objetos no se pintan.

- [ ] **Step 3: Crear `ShelfDecoration.vue`**

```vue
<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import DecorationArt from './DecorationArt.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    decoration: { type: Object, required: true },
    dragging: { type: Boolean, default: false },
    dropPosition: { type: String, default: null },
});

const { t } = useI18n();

const name = computed(() => t(`ui.decoration_${props.decoration.kind}`));

function remove() {
    router.delete(`/decorations/${props.decoration.id}`, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <div
        class="decoration-drag-source group relative shrink-0"
        :data-decoration-id="decoration.id"
        :class="{
            'opacity-30': dragging,
            'book-drop-before': dropPosition === 'before',
            'book-drop-after': dropPosition === 'after',
        }"
    >
        <div
            class="book-glow flex h-16 w-14 cursor-grab items-end justify-center sm:h-20 sm:w-16"
            role="img"
            :aria-label="name"
            :title="name"
        >
            <DecorationArt :kind="decoration.kind" :color="decoration.color" class="h-full w-full" />
        </div>

        <button
            type="button"
            class="absolute -right-1 -top-1 hidden h-7 w-7 items-center justify-center rounded-full border border-line bg-night-raised text-moon opacity-0 shadow-card transition-opacity focus-visible:opacity-100 group-hover:opacity-100 sm:flex"
            :aria-label="t('ui.decoration_remove')"
            :title="t('ui.decoration_remove')"
            @pointerdown.stop
            @touchstart.stop
            @click.prevent="remove"
        >
            <svg class="h-3.5 w-3.5" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                <path d="M3.5 3.5l7 7m0-7l-7 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            </svg>
        </button>
    </div>
</template>
```

- [ ] **Step 4: Rehacer la fila de `Shelf.vue`**

Sustituye el bloque `<div v-else class="flex flex-row ...">` completo por:

```html
                <div
                    v-else
                    class="flex flex-row items-end gap-3 overflow-x-auto pb-1 sm:flex-wrap sm:overflow-visible sm:pb-0"
                >
                    <template v-for="item in items" :key="item.key">
                        <div
                            v-if="item.type === 'book'"
                            class="group relative shrink-0"
                            :class="item.book.view_mode === 'spines' ? '' : 'w-[100px] sm:w-[110px]'"
                        >
                            <BookSpine
                                v-if="item.book.view_mode === 'spines'"
                                :book="item.book"
                                :dragging="draggingBookId === item.book.id"
                                :drop-position="dropPositionFor(item.book)"
                            />
                            <BookCard
                                v-else
                                :book="item.book"
                                :dragging="draggingBookId === item.book.id"
                                :drop-position="dropPositionFor(item.book)"
                            />

                            <button
                                type="button"
                                class="absolute -right-1 -top-1 hidden h-7 w-7 items-center justify-center rounded-full border border-line bg-night-raised text-moon opacity-0 shadow-card transition-opacity focus-visible:opacity-100 group-hover:opacity-100 sm:flex"
                                :aria-label="t('ui.switch_view')"
                                :title="t('ui.switch_view')"
                                @click.prevent="toggleBook(item.book)"
                            >
                                <svg
                                    v-if="item.book.view_mode === 'spines'"
                                    class="h-4 w-4"
                                    viewBox="0 0 14 14"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <rect x="3.5" y="1.5" width="7" height="11" rx="1.4" stroke="currentColor" stroke-width="1.3" />
                                    <path d="M5.75 2v10" stroke="currentColor" stroke-width="1.3" />
                                </svg>
                                <svg
                                    v-else
                                    class="h-4 w-4"
                                    viewBox="0 0 14 14"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <path d="M4 2.5v9M7 2.5v9M10 2.5v9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                </svg>
                            </button>
                        </div>

                        <ShelfDecoration
                            v-else
                            :decoration="item.decoration"
                            :dragging="draggingDecorationId === item.decoration.id"
                            :drop-position="dropPositionForDecoration(item.decoration)"
                        />
                    </template>
                </div>
```

Y en el `<script setup>` de `Shelf.vue`:

1. Añade las importaciones:

```js
import ShelfDecoration from './ShelfDecoration.vue';
import { mergeShelfItems } from '@/shelfItems';
```

2. Añade la prop nueva:

```js
    draggingDecorationId: { type: Number, default: null },
```

3. Añade `items` y el indicador de los objetos:

```js
const items = computed(() =>
    mergeShelfItems(props.shelf.books ?? [], props.shelf.decorations ?? [])
);

function dropPositionForDecoration(decoration) {
    if (
        props.insertion?.shelfId !== props.shelf.id ||
        props.insertion?.decorationId !== decoration.id
    ) {
        return null;
    }

    return props.insertion.position;
}
```

- [ ] **Step 5: Ejecutar y ver el verde**

Run: `cmd.exe /C 'npx vitest run resources/js/Components/__tests__/Shelf.test.js resources/js/Components/__tests__/ShelfDecoration.test.js --reporter=dot'`
Expected: 9 passed.

---

## Task 10: Sección «Decoración» en Ajustes

**Files:**
- Create: `resources/js/Components/DecorationSettings.vue`
- Modify: `resources/js/Pages/Settings/Index.vue`
- Modify: `app/Http/Controllers/SettingsController.php` (ya hecho en Task 6)
- Test: `resources/js/Components/__tests__/DecorationSettings.test.js`

- [ ] **Step 1: Escribir la prueba que falla**

`resources/js/Components/__tests__/DecorationSettings.test.js`:

```js
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    post: vi.fn(),
    delete: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    router: { post: mocks.post, delete: mocks.delete },
    usePage: () => ({ props: {} }),
}));

import DecorationSettings from '../DecorationSettings.vue';

describe('DecorationSettings', () => {
    beforeEach(() => {
        mocks.post.mockClear();
        mocks.delete.mockClear();
    });

    it('muestra el catálogo y añade con el color por defecto', async () => {
        const wrapper = mount(DecorationSettings, { props: { decorations: [] } });

        expect(wrapper.find('[data-decoration-card="cup"]').exists()).toBe(true);

        await wrapper.find('[data-decoration-add="cup"]').trigger('click');

        expect(mocks.post).toHaveBeenCalledWith(
            '/decorations',
            { kind: 'cup', color: 'cream' },
            { preserveScroll: true }
        );
    });

    it('añade con el color elegido', async () => {
        const wrapper = mount(DecorationSettings, { props: { decorations: [] } });

        await wrapper.find('[data-color="cup"][data-color-key="gold"]').trigger('click');
        await wrapper.find('[data-decoration-add="cup"]').trigger('click');

        expect(mocks.post).toHaveBeenCalledWith(
            '/decorations',
            { kind: 'cup', color: 'gold' },
            { preserveScroll: true }
        );
    });

    it('quita un objeto colocado', async () => {
        const wrapper = mount(DecorationSettings, {
            props: {
                decorations: [
                    { id: 5, kind: 'cup', color: 'rose', shelf: { id: 1, name: 'En biblioteca' } },
                ],
            },
        });

        await wrapper.find('[data-decoration-row="5"] button').trigger('click');

        expect(mocks.delete).toHaveBeenCalledWith('/decorations/5', { preserveScroll: true });
    });

    it('quita todas tras confirmar', async () => {
        vi.spyOn(window, 'confirm').mockReturnValue(true);

        const wrapper = mount(DecorationSettings, {
            props: {
                decorations: [
                    { id: 5, kind: 'cup', color: 'rose', shelf: { id: 1, name: 'En biblioteca' } },
                ],
            },
        });

        await wrapper.find('[data-decorations-clear]').trigger('click');

        expect(mocks.delete).toHaveBeenCalledWith('/decorations', { preserveScroll: true });
    });
});
```

- [ ] **Step 2: Ejecutar y ver el fallo**

Run: `cmd.exe /C 'npx vitest run resources/js/Components/__tests__/DecorationSettings.test.js --reporter=dot'`
Expected: FAIL — no existe el componente.

- [ ] **Step 3: Crear `DecorationSettings.vue`**

```vue
<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import DecorationArt from './DecorationArt.vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    decorations: { type: Array, default: () => [] },
});

const { t } = useI18n();

const CATALOG = [
    { kind: 'cup', color: 'cream' },
    { kind: 'roses', color: 'rose' },
    { kind: 'plant', color: 'green' },
    { kind: 'candle', color: 'gold' },
    { kind: 'hourglass', color: 'gold' },
    { kind: 'crystal_ball', color: 'rose' },
    { kind: 'lamp', color: 'gold' },
    { kind: 'cat', color: 'clay' },
    { kind: 'skull', color: 'cream' },
    { kind: 'cage', color: 'gold' },
    { kind: 'astrolabe', color: 'gold' },
    { kind: 'tea_set', color: 'cream' },
    { kind: 'music_box', color: 'forest' },
    { kind: 'quill', color: 'gold' },
    { kind: 'photo_frame', color: 'clay' },
];

const COLORS = ['rose', 'cream', 'green', 'forest', 'gold', 'clay'];

const COLOR_CLASS = {
    rose: 'bg-orquidea',
    cream: 'bg-crema',
    green: 'bg-salvia',
    forest: 'bg-bosque',
    gold: 'bg-gold',
    clay: 'bg-terracota',
};

const chosen = reactive(Object.fromEntries(CATALOG.map((item) => [item.kind, item.color])));

function nameFor(kind) {
    return t(`ui.decoration_${kind}`);
}

function add(item) {
    router.post(
        '/decorations',
        { kind: item.kind, color: chosen[item.kind] },
        { preserveScroll: true }
    );
}

function remove(decoration) {
    router.delete(`/decorations/${decoration.id}`, { preserveScroll: true });
}

function clearAll() {
    if (window.confirm(t('ui.decorations_remove_all_confirm'))) {
        router.delete('/decorations', { preserveScroll: true });
    }
}
</script>

<template>
    <section class="mb-8 space-y-4">
        <h2 class="text-2xl">{{ t('ui.decorations_heading') }}</h2>
        <p class="text-sm text-mist">{{ t('ui.decorations_help') }}</p>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="item in CATALOG"
                :key="item.kind"
                class="rounded-card border border-line bg-panel p-3"
                :data-decoration-card="item.kind"
            >
                <div class="mb-2 flex h-24 items-end justify-center rounded-control bg-panel-raised p-2">
                    <DecorationArt :kind="item.kind" :color="chosen[item.kind]" class="h-full w-full" />
                </div>

                <p class="mb-2 font-serif text-lg text-gold">{{ nameFor(item.kind) }}</p>

                <div class="mb-3 flex items-center gap-2" :aria-label="t('ui.decoration_color')">
                    <button
                        v-for="color in COLORS"
                        :key="color"
                        type="button"
                        class="h-6 w-6 rounded-full border border-line transition-shadow"
                        :class="[COLOR_CLASS[color], chosen[item.kind] === color ? 'ring-2 ring-magic ring-offset-2 ring-offset-panel' : '']"
                        :data-color="item.kind"
                        :data-color-key="color"
                        :aria-label="t(`ui.color_${color}`)"
                        :aria-pressed="chosen[item.kind] === color"
                        :title="t(`ui.color_${color}`)"
                        @click="chosen[item.kind] = color"
                    />
                </div>

                <button
                    type="button"
                    class="w-full rounded-control bg-arcane px-4 py-2 text-moon hover:bg-arcane-strong"
                    :data-decoration-add="item.kind"
                    @click="add(item)"
                >
                    {{ t('ui.decoration_add') }}
                </button>
            </div>
        </div>

        <button
            type="button"
            class="rounded-control border border-danger px-4 py-2 text-danger hover:bg-danger/10"
            data-decorations-clear
            @click="clearAll"
        >
            {{ t('ui.decorations_remove_all') }}
        </button>

        <div>
            <h3 class="mb-2 text-xl">{{ t('ui.decorations_placed') }}</h3>

            <p v-if="props.decorations.length === 0" class="text-sm text-mist">
                {{ t('ui.decorations_empty') }}
            </p>

            <ul v-else class="space-y-2">
                <li
                    v-for="decoration in props.decorations"
                    :key="decoration.id"
                    class="flex items-center gap-3 rounded-card border border-line bg-panel p-2"
                    :data-decoration-row="decoration.id"
                >
                    <div class="h-10 w-8 shrink-0">
                        <DecorationArt :kind="decoration.kind" :color="decoration.color" class="h-full w-full" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm text-moon">{{ nameFor(decoration.kind) }}</p>
                        <p class="truncate text-xs text-mist">{{ decoration.shelf?.name }}</p>
                    </div>

                    <span
                        class="h-4 w-4 shrink-0 rounded-full border border-line"
                        :class="COLOR_CLASS[decoration.color]"
                        :aria-label="t(`ui.color_${decoration.color}`)"
                    />

                    <button
                        type="button"
                        class="rounded-control border border-line px-2 py-1 text-sm text-moon hover:bg-panel-raised"
                        :aria-label="t('ui.decoration_remove')"
                        :title="t('ui.decoration_remove')"
                        @click="remove(decoration)"
                    >
                        ×
                    </button>
                </li>
            </ul>
        </div>
    </section>
</template>
```

- [ ] **Step 4: Montarla en Ajustes**

En `resources/js/Pages/Settings/Index.vue`:

1. Añade la importación:

```js
import DecorationSettings from '@/Components/DecorationSettings.vue';
```

2. Añade la prop:

```js
    decorations: { type: Array, default: () => [] },
```

3. Inserta el componente justo después del bloque de Estanterías (`</section>` de estanterías, línea ~237):

```html
        <DecorationSettings :decorations="decorations" />
```

- [ ] **Step 5: Ejecutar y ver el verde**

Run: `cmd.exe /C 'npx vitest run resources/js/Components/__tests__/DecorationSettings.test.js --reporter=dot'`
Expected: 4 passed.

---

## Task 11: Arrastrar objetos

**Files:**
- Create: `resources/js/Components/DecorationDragGhost.vue`
- Modify: `resources/js/useLibraryDrag.js`
- Modify: `resources/js/Pages/Library/Index.vue`
- Test: `resources/js/shelfItems.test.js` (ya cubre el ancla)

- [ ] **Step 1: Crear la miniatura**

`resources/js/Components/DecorationDragGhost.vue`:

```vue
<script setup>
import { computed } from 'vue';
import DecorationArt from './DecorationArt.vue';

const props = defineProps({
    decoration: { type: Object, required: true },
    x: { type: Number, required: true },
    y: { type: Number, required: true },
});

const style = computed(() => ({
    transform: `translate3d(${props.x}px, ${props.y}px, 0) translate(-50%, -70%)`,
}));
</script>

<template>
    <Teleport to="body">
        <div class="book-drag-ghost" :style="style" aria-hidden="true">
            <div class="h-20 w-16 drop-shadow-lg">
                <DecorationArt :kind="decoration.kind" :color="decoration.color" class="h-full w-full" />
            </div>
        </div>
    </Teleport>
</template>
```

- [ ] **Step 2: Generalizar `useLibraryDrag`**

En `resources/js/useLibraryDrag.js`:

1. Cambia el import inicial:

```js
import { onBeforeUnmount, ref } from 'vue';
import { resolveDecorationAnchor } from '@/shelfItems';
```

2. Sustituye `const draggedBook = ref(null);` por:

```js
    const draggedItem = ref(null);
```

3. Sustituye `locateBook` por:

```js
    function locateItem(type, id) {
        for (const shelf of getShelves()) {
            if (type === 'book') {
                const book = (shelf.books ?? []).find((item) => item.id === id);

                if (book) {
                    return { type: 'book', item: book, shelf };
                }
            } else {
                const decoration = (shelf.decorations ?? []).find((item) => item.id === id);

                if (decoration) {
                    return { type: 'decoration', item: decoration, shelf };
                }
            }
        }

        return null;
    }

    function dragElementFrom(event) {
        return event.target.closest?.('[data-book-id], [data-decoration-id]');
    }

    function identifyDragElement(element) {
        if (element.dataset.bookId) {
            return { type: 'book', id: Number(element.dataset.bookId) };
        }

        return { type: 'decoration', id: Number(element.dataset.decorationId) };
    }
```

4. En `onPointerDown`, sustituye el bloque desde `const element = event.target.closest?.('[data-book-id]');` hasta la creación de `candidate` por:

```js
        const element = dragElementFrom(event);
        if (!element) {
            return;
        }

        const { type, id } = identifyDragElement(element);
        const found = locateItem(type, id);
        if (!found) {
            return;
        }

        candidate = {
            type,
            item: found.item,
            shelf: found.shelf,
            startX: event.clientX,
            startY: event.clientY,
            pointerId: event.pointerId,
            touch: false,
            activated: false,
            timer: null,
        };
```

5. En `onTouchStart`, haz el mismo cambio (misma sustitución, usando `touch.clientX` / `touch.clientY` como ya hace el archivo).

6. En `activate`, sustituye las dos líneas de `draggedBook` y `dragMode` por:

```js
        draggedItem.value = { type: candidate.type, item: candidate.item };
        dragMode.value = candidate.type === 'book' && candidate.item.view_mode === 'spines'
            ? 'spines'
            : 'covers';
```

7. En `updatePointer`, después del bloque que comprueba `if (!target || !sourceShelf)`, añade:

```js
        if (draggedItem.value?.type === 'decoration') {
            dropTargetId.value = target === sourceShelf.id ? null : target;

            const shelf = getShelves().find((item) => item.id === target);
            const element = document.elementFromPoint(x, y)?.closest?.('[data-book-id], [data-decoration-id]');
            let hit = null;

            if (element) {
                const identified = identifyDragElement(element);

                if (!(identified.type === 'decoration' && identified.id === draggedItem.value.item.id)) {
                    hit = identified;
                }
            }

            insertion.value = decorationInsertion(target, shelf, hit, x, y);

            return;
        }
```

8. Añade la función `decorationInsertion` antes de `autoScroll`:

```js
    function decorationInsertion(shelfId, shelf, hit, x, y) {
        if (!shelf) {
            return null;
        }

        let place = 'after';

        if (hit) {
            const selector = hit.type === 'book'
                ? `[data-book-id="${hit.id}"]`
                : `[data-decoration-id="${hit.id}"]`;
            const rect = document.querySelector(selector)?.getBoundingClientRect();

            if (rect) {
                place = x >= rect.left + rect.width / 2 ? 'after' : 'before';
            }
        }

        const anchor = resolveDecorationAnchor(
            shelf.books ?? [],
            shelf.decorations ?? [],
            hit,
            place,
            draggedItem.value?.item.id ?? null
        );

        return {
            shelfId,
            bookId: hit?.type === 'book' ? hit.id : null,
            decorationId: hit?.type === 'decoration' ? hit.id : null,
            position: place,
            anchor,
        };
    }
```

9. Sustituye `finishDrag` completa por:

```js
    function finishDrag(x, y) {
        const targetId = shelfIdAt(x, y);
        const current = draggedItem.value;
        const currentInsertion = insertion.value;
        const fromShelfId = sourceShelf?.id ?? null;

        cleanup();

        if (!current || !targetId) {
            return;
        }

        if (current.type === 'decoration') {
            onDrop(current, targetId, currentInsertion?.anchor ?? null);

            return;
        }

        const shouldMove = Boolean(fromShelfId && targetId !== fromShelfId);
        const shouldReorder = Boolean(fromShelfId && targetId === fromShelfId && currentInsertion);

        if (shouldMove) {
            onDrop(current, targetId);
        } else if (shouldReorder) {
            onDrop(current, targetId, currentInsertion);
        }
    }
```

10. En `cleanup`, cambia `draggedBook.value = null;` por `draggedItem.value = null;`.

11. En el `return` final, cambia `draggedBook,` por `draggedItem,`.

- [ ] **Step 3: Adaptar `Library/Index.vue`**

1. Importa el nuevo fantasma:

```js
import DecorationDragGhost from '@/Components/DecorationDragGhost.vue';
```

2. `cloneShelves` debe clonar también las decoraciones:

```js
function cloneShelves(shelves) {
    return shelves.map((shelf) => ({
        ...shelf,
        books: (shelf.books ?? []).map((book) => ({ ...book })),
        decorations: (shelf.decorations ?? []).map((decoration) => ({ ...decoration })),
    }));
}
```

3. Añade `moveDecoration` después de `moveBook`:

```js
function moveDecoration(decoration, targetShelfId, anchor) {
    const sourceShelf = localShelves.value.find((shelf) =>
        (shelf.decorations ?? []).some((item) => item.id === decoration.id)
    );
    const targetShelf = localShelves.value.find((shelf) => shelf.id === targetShelfId);

    if (!sourceShelf || !targetShelf || !anchor) {
        return;
    }

    const snapshot = cloneShelves(localShelves.value);
    const index = sourceShelf.decorations.findIndex((item) => item.id === decoration.id);

    if (index === -1) {
        return;
    }

    const [moved] = sourceShelf.decorations.splice(index, 1);
    moved.shelf_id = targetShelf.id;
    moved.after_book_id = anchor.afterBookId;
    moved.sort = anchor.sort;
    targetShelf.decorations.push(moved);

    let settled = false;

    router.patch(
        `/decorations/${decoration.id}`,
        {
            shelf_id: targetShelf.id,
            after_book_id: anchor.afterBookId,
            sort: anchor.sort,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                settled = true;
                showNotice(
                    'success',
                    t('ui.decoration_moved', {
                        name: t(`ui.decoration_${decoration.kind}`),
                        shelf: targetShelf.display_name ?? targetShelf.name,
                    })
                );
            },
            onError: () => {
                settled = true;
                localShelves.value = snapshot;
                showNotice('error', t('ui.decoration_move_error'));
            },
            onFinish: () => {
                if (settled) {
                    return;
                }

                localShelves.value = snapshot;
                showNotice('error', t('ui.decoration_move_error'));
            },
        }
    );
}
```

4. Sustituye `handleDrop` por:

```js
function handleDrop(dragged, targetShelfId, target = null) {
    if (dragged.type === 'decoration') {
        moveDecoration(dragged.item, targetShelfId, target);

        return;
    }

    if (target) {
        reorderBook(dragged.item, targetShelfId, target);

        return;
    }

    moveBook(dragged.item, targetShelfId);
}
```

5. En la desestructuración del composable, cambia `draggedBook,` por `draggedItem,`.

6. En el template, cambia las props de `Shelf`:

```html
                :dragging-book-id="draggedItem?.type === 'book' ? draggedItem.item.id : null"
                :dragging-decoration-id="draggedItem?.type === 'decoration' ? draggedItem.item.id : null"
```

7. Sustituye el bloque de `BookDragGhost` por:

```html
        <BookDragGhost
            v-if="draggedItem?.type === 'book'"
            :book="draggedItem.item"
            :mode="dragMode"
            :x="pointer.x"
            :y="pointer.y"
        />

        <DecorationDragGhost
            v-else-if="draggedItem"
            :decoration="draggedItem.item"
            :x="pointer.x"
            :y="pointer.y"
        />
```

- [ ] **Step 4: Ejecutar las pruebas**

Run: `cmd.exe /C 'npx vitest run --reporter=dot'`
Expected: todas pasan (incluidas `Shelf.test.js`, `ShelfDecoration.test.js`, `shelfItems.test.js`).

- [ ] **Step 5: Comprobación manual del arrastre**

1. Abre `http://localhost:8000`, añade una taza desde Ajustes.
2. Arrástrala entre dos libros y comprueba que se queda ahí.
3. Arrástrala a otra estantería.
4. Arrástrala al principio de la balda.
5. En móvil (o ventana estrecha), mantén pulsado y arrastra.

---

## Task 12: Documentación visual y verificación final

**Files:**
- Modify: `docs/DISENO.md`

- [ ] **Step 1: Añadir la subsección de componentes**

En `docs/DISENO.md`, después de la sección «Lomo (`BookSpine`)» y antes de «Estantería (`Shelf` + `ShelfBoard`)», inserta:

```markdown
### Objeto decorativo (`DecorationArt` + `ShelfDecoration`)

- Quince dibujos SVG planos en un lienzo de 64 × 80: taza, rosas, planta, vela, reloj de arena, bola de cristal, lámpara, gato, calavera, jaula, astrolabio, set de té, caja de música, pluma y tintero, marco de fotos.
- La pieza principal usa el color elegido entre seis tokens existentes: `orquidea` (rosa), `crema`, `salvia` (verde), `bosque`, `gold` (dorado) y `terracota`. Los neutros salen de `madera`, `tinta` y `pergamino`; no se añaden colores nuevos.
- En la balda ocupa ~56 px de ancho (64 px desde `sm`) y se apoya en el fondo como los libros, con halo `dorado` al pasar el cursor y sombra suave.
- Al pasar el ratón aparece una **×** circular arriba a la derecha (solo escritorio) para quitarlo; en móvil se quita desde Ajustes.
- `title` y `aria-label` con el nombre traducido del objeto.
```

- [ ] **Step 2: Actualizar la sección de la estantería**

En la lista de la sección «Estantería», añade un punto después de «Balda mixta»:

```markdown
- **Objetos intercalados:** los objetos decorativos comparten la fila con los libros y se ordenan por su ancla: sin ancla van antes del primer libro; con ancla, justo después de ese libro; si el libro ancla no está en la balda, van al final.
```

- [ ] **Step 3: Actualizar el arrastre**

En «Arrastrar libros entre estanterías», cambia el primer punto por:

```markdown
- Origen: cualquier `BookCard`, `BookSpine` o `ShelfDecoration`. En escritorio se arrastra con el botón principal y el gesto se activa tras 6 px. En móvil se mantiene pulsado 320 ms y después se arrastra; si hay un desplazamiento mayor de 8 px antes de ese tiempo, el gesto se cancela y la página puede hacer scroll.
```

Y añade al final de la sección:

```markdown
- Los objetos decorativos usan el mismo gesto: se pueden soltar entre dos libros, antes del primero, al final o en otra estantería. La miniatura flotante muestra el propio objeto.
```

- [ ] **Step 4: Registrar el cambio**

Añade al final de la tabla de cambios:

```markdown
| 2026-10-30 | Objetos decorativos: quince dibujos (taza, rosas, planta, vela, reloj de arena, bola de cristal, lámpara, gato, calavera, jaula, astrolabio, set de té, caja de música, pluma y tintero, marco de fotos) con seis colores de la paleta existente. Se añaden desde Ajustes de uno en uno, al final de la primera estantería, se intercalan con los libros, se arrastran dentro y entre estanterías, y se quitan con la × del objeto o desde la lista de Ajustes. | Agente |
```

- [ ] **Step 5: Verificación completa**

Run: `cmd.exe /C 'php artisan test'`
Expected: todos los tests pasan (17 nuevos de decoraciones + los existentes).

Run: `cmd.exe /C 'npx vitest run --reporter=dot'`
Expected: todos los tests pasan.

Run: `cmd.exe /C 'npm run build'`
Expected: `✓ built in ...` sin errores.

- [ ] **Step 6: Revisión manual**

- Ajustes → Decoración: catálogo de 15, colores, añadir, quitar uno, quitar todas.
- Biblioteca: objetos intercalados, halo, ×, arrastre dentro y entre estanterías.
- 360 px: sin scroll horizontal en Ajustes; los objetos se deslizan con la balda.
- Cambiar de idioma y comprobar que los nombres y los avisos se traducen.
- Confirmar que los objetos de otra persona no se pueden tocar (403 en pruebas).

- [ ] **Step 7: Dejar listo para el commit de la usuaria**

No ejecutar `git add` ni `git commit`. Informar de los archivos modificados.
