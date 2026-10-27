<?php

use App\Models\Book;
use App\Models\User;

it('no permite ver la portada de un libro ajeno', function () {
    $book = Book::factory()->for(User::factory())->create(['cover_path' => 'covers/x.jpg']);

    $this->actingAs(User::factory()->create())
        ->get(route('covers.show', $book))
        ->assertForbidden();
});
