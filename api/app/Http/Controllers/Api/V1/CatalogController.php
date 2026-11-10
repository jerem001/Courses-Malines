<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Store;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function brands()
    {
        return Brand::orderBy('name')->get();
    }

    public function stores(Request $request)
    {
        $data = $request->validate([
            'brand_id' => 'sometimes|integer|exists:brands,id',
        ]);

        return Store::with('brand')
            ->when(isset($data['brand_id']), fn ($q) => $q->where('brand_id', $data['brand_id']))
            ->orderBy('name')
            ->paginate(25);
    }
}
