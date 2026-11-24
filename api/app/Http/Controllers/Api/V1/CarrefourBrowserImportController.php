<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Services\PriceObservationService;
use App\Services\Prices\Carrefour\CarrefourStoreResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CarrefourBrowserImportController
{
    public function store(Request $request, CarrefourStoreResolver $resolver, PriceObservationService $observations)
    {
        if (strlen($request->getContent()) > 200000) {
            throw ValidationException::withMessages(['products' => 'Relevé trop volumineux.']);
        }
        $data = $request->validate([
            'store' => 'required|array:store_id,cart_store_id,mode',
            'store.store_id' => 'required|string|regex:/^[0-9]{1,12}$/D',
            'store.cart_store_id' => 'required|string|regex:/^[0-9]{1,12}$/D',
            'store.mode' => 'required|in:PICKING_DRIVE',
            'observed_at' => 'required|date|before_or_equal:now',
            'products' => 'required|array|min:1|max:100',
            'products.*' => 'required|array:gtin,amount_minor,currency,price_basis,unit_price,unit_of_measure,promotion_label,sponsored',
            'products.*.gtin' => 'required|string|regex:/^[0-9]{13}$/D|distinct',
            'products.*.amount_minor' => 'required|integer|min:0|max:100000000',
            'products.*.currency' => 'required|in:EUR',
            'products.*.price_basis' => 'required|in:item',
            'products.*.unit_price' => 'nullable|numeric|min:0|max:1000000',
            'products.*.unit_of_measure' => 'nullable|in:KG,L',
            'products.*.promotion_label' => 'nullable|string|max:500',
            'products.*.sponsored' => 'required|boolean',
            'user_id' => 'prohibited',
        ]);
        // Only the previously verified Drive mapping is enabled.
        if ($data['store']['store_id'] !== '8138' || $data['store']['cart_store_id'] !== '1046') {
            throw ValidationException::withMessages(['store' => 'Drive non encore référencé pour cette extension.']);
        }
        $serviceId = '8138-150-1046';
        $store = $resolver->resolve($serviceId);
        return response()->json(DB::transaction(function () use ($data, $store, $serviceId, $request, $observations) {
            $ids = [];
            $unknown = [];
            foreach ($data['products'] as $row) {
                $product = Product::where('gtin', $row['gtin'])->first();
                if (!$product) {
                    $unknown[] = $row['gtin'];
                    continue;
                }
                $ids[] = $observations->record([
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                    'user_id' => $request->user()->id,
                    'source' => 'drive',
                    'source_reference' => 'carrefour-browser:'.$serviceId.':'.$row['gtin'],
                    'amount_minor' => (int) $row['amount_minor'],
                    'currency' => 'EUR',
                    'price_basis' => 'item',
                    'promotion' => empty($row['promotion_label']) ? [] : ['label' => $row['promotion_label'], 'verified' => false],
                    'observed_at' => $data['observed_at'],
                    'confidence' => 'observed',
                ])->id;
            }
            return ['store_id' => $store->id, 'observation_ids' => $ids, 'unknown_gtins' => $unknown];
        }));
    }
}
