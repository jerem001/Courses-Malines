<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\OpenFoodFactsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function search(Request $request, OpenFoodFactsService $off): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $query = trim($data['q']);
        $local = Product::query()
            ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(addcslashes($query, '%_\\')) . '%'])
            ->orWhereRaw('LOWER(brand_name) LIKE ?', ['%' . strtolower(addcslashes($query, '%_\\')) . '%'])
            ->orWhere('gtin', $query)
            ->limit(12)->get();

        try {
            $remote = preg_match('/^\d{8,14}$/', $query)
                ? array_filter([$off->lookup($query)])
                : $off->search($query);
            return response()->json(['local' => $local, 'external' => array_values($remote)]);
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            return response()->json(['local' => $local, 'external' => [], 'external_unavailable' => true]);
        }
    }

    public function import(Request $request, OpenFoodFactsService $off): JsonResponse
    {
        $data = $request->validate(['gtin' => ['required', 'regex:/^\d{8,14}$/']]);
        $existing = Product::where('gtin', $data['gtin'])->first();
        if ($existing) {
            return response()->json($existing);
        }

        try {
            $product = $off->lookup($data['gtin']);
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            return response()->json(['message' => 'Open Food Facts est temporairement indisponible.'], 503);
        }

        if (!$product) {
            return response()->json(['message' => 'Produit introuvable dans Open Food Facts.'], 404);
        }

        return response()->json($off->persist($product), 201);
    }
}
