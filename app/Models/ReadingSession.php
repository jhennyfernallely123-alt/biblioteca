<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingSession extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'book_id', 'read_on', 'minutes', 'page'];

    protected function casts(): array
    {
        return [
            'read_on' => 'date:Y-m-d',
            'minutes' => 'integer',
            'page' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
