<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\PriceObservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CarrefourImportTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/prices/import/carrefour';

    private function payload(): array
    {
        $gtin = '3017620422003';
        $service = '8138-150-1046';
        return [
            'service_id' => $service,
            'observed_at' => now()->subMinute()->toIso8601String(),
            'payload' => ['data' => ['products' => [['attributes' => [
                'ean' => $gtin,
                'offers' => [$gtin => [$service => ['attributes' => [
                    'ean' => $gtin, 'offerServiceId' => $service,
                    'availability' => ['purchasable' => true],
                    'variableWeight' => false,
                    'price' => ['price' => 3.69],
                    'promotions' => [],
                ]]]],
            ]]]]],
        ];
    }

    public function test_authentication_is_required(): void
    {
        $this->postJson(self::URL, $this->payload())->assertUnauthorized();
    }

    public function test_unmapped_service_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson(self::URL, $this->payload())
            ->assertUnprocessable()->assertJsonValidationErrors('service_id');
        $this->assertDatabaseCount('price_observations', 0);
    }

    public function test_import_is_private_and_idempotent(): void
    {
        $brand = Brand::create(['name' => 'Carrefour', 'slug' => 'carrefour']);
        $store = Store::create(['brand_id' => $brand->id, 'name' => 'Market', 'external_id' => 'WAY/123']);
        DB::table('store_source_identifiers')->insert([
            'store_id' => $store->id, 'connector' => 'carrefour',
            'external_id' => '8138-150-1046', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $product = Product::create(['name' => 'Nutella', 'gtin' => '3017620422003', 'source' => 'openfoodfacts']);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($owner);
        $payload = $this->payload();
        $this->postJson(self::URL, $payload + ['user_id' => $other->id])
            ->assertUnprocessable()->assertJsonValidationErrors('user_id');
        $first = $this->postJson(self::URL, $payload)->assertOk()
            ->assertJsonPath('store_id', $store->id)->json('observation_ids');
        $this->assertCount(1, $first);
        $this->postJson(self::URL, $payload)->assertOk()->assertJsonPath('observation_ids', $first);
        $this->assertDatabaseCount('price_observations', 1);
        $this->assertDatabaseHas('price_observations', [
            'product_id' => $product->id, 'store_id' => $store->id,
            'user_id' => $owner->id, 'amount_minor' => 369, 'source' => 'drive',
        ]);
        $this->assertSame(0, PriceObservation::visibleTo($other->id)->count());
        $this->assertSame(1, PriceObservation::visibleTo($owner->id)->count());
        $product->delete();
        $this->postJson(self::URL, $payload)->assertOk()
            ->assertJsonPath('unknown_gtins', ['3017620422003'])
            ->assertJsonPath('observation_ids', []);
    }
}
