<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'brand_name', 'gtin', 'external_id',
        'package_quantity', 'package_unit', 'units_per_package',
        'image_url', 'nutriscore_grade', 'nova_group', 'allergens',
        'nutrition_data', 'source', 'source_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'package_quantity' => 'decimal:3',
            'units_per_package' => 'integer',
            'nova_group' => 'integer',
            'allergens' => 'array',
            'nutrition_data' => 'array',
            'source_updated_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function shoppingListItems(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class);
    }
}
