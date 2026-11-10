<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShoppingListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_require_authentication(): void
    {
        $this->getJson('/api/v1/shopping-lists')->assertUnauthorized();
    }

    public function test_user_can_create_edit_and_duplicate_a_list(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        $id = $this->postJson('/api/v1/shopping-lists', ['name' => 'Courses semaine'])
            ->assertCreated()->json('id');

        $itemId = $this->postJson("/api/v1/shopping-lists/{$id}/items", [
            'custom_name' => 'Pommes', 'quantity' => 2, 'unit' => 'kg',
        ])->assertCreated()->json('id');

        $this->patchJson("/api/v1/shopping-lists/{$id}/items/{$itemId}", [
            'is_checked' => true,
        ])->assertOk()->assertJsonPath('is_checked', true);

        $this->getJson("/api/v1/shopping-lists/{$id}")
            ->assertOk()->assertJsonPath('items.0.custom_name', 'Pommes');

        $this->postJson("/api/v1/shopping-lists/{$id}/duplicate")
            ->assertCreated()->assertJsonPath('items.0.is_checked', false);

        $this->getJson('/api/v1/shopping-lists')->assertOk()->assertJsonCount(2);
    }

    public function test_lists_and_items_are_isolated_between_users(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $this->actingAs($alice, 'sanctum');
        $id = $this->postJson('/api/v1/shopping-lists', ['name' => 'Privée'])
            ->assertCreated()->json('id');
        $itemId = $this->postJson("/api/v1/shopping-lists/{$id}/items", [
            'custom_name' => 'Lait',
        ])->assertCreated()->json('id');

        $this->actingAs($bob, 'sanctum');
        $this->getJson('/api/v1/shopping-lists')->assertOk()->assertExactJson([]);
        $this->getJson("/api/v1/shopping-lists/{$id}")->assertNotFound();
        $this->putJson("/api/v1/shopping-lists/{$id}", ['name' => 'Volée'])->assertNotFound();
        $this->deleteJson("/api/v1/shopping-lists/{$id}")->assertNotFound();
        $this->patchJson("/api/v1/shopping-lists/{$id}/items/{$itemId}", [
            'is_checked' => true,
        ])->assertNotFound();
    }

    public function test_invalid_quantities_and_missing_item_names_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        $id = $this->postJson('/api/v1/shopping-lists', ['name' => 'Test'])
            ->assertCreated()->json('id');
        $this->postJson("/api/v1/shopping-lists/{$id}/items", [
            'custom_name' => 'Farine', 'quantity' => -1,
        ])->assertUnprocessable();
        $this->postJson("/api/v1/shopping-lists/{$id}/items", [
            'quantity' => 1,
        ])->assertUnprocessable();
    }
}
