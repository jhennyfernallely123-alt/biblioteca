<?php

namespace App\Models;

use Database\Factories\LiteraryUniverseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LiteraryUniverse extends Model
{
    /** @use HasFactory<LiteraryUniverseFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'description', 'type', 'total_books',
    ];

    protected function casts(): array
    {
        return [
            'total_books' => 'integer',
        ];
    }

    public const TYPES = [
        'bilogy' => 'ui.universe_type_bilogy',
        'trilogy' => 'ui.universe_type_trilogy',
        'tetralogy' => 'ui.universe_type_tetralogy',
        'saga' => 'ui.universe_type_saga',
        'series' => 'ui.universe_type_series',
        'other' => 'ui.universe_type_other',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function universeBooks(): HasMany
    {
        return $this->hasMany(LiteraryUniverseBook::class)->orderBy('reading_order');
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'literary_universe_book')
            ->using(LiteraryUniverseBook::class)
            ->withPivot('reading_order')
            ->withTimestamps()
            ->orderByPivot('reading_order');
    }
}
