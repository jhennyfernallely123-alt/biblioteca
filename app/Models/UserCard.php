<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCard extends Model
{
    protected $fillable = ['user_id', 'card_key', 'copies', 'first_unlocked_at'];

    protected function casts(): array
    {
        return [
            'copies' => 'integer',
            'first_unlocked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
