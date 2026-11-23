<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class StoreDiscoveryService
{
    public function center(?string $city, ?string $postalCode): ?array
    {
        $city = trim($city ?: '');
        $postalCode = trim($postalCode ?: '');
        if ($city === '' && $postalCode === '') {
            return null;
        }
        return Cache::remember('geo:commune:' . sha1($city . '|' . $postalCode), now()->addDays(7), function () use ($city, $postalCode) {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(12)
                ->get('https://geo.api.gouv.fr/communes', array_filter([
                    'nom' => $city ?: null,
                    'codePostal' => $postalCode ?: null,
                    'fields' => 'nom,centre,codesPostaux',
                    'format' => 'json',
                    'limit' => 10,
                ], fn ($value) => $value !== null));
            $response->throw();
            foreach ($response->json() ?: [] as $commune) {
                $coords = $commune['centre']['coordinates'] ?? null;
                if (is_array($coords) && count($coords) === 2 && is_numeric($coords[0]) && is_numeric($coords[1])) {
                    return ['latitude' => (float) $coords[1], 'longitude' => (float) $coords[0], 'city' => $commune['nom'] ?? $city];
                }
            }
            return null;
        });
    }

    public function discover(float $latitude, float $longitude, int $radiusKm): array
    {
        $radiusMeters = $radiusKm * 1000;
        $query = '[out:json][timeout:25];(nwr["shop"="supermarket"](around:' . $radiusMeters . ',' . $latitude . ',' . $longitude . '););out center tags;';
        $response = null;
        $failures = [];
        foreach (['https://overpass.kumi.systems/api/interpreter', 'https://overpass.nchc.org.tw/api/interpreter', 'https://overpass-api.de/api/interpreter'] as $endpoint) {
            try {
                $candidate = Http::asForm()->acceptJson()->connectTimeout(4)->timeout(18)
                    ->post($endpoint, ['data' => $query]);
                if ($candidate->successful() && is_array($candidate->json('elements'))) {
                    $response = $candidate;
                    break;
                }
                $failures[] = parse_url($endpoint, PHP_URL_HOST) . ': HTTP ' . $candidate->status();
            } catch (ConnectionException $exception) {
                $failures[] = parse_url($endpoint, PHP_URL_HOST) . ': ' . $exception->getMessage();
            }
        }
        if ($response === null) {
            Log::warning('All Overpass endpoints failed', ['failures' => $failures]);
            throw new ConnectionException('Aucun serveur Overpass accessible : ' . implode('; ', $failures));
        }
        return collect($response->json('elements', []))
            ->filter(fn ($item) => isset($item['id'], $item['type']))
            ->map(function ($item) {
                $tags = $item['tags'] ?? [];
                $label = trim(($tags['brand'] ?? '') . ' ' . ($tags['name'] ?? ''));
                $normalized = mb_strtolower($label);
                $brand = null;
                foreach (['Leclerc' => ['leclerc', 'e.leclerc'], 'Intermarché' => ['intermarché', 'intermarche'], 'Carrefour' => ['carrefour']] as $name => $needles) {
                    foreach ($needles as $needle) {
                        if (str_contains($normalized, $needle)) {
                            $brand = $name;
                            break 2;
                        }
                    }
                }
                if ($brand === null) {
                    return null;
                }
                $lat = $item['lat'] ?? $item['center']['lat'] ?? null;
                $lon = $item['lon'] ?? $item['center']['lon'] ?? null;
                if (!is_numeric($lat) || !is_numeric($lon)) {
                    return null;
                }
                return [
                    'brand' => $brand,
                    'external_id' => strtoupper($item['type']) . '/' . $item['id'],
                    'name' => $tags['name'] ?? $brand,
                    'address' => trim(($tags['addr:housenumber'] ?? '') . ' ' . ($tags['addr:street'] ?? '')),
                    'postal_code' => $tags['addr:postcode'] ?? null,
                    'city' => $tags['addr:city'] ?? null,
                    'latitude' => (float) $lat,
                    'longitude' => (float) $lon,
                ];
            })->filter()->values()->all();
    }
}
