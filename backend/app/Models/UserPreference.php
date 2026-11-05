<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id', 'city', 'postal_code', 'radius_km', 'search_mode',
    ];

    protected $casts = ['radius_km' => 'integer'];
}
