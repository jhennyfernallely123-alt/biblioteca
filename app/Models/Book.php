<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    use HasFactory;

    public const BOOKS_PER_BAND = 5;

    protected $fillable = [
        'shelf_id', 'position', 'band', 'band_position', 'view_mode', 'title', 'author',
        'isbn', 'publisher', 'published_year', 'cover_path', 'rating', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'band' => 'integer',
            'band_position' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }
}
