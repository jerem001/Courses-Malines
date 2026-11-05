<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoursesMalinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/v1/preferences')->assertUnauthorized();
    }

    public function test_login_and_me(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk()->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()->assertJsonPath('id', $user->id);
    }

    public function test_preferences_are_isolated_per_user(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice, 'sanctum')
            ->putJson('/api/v1/preferences', [
                'city' => 'Nantes',
                'postal_code' => '44000',
                'radius_km' => 20,
                'search_mode' => 'both',
            ])->assertOk();

        $this->actingAs($bob, 'sanctum')
            ->getJson('/api/v1/preferences')
            ->assertOk()
            ->assertJsonPath('radius_km', 15)
            ->assertJsonPath('city', null);
    }

    public function test_favorites_are_isolated_per_user(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $brand = Brand::create(['name' => 'Leclerc', 'slug' => 'leclerc']);
        $store = Store::create(['brand_id' => $brand->id, 'name' => 'Drive test']);

        $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/favorite-stores', ['store_id' => $store->id])
            ->assertCreated();

        $this->actingAs($bob, 'sanctum')
            ->getJson('/api/v1/favorite-stores')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_public_registration_route_is_not_defined(): void
    {
        $this->postJson('/api/v1/auth/register', [])->assertNotFound();
    }
}
