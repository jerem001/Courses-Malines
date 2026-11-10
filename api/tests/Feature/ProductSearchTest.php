<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_and_import_open_food_facts_product(): void
    {
        Http::fake([
            'world.openfoodfacts.org/*' => Http::response([
                'status' => 1,
                'product' => [
                    'code' => '3017620422003',
                    'product_name_fr' => 'Pâte à tartiner',
                    'brands' => 'Marque test',
                    'nutriscore_grade' => 'e',
                    'nova_group' => 4,
                    'allergens_tags' => ['en:milk'],
                    'nutriments' => ['energy-kcal_100g' => 530],
                ],
            ]),
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum');
        $this->getJson('/api/v1/products/search?q=3017620422003')
            ->assertOk()->assertJsonPath('external.0.name', 'Pâte à tartiner');

        $id = $this->postJson('/api/v1/products/import', ['gtin' => '3017620422003'])
            ->assertCreated()->assertJsonPath('source', 'openfoodfacts')->json('id');

        $this->postJson('/api/v1/products/import', ['gtin' => '3017620422003'])
            ->assertOk()->assertJsonPath('id', $id);

        $list = $this->postJson('/api/v1/shopping-lists', ['name' => 'Courses'])
            ->assertCreated()->json('id');
        $this->postJson("/api/v1/shopping-lists/{$list}/items", ['product_id' => $id])
            ->assertCreated()->assertJsonPath('product.name', 'Pâte à tartiner');
    }

    public function test_search_requires_authentication_and_valid_input(): void
    {
        $this->getJson('/api/v1/products/search?q=lait')->assertUnauthorized();
        $this->actingAs(User::factory()->create(), 'sanctum');
        $this->getJson('/api/v1/products/search?q=x')->assertUnprocessable();
        $this->postJson('/api/v1/products/import', ['gtin' => 'abc'])->assertUnprocessable();
    }
}
