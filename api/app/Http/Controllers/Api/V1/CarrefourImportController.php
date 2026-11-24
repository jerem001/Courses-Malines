<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Prices\Carrefour\CarrefourImportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class CarrefourImportController extends Controller
{
    public function store(Request $request, CarrefourImportService $import)
    {
        $data = $request->validate([
            'service_id' => 'required|string|max:100',
            'observed_at' => 'required|date|before_or_equal:now',
            'payload' => 'required|array',
            'payload.data.products' => 'present|array|max:100',
            'payload.data.products.*' => 'array',
            'payload.data.products.*.attributes' => 'required|array',
            'user_id' => 'prohibited',
        ]);

        if (strlen($request->getContent()) > 2000000) {
            throw ValidationException::withMessages(['payload' => 'Import trop volumineux.']);
        }

        return response()->json($import->import(
            $data['payload'], $data['service_id'],
            (int) $request->user()->id, $data['observed_at']
        ));
    }
}
