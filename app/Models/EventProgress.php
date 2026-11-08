<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventProgress extends Model
{
    protected $fillable = ['user_id', 'event_key', 'day_date', 'task_key', 'ink_earned'];

    protected function casts(): array
    {
        return [
            'day_date' => 'date',
            'ink_earned' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
