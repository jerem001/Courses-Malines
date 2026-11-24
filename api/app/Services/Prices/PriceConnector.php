<?php

namespace App\Services\Prices;

use App\Models\Product;
use App\Models\Store;

interface PriceConnector
{
    public function key(): string;

    public function supports(Store $store): bool;

    /** @return list<array<string, mixed>> Normalized PriceObservationService inputs. */
    public function collect(Product $product, Store $store): array;
}
