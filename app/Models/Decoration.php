<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Decoration extends Model
{
    use HasFactory;

    public const KINDS = [
        'cup', 'roses', 'plant', 'candle', 'hourglass', 'crystal_ball', 'lamp',
        'cat', 'skull', 'cage', 'astrolabe', 'tea_set', 'music_box', 'quill',
        'photo_frame',
    ];

    public const COLORS = ['rose', 'cream', 'green', 'forest', 'gold', 'clay'];

    protected $fillable = ['shelf_id', 'kind', 'color', 'after_book_id', 'sort'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }

    public function afterBook(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'after_book_id');
    }
}
