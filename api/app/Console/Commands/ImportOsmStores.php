<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOsmStores extends Command
{
    protected $signature = 'stores:import-osm {file : Fichier GeoJSON exporté depuis osmium} {--lat=48.0061} {--lon=0.1996} {--radius=15}';
    protected $description = 'Importe les supermarchés OSM proches d’un point depuis un GeoJSON local';

    public function handle(): int
    {
        $path = $this->argument('file');
        $radius = (float) $this->option('radius');
        $lat = (float) $this->option('lat');
        $lon = (float) $this->option('lon');
        if (!is_file($path) || !is_readable($path) || $radius <= 0 || $radius > 100 || abs($lat) > 90 || abs($lon) > 180) {
            $this->error('Fichier inaccessible ou coordonnées/rayon invalides.');
            return self::FAILURE;
        }
        $json = json_decode(file_get_contents($path), true);
        if (!is_array($json) || ($json['type'] ?? null) !== 'FeatureCollection' || !is_array($json['features'] ?? null)) {
            $this->error('GeoJSON FeatureCollection invalide.');
            return self::FAILURE;
        }
        $count = 0;
        DB::transaction(function () use ($json, $lat, $lon, $radius, &$count) {
            foreach ($json['features'] as $feature) {
                $tags = $feature['properties'] ?? [];
                if (($tags['shop'] ?? null) !== 'supermarket') {
                    continue;
                }
                $name = trim(($tags['brand'] ?? '') . ' ' . ($tags['name'] ?? ''));
                $normalized = mb_strtolower($name);
                $brandName = match (true) {
                    str_contains($normalized, 'leclerc') => 'Leclerc',
                    str_contains($normalized, 'intermarché'), str_contains($normalized, 'intermarche') => 'Intermarché',
                    str_contains($normalized, 'carrefour') => 'Carrefour',
                    default => null,
                };
                if ($brandName === null) {
                    continue;
                }
                $id = $tags['@id'] ?? $feature['id'] ?? null;
                if (!is_string($id) || !preg_match('~^(node|way|relation)/([0-9]+)$~i', $id, $matches)) {
                    continue;
                }
                $point = $this->point($feature['geometry'] ?? []);
                if (!$point) {
                    continue;
                }
                [$storeLon, $storeLat] = $point;
                if ($this->distance($lat, $lon, $storeLat, $storeLon) > $radius) {
                    continue;
                }
                $slug = match ($brandName) {
                    'Leclerc' => 'leclerc', 'Intermarché' => 'intermarche', default => 'carrefour',
                };
                $brand = Brand::firstOrCreate(['slug' => $slug], ['name' => $brandName]);
                Store::updateOrCreate(
                    ['external_id' => strtoupper($matches[1]) . '/' . $matches[2]],
                    [
                        'brand_id' => $brand->id,
                        'name' => $tags['name'] ?? $brandName,
                        'address' => trim(($tags['addr:housenumber'] ?? '') . ' ' . ($tags['addr:street'] ?? '')),
                        'postal_code' => $tags['addr:postcode'] ?? null,
                        'city' => $tags['addr:city'] ?? null,
                        'latitude' => $storeLat,
                        'longitude' => $storeLon,
                    ]
                );
                $count++;
            }
        });
        $this->info($count . ' magasins importés ou actualisés dans un rayon de ' . $radius . ' km.');
        return self::SUCCESS;
    }

    private function point(array $geometry): ?array
    {
        $type = $geometry['type'] ?? '';
        $coordinates = $geometry['coordinates'] ?? [];
        if ($type === 'Point' && count($coordinates) >= 2) {
            return [(float) $coordinates[0], (float) $coordinates[1]];
        }
        // Pour les polygones, le centre moyen des sommets est une approximation.
        $ring = match ($type) {
            'Polygon' => $coordinates[0] ?? [],
            'MultiPolygon' => $coordinates[0][0] ?? [],
            default => [],
        };
        if (!is_array($ring) || count($ring) < 3) {
            return null;
        }
        $sumLon = 0.0;
        $sumLat = 0.0;
        $n = 0;
        foreach ($ring as $coordinate) {
            if (is_array($coordinate) && count($coordinate) >= 2) {
                $sumLon += (float) $coordinate[0];
                $sumLat += (float) $coordinate[1];
                $n++;
            }
        }
        return $n ? [$sumLon / $n, $sumLat / $n] : null;
    }

    private function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $a = sin(deg2rad($lat2 - $lat1) / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lon2 - $lon1) / 2) ** 2;
        return 6371 * 2 * asin(min(1, sqrt($a)));
    }
}
