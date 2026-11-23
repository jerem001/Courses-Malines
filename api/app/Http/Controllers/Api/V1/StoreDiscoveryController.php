<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Store;
use App\Models\UserPreference;
use App\Services\StoreDiscoveryService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreDiscoveryController extends Controller
{
    public function import(Request $request, StoreDiscoveryService $discovery): JsonResponse
    {
        $preferences = UserPreference::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['city' => 'Le Mans', 'postal_code' => '72000', 'radius_km' => 15, 'search_mode' => 'both']
        );
        if (!$preferences->city && !$preferences->postal_code) {
            return response()->json(['message' => 'Renseigne une commune ou un code postal dans les préférences.'], 422);
        }

        try {
            $center = $discovery->center($preferences->city, $preferences->postal_code);
            if (!$center) {
                return response()->json(['message' => 'Commune introuvable. Vérifie tes préférences.'], 422);
            }
            $found = $discovery->discover($center['latitude'], $center['longitude'], (int) $preferences->radius_km);
            $saved = DB::transaction(function () use ($found) {
                $count = 0;
                foreach ($found as $entry) {
                    $brandName = $entry['brand'];
                    unset($entry['brand']);
                    $brand = Brand::firstOrCreate(
                        ['slug' => match ($brandName) {
                            'Leclerc' => 'leclerc',
                            'Intermarché' => 'intermarche',
                            default => 'carrefour',
                        }],
                        ['name' => $brandName]
                    );
                    Store::updateOrCreate(
                        ['external_id' => $entry['external_id']],
                        array_merge($entry, ['brand_id' => $brand->id])
                    );
                    $count++;
                }
                return $count;
            });
            return response()->json(['imported' => $saved, 'center' => $center, 'radius_km' => $preferences->radius_km]);
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Store discovery failed', ['error' => $exception->getMessage()]);
            return response()->json(['message' => 'Service cartographique temporairement indisponible.'], 503);
        }
    }
}
