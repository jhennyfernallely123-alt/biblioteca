<?php

namespace App\Models;

use Database\Factories\CatalogBookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogBook extends Model
{
    /** @use HasFactory<CatalogBookFactory> */
    use HasFactory;

    protected $fillable = [
        'created_by', 'title', 'author', 'isbn', 'publisher', 'published_year', 'cover_path',
    ];

    protected function casts(): array
    {
        return [
            'published_year' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
