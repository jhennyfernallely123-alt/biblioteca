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
    expect(Isbn::to13('843760494X'))->toBe('9788437604947');
});
