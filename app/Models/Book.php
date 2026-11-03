<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    public const BOOKS_PER_BAND = 5;

    protected $fillable = [
        'shelf_id', 'position', 'band', 'band_position', 'view_mode', 'title', 'author',
        'isbn', 'publisher', 'published_year', 'total_pages', 'current_page', 'cover_path',
        'rating', 'spice_rating', 'heart_rating', 'drop_rating', 'crystal_rating', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'band' => 'integer',
            'band_position' => 'integer',
            'published_year' => 'integer',
            'total_pages' => 'integer',
            'current_page' => 'integer',
            'rating' => 'integer',
            'spice_rating' => 'integer',
            'heart_rating' => 'integer',
            'drop_rating' => 'integer',
            'crystal_rating' => 'integer',
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

    public function images(): HasMany
    {
        return $this->hasMany(BookImage::class)->orderBy('slot');
    }

    public function readingSessions(): HasMany
    {
        return $this->hasMany(ReadingSession::class);
    }

    public function readingProgress(): int
    {
        if (! $this->total_pages || $this->current_page === null) {
            return 0;
        }

        return (int) min(100, round($this->current_page / $this->total_pages * 100));
    }

    public function readingStatus(): string
    {
        if (
            $this->total_pages
            && $this->current_page !== null
            && $this->current_page >= $this->total_pages
        ) {
            return 'finished';
        }

        $sessions = (int) ($this->reading_sessions_count ?? 0);

        return (($this->current_page ?? 0) > 0 || $sessions > 0) ? 'reading' : 'none';
    }
}
