<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OpenFoodFactsService
{
    private const BASE = 'https://world.openfoodfacts.org';

    public function lookup(string $gtin): ?array
    {
        $response = Http::acceptJson()->withHeaders([
            'User-Agent' => config('services.openfoodfacts.user_agent', 'CoursesMalines/0.2 (contact: configure-OPENFOODFACTS_USER_AGENT)'),
        ])->timeout(8)->get(self::BASE . '/api/v2/product/' . rawurlencode($gtin) . '.json', [
            'fields' => 'code,product_name,product_name_fr,brands,quantity,image_front_url,nutriscore_grade,nova_group,allergens_tags,nutriments',
        ]);

        if (!$response->successful() || $response->json('status') !== 1) {
            return null;
        }

        return $this->normalize($response->json('product', []), $gtin);
    }

    public function search(string $query): array
    {
        $response = Http::acceptJson()->withHeaders([
            'User-Agent' => config('services.openfoodfacts.user_agent', 'CoursesMalines/0.2 (contact: configure-OPENFOODFACTS_USER_AGENT)'),
        ])->timeout(8)->get(self::BASE . '/cgi/search.pl', [
            'search_terms' => $query,
            'search_simple' => 1,
            'action' => 'process',
            'json' => 1,
            'page_size' => 12,
            'fields' => 'code,product_name,product_name_fr,brands,quantity,image_front_url,nutriscore_grade,nova_group,allergens_tags,nutriments',
        ]);

        if (!$response->successful()) {
            return [];
        }

        return collect($response->json('products', []))
            ->map(fn ($product) => $this->normalize($product))
            ->filter()
            ->unique('gtin')
            ->values()
            ->all();
    }

    public function normalize(array $data, ?string $fallbackCode = null): ?array
    {
        $code = (string) ($data['code'] ?? $fallbackCode ?? '');
        $name = trim((string) (($data['product_name_fr'] ?? null) ?: ($data['product_name'] ?? '')));
        if (!preg_match('/^\d{8,14}$/', $code) || $name === '') {
            return null;
        }

        $grade = strtolower((string) ($data['nutriscore_grade'] ?? ''));
        $nova = (int) ($data['nova_group'] ?? 0);

        return [
            'gtin' => $code,
            'name' => Str::limit($name, 255, ''),
            'brand_name' => Str::limit((string) ($data['brands'] ?? ''), 255, ''),
            'package_label' => (string) ($data['quantity'] ?? ''),
            'image_url' => filter_var($data['image_front_url'] ?? null, FILTER_VALIDATE_URL) ?: null,
            'nutriscore_grade' => in_array($grade, ['a', 'b', 'c', 'd', 'e'], true) ? $grade : null,
            'nova_group' => in_array($nova, [1, 2, 3, 4], true) ? $nova : null,
            'allergens' => $data['allergens_tags'] ?? [],
            'nutrition_data' => $data['nutriments'] ?? [],
        ];
    }

    public function persist(array $data): Product
    {
        return Product::updateOrCreate(['gtin' => $data['gtin']], [
            'name' => $data['name'],
            'brand_name' => $data['brand_name'],
            'image_url' => $data['image_url'],
            'nutriscore_grade' => $data['nutriscore_grade'],
            'nova_group' => $data['nova_group'],
            'allergens' => $data['allergens'],
            'nutrition_data' => $data['nutrition_data'],
            'source' => 'openfoodfacts',
            'source_updated_at' => now(),
        ]);
    }
}
