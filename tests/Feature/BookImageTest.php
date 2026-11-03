<?php

use App\Models\Book;
use App\Models\BookImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->book = Book::factory()->for($this->user)->create();
    $this->actingAs($this->user);

    Storage::fake('public');

    $this->png = fn () => UploadedFile::fake()->createWithContent(
        'foto.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
    );
});

it('guarda una imagen en una ranura', function () {
    $this->post(route('book-images.store', $this->book), [
        'image' => ($this->png)(),
        'slot' => 0,
    ])->assertRedirect();

    $image = $this->book->images()->sole();

    expect($image->slot)->toBe(0);

    Storage::disk('public')->assertExists($image->path);
});

it('reemplaza la imagen de la misma ranura', function () {
    $this->post(route('book-images.store', $this->book), [
        'image' => ($this->png)(),
        'slot' => 1,
    ])->assertRedirect();

    $old = $this->book->images()->sole();

    $this->post(route('book-images.store', $this->book), [
        'image' => ($this->png)(),
        'slot' => 1,
    ])->assertRedirect();

    expect($this->book->images()->count())->toBe(1);

    Storage::disk('public')->assertMissing($old->path);
});

it('rechaza una ranura fuera de rango', function () {
    $this->post(route('book-images.store', $this->book), [
        'image' => ($this->png)(),
        'slot' => 3,
    ])->assertSessionHasErrors('slot');

    expect($this->book->images()->count())->toBe(0);
});

it('quita una imagen', function () {
    $this->post(route('book-images.store', $this->book), [
        'image' => ($this->png)(),
        'slot' => 2,
    ])->assertRedirect();

    $image = $this->book->images()->sole();

    $this->delete(route('book-images.destroy', $image))->assertRedirect();

    expect(BookImage::find($image->id))->toBeNull();
    Storage::disk('public')->assertMissing($image->path);
});

it('no permite añadir imágenes a un libro ajeno', function () {
    $foreign = Book::factory()->for(User::factory())->create();

    $this->post(route('book-images.store', $foreign), [
        'image' => ($this->png)(),
        'slot' => 0,
    ])->assertForbidden();

    expect($foreign->images()->count())->toBe(0);
});

it('no permite ver ni quitar imágenes ajenas', function () {
    $other = User::factory()->create();
    $foreignBook = Book::factory()->for($other)->create();
    $image = $foreignBook->images()->create(['slot' => 0, 'path' => 'book-images/x.png']);

    $this->get(route('book-images.show', $image))->assertForbidden();
    $this->delete(route('book-images.destroy', $image))->assertForbidden();
});
