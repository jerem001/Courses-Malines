<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Product;
use App\Services\PriceObservationService;
use App\Models\UserPreference;
use App\Services\OpenPricesService;
use App\Services\StoreDiscoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PriceController extends Controller
{
    public function product(Request $request, OpenPricesService $prices, StoreDiscoveryService $discovery, PriceObservationService $observations): JsonResponse
    {
        $data = $request->validate([
            'gtin' => ['required', 'regex:/^\\d{8,14}$/'],
        ]);

        try {
            $result = $prices->forProduct($data['gtin']);
            $preferences = UserPreference::where('user_id', $request->user()->id)->first();
            $radius = $preferences?->radius_km ?? 15;
            $mode = $preferences?->search_mode ?? 'both';

            $favoriteIds = DB::table('favorite_stores')
                ->where('user_id', $request->user()->id)
                ->pluck('store_id')->all();
            $stores = Store::with('brand')->get();
            $favorites = $stores->whereIn('id', $favoriteIds);
            // Until a geocoded home/search location exists, favorite stores are the
            // only known geographic anchors. Never silently assume the user's city center.
            $anchors = $favorites->filter(fn ($s) => $s->latitude !== null && $s->longitude !== null);
            $center = ($preferences?->city || $preferences?->postal_code)
                ? $discovery->center($preferences?->city, $preferences?->postal_code)
                : null;
            $product = Product::where('gtin', $data['gtin'])->first();
            $filtered = collect($result['prices'])->map(function ($price) use ($stores, $favoriteIds, $anchors, $center, $radius, $mode, $product, $observations) {
                $lat = $price['store_latitude'] ?? null;
                $lon = $price['store_longitude'] ?? null;
                $nearest = null;
                if ($lat !== null && $lon !== null && $center !== null) {
                    $nearest = $this->distanceKm((float) $lat, (float) $lon, $center['latitude'], $center['longitude']);
                } elseif ($lat !== null && $lon !== null && $anchors->isNotEmpty()) {
                    $nearest = $anchors->map(fn ($store) => $this->distanceKm(
                        (float) $lat, (float) $lon, (float) $store->latitude, (float) $store->longitude
                    ))->min();
                }

                // Match only when an OSM identifier explicitly identifies the store.
                // Brand/name/postcode alone are not a reliable store identifier.
                $matched = $stores->first(function ($store) use ($price) {
                    $type = strtoupper((string) ($price['location_osm_type'] ?? ''));
                    $id = (string) ($price['location_osm_id'] ?? '');
                    return in_array($type, ['NODE', 'WAY', 'RELATION'], true)
                        && ctype_digit($id)
                        && strtoupper((string) $store->external_id) === $type . '/' . $id;
                });
                $isFavorite = $matched && in_array($matched->id, $favoriteIds);
                $price['matched_store_id'] = $matched?->id;
                $price['matched_store_name'] = $matched?->name;
                $price['is_favorite_store'] = (bool) $isFavorite;
                $price['distance_to_nearest_favorite_km'] = $nearest === null ? null : round($nearest, 1);
                $price['within_radius_of_favorite'] = $nearest === null ? null : $nearest <= $radius;
                $price['in_selected_scope'] = match ($mode) {
                    'favorites' => (bool) $isFavorite,
                    'radius' => $nearest !== null && $nearest <= $radius,
                    default => (bool) $isFavorite || ($nearest !== null && $nearest <= $radius),
                };
                $price['observation_id'] = null;
                $basis = match (strtoupper((string) ($price['price_per'] ?? ''))) {
                    '', 'UNIT', 'ITEM' => 'item',
                    'KILOGRAM', 'KG' => 'kg',
                    'LITER', 'LITRE', 'L' => 'l',
                    default => null,
                };
                if ($product && $matched && $basis !== null && $price['id'] !== null) {
                    $observation = $observations->record([
                        'product_id' => $product->id,
                        'store_id' => $matched->id,
                        'source' => 'open_prices',
                        'source_reference' => (string) $price['id'],
                        'amount_minor' => (int) round($price['price'] * 100),
                        'currency' => 'EUR',
                        'price_basis' => $basis,
                        'regular_amount_minor' => is_numeric($price['price_without_discount'])
                            ? (int) round($price['price_without_discount'] * 100) : null,
                        'promotion' => $price['price_is_discounted']
                            ? ['discount_type' => $price['discount_type']] : null,
                        'observed_at' => $price['date'],
                        'confidence' => 'observed',
                    ]);
                    $price['observation_id'] = $observation->id;
                }
                return $price;
            });

            $result['prices'] = $filtered->values()->all();
            $result['count'] = $filtered->count();
            $result['scope_count'] = $filtered->where('in_selected_scope', true)->count();
            $result['scope'] = [
                'mode' => $mode,
                'radius_km' => $radius,
                'reference' => $center !== null ? 'commune' : ($anchors->isNotEmpty() ? 'favorite_stores' : null),
                'favorite_stores_count' => count($favoriteIds),
                'geographic_filter_available' => $center !== null || $anchors->isNotEmpty(),
                'note' => $center !== null ? 'Rayon autour du centre de la commune.' : 'Rayon autour des magasins favoris geolocalises.',
            ];
            return response()->json($result);
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Open Prices request failed', [
                'gtin' => $data['gtin'],
                'exception' => get_class($exception),
                'upstream_status' => $exception instanceof RequestException ? $exception->response->status() : null,
                'error' => $exception->getMessage(),
            ]);
            return response()->json(['message' => 'Open Prices est temporairement indisponible.'], 503);
        }
    }

    private function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;
        return 6371 * 2 * asin(min(1.0, sqrt($a)));
    }
}
