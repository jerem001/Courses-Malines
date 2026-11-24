<?php

namespace App\Services\Prices;

use App\Models\Product;
use App\Models\Store;
use App\Services\PriceObservationService;
use Illuminate\Support\Facades\DB;

class PriceCollector
{
    public function __construct(private PriceObservationService $observations) {}

    public function collect(PriceConnector $connector, Product $product, Store $store): array
    {
        if (!$connector->supports($store)) {
            throw new \InvalidArgumentException('Unsupported store for connector.');
        }

        $rows = $connector->collect($product, $store);

        return DB::transaction(function () use ($rows, $product, $store) {
            $ids = [];
            foreach ($rows as $row) {
                $row['product_id'] = $product->id;
                $row['store_id'] = $store->id;
                $row['user_id'] = null;
                if (!in_array($row['source'] ?? null, ['open_prices', 'drive'], true)) {
                    throw new \InvalidArgumentException('Unsupported public price source.');
                }
                $ids[] = $this->observations->record($row)->id;
            }
            return array_values(array_unique($ids));
        });
    }
}
