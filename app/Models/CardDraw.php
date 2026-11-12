<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardDraw extends Model
{
    protected $fillable = ['user_id', 'draw_date', 'kind', 'item_key', 'ball_index', 'duplicate', 'ink_earned'];

    protected function casts(): array
    {
        return [
            'draw_date' => 'date',
            'duplicate' => 'boolean',
            'ink_earned' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
