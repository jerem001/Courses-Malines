<?php

namespace App\Services\Prices\Carrefour;

use App\Models\Product;
use App\Services\PriceObservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CarrefourImportService
{
    public function __construct(
        private CarrefourPriceParser $parser,
        private CarrefourStoreResolver $stores,
        private PriceObservationService $observations,
    ) {}

    public function import(array $payload, string $serviceId, int $userId, string $observedAt): array
    {
        if ($userId <= 0) {
            throw ValidationException::withMessages(['user_id' => 'Utilisateur requis.']);
        }
        $store = $this->stores->resolve($serviceId);
        $rows = $this->parser->parse($payload, $serviceId);

        return DB::transaction(function () use ($store, $rows, $userId, $observedAt) {
            $ids = [];
            $unknown = [];
            foreach ($rows as $row) {
                $product = Product::query()->where('gtin', $row['gtin'])->first();
                if (!$product) {
                    $unknown[] = $row['gtin'];
                    continue;
                }

                $observation = $this->observations->record([
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                    'user_id' => $userId,
                    'source' => 'drive',
                    'source_reference' => 'carrefour:'.$row['service_id'].':'.$row['gtin'],
                    'amount_minor' => $row['amount_minor'],
                    'currency' => 'EUR',
                    'price_basis' => 'item',
                    'promotion' => is_array($row['promotions']) ? $row['promotions'] : [],
                    'observed_at' => $observedAt,
                    'confidence' => 'observed',
                ]);
                $ids[] = $observation->id;
            }

            return [
                'store_id' => $store->id,
                'observation_ids' => array_values(array_unique($ids)),
                'unknown_gtins' => $unknown,
            ];
        });
    }
}
