<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OpenPricesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class PriceController extends Controller
{
    public function product(Request $request, OpenPricesService $prices): JsonResponse
    {
        $data = $request->validate([
            'gtin' => ['required', 'regex:/^\\d{8,14}$/'],
        ]);

        try {
            return response()->json($prices->forProduct($data['gtin']));
        } catch (ConnectionException|RequestException $exception) {
            return response()->json(['message' => 'Open Prices est temporairement indisponible.'], 503);
        }
    }
}
