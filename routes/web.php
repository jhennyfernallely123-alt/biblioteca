<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\Auth\CompleteNicknameController;
use App\Http\Controllers\BookcaseController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookImageController;
use App\Http\Controllers\BookLookupController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CardDrawController;
use App\Http\Controllers\CatalogBookController;
use App\Http\Controllers\CatalogCoverController;
use App\Http\Controllers\CoverController;
use App\Http\Controllers\DecorationController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReadingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShelfController;
use App\Http\Controllers\ShelvesController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

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
    Route::patch('/bookcase/move', [BookcaseController::class, 'move'])->name('bookcase.move');
    Route::patch('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::patch('/books/{book}/move', [BookController::class, 'move'])->name('books.move');
    Route::patch('/books/{book}/view-mode', [BookController::class, 'viewMode'])->name('books.view-mode');
    Route::get('/books/{book}/reading', [ReadingController::class, 'show'])->name('reading.show');
    Route::patch('/books/{book}/reading', [ReadingController::class, 'update'])->name('reading.update');
    Route::post('/books/{book}/reading-sessions', [ReadingController::class, 'storeSession'])->name('reading.sessions.store');
    Route::delete('/reading-sessions/{session}', [ReadingController::class, 'destroySession'])->name('reading.sessions.destroy');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    Route::get('/covers/{book}', CoverController::class)->name('covers.show');
    Route::get('/catalog-covers/{catalogBook}', CatalogCoverController::class)
        ->middleware('auth')
        ->name('catalog-covers.show');
    Route::get('/book-images/{image}', [BookImageController::class, 'show'])->name('book-images.show');
    Route::post('/books/{book}/images', [BookImageController::class, 'store'])->name('book-images.store');
    Route::delete('/book-images/{image}', [BookImageController::class, 'destroy'])->name('book-images.destroy');

    Route::get('/cards', [CardController::class, 'index'])->name('cards.index');
    Route::get('/cards/album', [CardController::class, 'album'])->name('cards.album');
    Route::post('/cards/draw', [CardDrawController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('cards.draw');
    Route::get('/cards/{card}', [CardController::class, 'show'])
        ->where('card', '[a-z_]+')
        ->name('cards.show');

    Route::get('/album', [AlbumController::class, 'index'])->name('album.index');

    Route::get('/decorations', [DecorationController::class, 'index'])->name('decorations.index');
    Route::post('/decorations', [DecorationController::class, 'store'])->name('decorations.store');
    Route::patch('/decorations/{decoration}', [DecorationController::class, 'update'])->name('decorations.update');
    Route::delete('/decorations', [DecorationController::class, 'clear'])->name('decorations.clear');
    Route::delete('/decorations/{decoration}', [DecorationController::class, 'destroy'])->name('decorations.destroy');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::patch('/settings/theme', [SettingsController::class, 'updateTheme'])->name('settings.theme');

    Route::post('/settings/locale/prepare', [LocaleController::class, 'prepare'])
        ->middleware('throttle:30,1')
        ->name('settings.locale.prepare');
    Route::post('/settings/locale/translate', [LocaleController::class, 'translate'])
        ->middleware('throttle:30,1')
        ->name('settings.locale.translate');
    Route::patch('/settings/locale', [LocaleController::class, 'update'])
        ->name('settings.locale');

    Route::get('/shelves', [ShelvesController::class, 'index'])->name('shelves.index');
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

Route::middleware(['auth', 'nickname', 'master'])
    ->prefix('catalog')
    ->name('catalog.')
    ->group(function () {
        Route::get('/', [CatalogBookController::class, 'index'])->name('index');
        Route::get('/create', [CatalogBookController::class, 'create'])->name('create');
        Route::post('/', [CatalogBookController::class, 'store'])->name('store');
        Route::get('/{catalogBook}/edit', [CatalogBookController::class, 'edit'])->name('edit');
        Route::patch('/{catalogBook}', [CatalogBookController::class, 'update'])->name('update');
        Route::delete('/{catalogBook}', [CatalogBookController::class, 'destroy'])->name('destroy');
    });

require __DIR__.'/auth.php';
