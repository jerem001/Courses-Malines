<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingListTemplate extends Model
{
    protected $fillable = ['user_id', 'name', 'description', 'items'];

    protected function casts(): array
    {
        return ['items' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
