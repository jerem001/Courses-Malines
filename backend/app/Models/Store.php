<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'brand_id', 'external_id', 'name', 'address', 'postal_code',
        'city', 'latitude', 'longitude', 'drive_enabled',
    ];

    protected $casts = [
        'drive_enabled' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
