<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

class LiteraryUniverseBook extends Model
{
    use AsPivot, HasFactory;

    protected $table = 'literary_universe_book';

    protected $fillable = [
        'literary_universe_id', 'book_id', 'reading_order',
    ];

    protected function casts(): array
    {
        return [
            'reading_order' => 'integer',
        ];
    }

    public function literaryUniverse(): BelongsTo
    {
        return $this->belongsTo(LiteraryUniverse::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
