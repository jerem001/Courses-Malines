<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteStoreController extends Controller
{
    public function index(Request $request)
    {
        return Store::with('brand')
            ->whereIn('id', DB::table('favorite_stores')
                ->where('user_id', $request->user()->id)
                ->select('store_id'))
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
        ]);

        DB::table('favorite_stores')->insertOrIgnore([
            'user_id' => $request->user()->id,
            'store_id' => $data['store_id'],
        ]);

        return response()->json(['ok' => true], 201);
    }

    public function destroy(Request $request, Store $store)
    {
        DB::table('favorite_stores')
            ->where('user_id', $request->user()->id)
            ->where('store_id', $store->id)
            ->delete();

        return response()->noContent();
    }
}
