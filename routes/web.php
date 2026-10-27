<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookLookupController;
use App\Http\Controllers\CoverController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShelfController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', [LibraryController::class, 'index'])
    ->middleware('auth')
    ->name('library.index');

Route::get('/dashboard', fn () => redirect()->route('library.index'))
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/books/lookup', BookLookupController::class)->middleware('throttle:60,1')->name('books.lookup');
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
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

require __DIR__.'/auth.php';
