<?php

namespace App\Services\Prices\Carrefour;

use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CarrefourStoreResolver
{
    public function resolve(string $serviceId): Store
    {
        $id = DB::table('store_source_identifiers')
            ->where('connector', 'carrefour')
            ->where('external_id', $serviceId)
            ->value('store_id');

        $store = $id === null ? null : Store::query()
            ->whereKey($id)
            ->whereHas('brand', fn ($query) => $query->where('slug', 'carrefour'))
            ->first();

        if (!$store) {
            throw ValidationException::withMessages([
                'service_id' => 'Service Drive Carrefour non associé à un magasin.',
            ]);
        }

        return $store;
    }
}
