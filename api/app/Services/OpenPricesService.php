<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class OpenPricesService
{
    public function forProduct(string $gtin): array
    {
        return Cache::remember('openprices:product:' . $gtin, now()->addMinutes(15), function () use ($gtin) {
            $response = Http::acceptJson()
                ->withHeaders(['User-Agent' => config('services.openfoodfacts.user_agent')])
                ->connectTimeout(5)
                ->timeout(15)
                ->retry(2, 300, function ($exception) {
                    return $exception instanceof ConnectionException
                        || ($exception instanceof RequestException
                            && ($exception->response->serverError() || $exception->response->status() === 429));
                })
                ->get('https://prices.openfoodfacts.org/api/v1/prices', [
                    'product_code' => $gtin,
                    'currency' => 'EUR',
                    'type' => 'PRODUCT',
                    'order_by' => '-date',
                    'size' => 50,
                    'page' => 1,
                ]);

            $response->throw();

            $items = collect($response->json('items', []))
                ->filter(fn ($p) => ($p['product_code'] ?? null) === $gtin
                    && ($p['currency'] ?? null) === 'EUR'
                    && is_numeric($p['price'] ?? null)
                    && ($p['price'] ?? -1) >= 0
                    && !empty($p['date']))
                ->map(function ($p) {
                    $location = $p['location'] ?? [];
                    return [
                        'id' => $p['id'] ?? null,
                        'price' => (float) $p['price'],
                        'currency' => 'EUR',
                        'date' => $p['date'],
                        'price_per' => $p['price_per'] ?? null,
                        'price_is_discounted' => (bool) ($p['price_is_discounted'] ?? false),
                        'price_without_discount' => $p['price_without_discount'] ?? null,
                        'discount_type' => $p['discount_type'] ?? null,
                        'store_name' => $location['osm_name'] ?? $location['osm_brand'] ?? $location['osm_display_name'] ?? null,
                        'store_city' => $location['osm_address_city'] ?? null,
                        'store_postcode' => $location['osm_address_postcode'] ?? null,
                        'store_country' => $location['osm_address_country_code'] ?? null,
                        'store_latitude' => isset($location['osm_lat']) ? (float) $location['osm_lat'] : null,
                        'store_longitude' => isset($location['osm_lon']) ? (float) $location['osm_lon'] : null,
                        'location_osm_id' => $p['location_osm_id'] ?? null,
                        'location_osm_type' => $p['location_osm_type'] ?? null,
                        'proof_id' => $p['proof_id'] ?? null,
                        'source' => 'Open Prices',
                    ];
                })->values()->all();

            return [
                'gtin' => $gtin,
                'prices' => $items,
                'count' => count($items),
                'fetched_at' => now()->toIso8601String(),
                'partial' => ($response->json('pages', 1) > 1),
            ];
        });
    }
}
