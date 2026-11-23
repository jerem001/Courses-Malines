<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StoreDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_requires_authentication(): void
    {
        $this->postJson('/api/v1/stores/import')->assertUnauthorized();
    }

    public function test_imports_nearby_osm_stores_without_duplicates(): void
    {
        Http::fake([
            'geo.api.gouv.fr/*' => Http::response([
                ['nom' => 'Le Mans', 'centre' => ['coordinates' => [0.1996, 48.0061]]],
            ]),
            'overpass-api.de/*' => Http::response(['elements' => [
                ['type' => 'node', 'id' => 12345, 'lat' => 48.01, 'lon' => 0.2,
                    'tags' => ['shop' => 'supermarket', 'name' => 'E.Leclerc Test', 'addr:city' => 'Le Mans']],
            ]]),
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum');
        $this->postJson('/api/v1/stores/import')->assertOk()->assertJsonPath('imported', 1);
        $this->postJson('/api/v1/stores/import')->assertOk()->assertJsonPath('imported', 1);
        $this->assertDatabaseCount('stores', 1);
        $this->assertDatabaseHas('stores', ['external_id' => 'NODE/12345']);
    }
}
