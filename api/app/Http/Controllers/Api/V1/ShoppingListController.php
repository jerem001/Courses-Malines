<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ShoppingListController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->shoppingLists()
            ->with('items.product')->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $list = $request->user()->shoppingLists()->create($data);

        return response()->json($list->load('items'), 201);
    }

    public function show(Request $request, ShoppingList $shoppingList): JsonResponse
    {
        $this->ensureOwner($request, $shoppingList);

        return response()->json($shoppingList->load('items.product'));
    }

    public function update(Request $request, ShoppingList $shoppingList): JsonResponse
    {
        $this->ensureOwner($request, $shoppingList);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
        ]);
        $shoppingList->update($data);

        return response()->json($shoppingList->load('items.product'));
    }

    public function destroy(Request $request, ShoppingList $shoppingList): JsonResponse
    {
        $this->ensureOwner($request, $shoppingList);
        $shoppingList->delete();

        return response()->json(null, 204);
    }

    public function duplicate(Request $request, ShoppingList $shoppingList): JsonResponse
    {
        $this->ensureOwner($request, $shoppingList);
        $copy = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $shoppingList) {
            $copy = $request->user()->shoppingLists()->create([
                'name' => $shoppingList->name . ' (copie)',
                'description' => $shoppingList->description,
                'status' => 'active',
            ]);
            foreach ($shoppingList->items as $item) {
                $copy->items()->create([
                    'product_id' => $item->product_id,
                    'custom_name' => $item->custom_name,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'position' => $item->position,
                    'is_checked' => false,
                ]);
            }
            return $copy;
        });

        return response()->json($copy->load('items.product'), 201);
    }

    public function addItem(Request $request, ShoppingList $shoppingList): JsonResponse
    {
        $this->ensureOwner($request, $shoppingList);
        $data = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id', 'required_without:custom_name'],
            'custom_name' => ['nullable', 'string', 'max:255', 'required_without:product_id'],
            'quantity' => ['sometimes', 'required', 'numeric', 'gt:0', 'max:9999999'],
            'unit' => ['sometimes', Rule::in(['unit', 'g', 'kg', 'ml', 'l'])],
            'position' => ['sometimes', 'integer', 'min:0'],
        ]);
        $item = $shoppingList->items()->create($data);

        return response()->json($item->load('product'), 201);
    }

    public function updateItem(Request $request, ShoppingList $shoppingList, ShoppingListItem $item): JsonResponse
    {
        $this->ensureItemOwner($request, $shoppingList, $item);
        $data = $request->validate([
            'product_id' => ['sometimes', 'nullable', 'integer', 'exists:products,id'],
            'custom_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'quantity' => ['sometimes', 'required', 'numeric', 'gt:0', 'max:9999999'],
            'unit' => ['sometimes', Rule::in(['unit', 'g', 'kg', 'ml', 'l'])],
            'is_checked' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ]);
        if (array_key_exists('product_id', $data) || array_key_exists('custom_name', $data)) {
            $productId = $data['product_id'] ?? $item->product_id;
            $customName = $data['custom_name'] ?? $item->custom_name;
            if ($productId === null && ($customName === null || trim($customName) === '')) {
                return response()->json(['message' => 'Un produit ou un nom est obligatoire.'], 422);
            }
        }
        $item->update($data);

        return response()->json($item->load('product'));
    }

    public function deleteItem(Request $request, ShoppingList $shoppingList, ShoppingListItem $item): JsonResponse
    {
        $this->ensureItemOwner($request, $shoppingList, $item);
        $item->delete();

        return response()->json(null, 204);
    }

    private function ensureOwner(Request $request, ShoppingList $shoppingList): void
    {
        abort_unless($shoppingList->user_id === $request->user()->id, 404);
    }

    private function ensureItemOwner(Request $request, ShoppingList $shoppingList, ShoppingListItem $item): void
    {
        $this->ensureOwner($request, $shoppingList);
        abort_unless($item->shopping_list_id === $shoppingList->id, 404);
    }
}
