<?php

namespace App\Services;

use App\Models\PriceObservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PriceObservationService
{
    public function record(array $input): PriceObservation
    {
        $data = Validator::make($input, [
            'product_id' => 'required|integer|exists:products,id',
            'store_id' => 'required|integer|exists:stores,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'source' => ['required', Rule::in(['open_prices', 'drive', 'receipt', 'manual'])],
            'source_reference' => 'nullable|string|max:255',
            'amount_minor' => 'required|integer|min:0|max:100000000',
            'currency' => 'required|in:EUR',
            'price_basis' => 'required|in:item,kg,l',
            'regular_amount_minor' => 'nullable|integer|min:0|max:100000000',
            'promotion' => 'nullable|array',
            'observed_at' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:observed_at',
            'confidence' => 'required|in:observed,estimated',
        ])->validate();
        $data['observed_at'] = Carbon::parse($data['observed_at'])->utc()->toIso8601String();
        if (!empty($data['valid_until'])) {
            $data['valid_until'] = Carbon::parse($data['valid_until'])->utc()->toIso8601String();
        }
        $data['user_id'] = $data['user_id'] ?? null;
        $data['source_reference'] = $data['source_reference'] ?? null;
        if (in_array($data['source'], ['receipt', 'manual'], true) && $data['user_id'] === null) {
            throw \Illuminate\Validation\ValidationException::withMessages(['user_id' => 'Un relevé personnel doit appartenir à un utilisateur.']);
        }
        $identity = $data;
        ksort($identity);
        $fingerprint = hash('sha256', json_encode($identity, JSON_THROW_ON_ERROR));
        return PriceObservation::firstOrCreate(['fingerprint' => $fingerprint], $data);
    }
}
