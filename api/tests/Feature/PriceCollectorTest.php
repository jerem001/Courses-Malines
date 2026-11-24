<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Store;
use App\Services\Prices\PriceCollector;
use App\Services\Prices\PriceConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceCollectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_collector_persists_normalized_prices_idempotently(): void
    {
        $brand = Brand::create(['name' => 'Test', 'slug' => 'test']);
        $store = Store::create(['brand_id' => $brand->id, 'name' => 'Test store', 'external_id' => 'WAY/123']);
        $product = Product::create(['name' => 'Test product', 'gtin' => '3017620422003', 'source' => 'openfoodfacts']);
        $connector = new class implements PriceConnector {
            public function key(): string { return 'test'; }
            public function supports(Store $store): bool { return true; }
            public function collect(Product $product, Store $store): array
            {
                return [[
                    'source' => 'drive', 'source_reference' => 'test:123',
                    'amount_minor' => 299, 'currency' => 'EUR',
                    'price_basis' => 'item', 'observed_at' => '2026-11-24',
                    'confidence' => 'observed',
                ]];
            }
        };
        $collector = app(PriceCollector::class);
        $first = $collector->collect($connector, $product, $store);
        $this->assertCount(1, $first);
        $this->assertSame($first, $collector->collect($connector, $product, $store));
        $this->assertDatabaseCount('price_observations', 1);
        $this->assertDatabaseHas('price_observations', [
            'product_id' => $product->id, 'store_id' => $store->id,
            'amount_minor' => 299, 'source' => 'drive', 'user_id' => null,
        ]);
    }
}
