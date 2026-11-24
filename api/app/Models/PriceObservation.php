<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceObservation extends Model
{
    protected $fillable = ['product_id', 'store_id', 'user_id', 'source', 'source_reference', 'amount_minor', 'currency', 'price_basis', 'regular_amount_minor', 'promotion', 'observed_at', 'valid_until', 'confidence', 'fingerprint'];

    protected $casts = ['amount_minor' => 'integer', 'regular_amount_minor' => 'integer', 'promotion' => 'array', 'observed_at' => 'immutable_datetime', 'valid_until' => 'immutable_datetime'];

    public function product() { return $this->belongsTo(Product::class); }
    public function store() { return $this->belongsTo(Store::class); }
    public function scopeVisibleTo($query, int $userId)
    {
        return $query->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId));
    }
}
