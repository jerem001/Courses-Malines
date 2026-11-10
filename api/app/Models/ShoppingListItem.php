<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingListItem extends Model
{
    protected $fillable = [
        'shopping_list_id', 'product_id', 'custom_name',
        'quantity', 'unit', 'is_checked', 'position',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'is_checked' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function shoppingList(): BelongsTo
    {
        return $this->belongsTo(ShoppingList::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
