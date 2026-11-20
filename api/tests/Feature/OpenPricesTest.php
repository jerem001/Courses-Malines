<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenPricesTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_prices_are_normalized_and_cached(): void
    {
        Http::fake(['prices.openfoodfacts.org/*' => Http::response([
            'items' => [[
                'id' => 12,
                'product_code' => '3017620422003',
                'price' => 3.49,
                'currency' => 'EUR',
                'date' => '2026-11-18',
                'location' => ['osm_name' => 'Magasin test', 'osm_address_city' => 'Le Mans'],
            ]],
            'pages' => 1,
        ])]);

        $this->actingAs(User::factory()->create(), 'sanctum');
        $url = '/api/v1/prices/product?gtin=3017620422003';
        $this->getJson($url)->assertOk()
            ->assertJsonPath('prices.0.price', 3.49)
            ->assertJsonPath('prices.0.store_name', 'Magasin test')
            ->assertJsonPath('count', 1);
        $this->getJson($url)->assertOk();
        Http::assertSentCount(1);
    }

    public function test_prices_require_authentication_and_valid_gtin(): void
    {
        $this->getJson('/api/v1/prices/product?gtin=3017620422003')->assertUnauthorized();
        $this->actingAs(User::factory()->create(), 'sanctum');
        $this->getJson('/api/v1/prices/product?gtin=invalid')->assertUnprocessable();
    }

    public function test_upstream_failure_returns_service_unavailable(): void
    {
        Http::fake(['prices.openfoodfacts.org/*' => Http::response([], 502)]);
        $this->actingAs(User::factory()->create(), 'sanctum');
        $this->getJson('/api/v1/prices/product?gtin=3017620422003')
            ->assertStatus(503);
    }
}
