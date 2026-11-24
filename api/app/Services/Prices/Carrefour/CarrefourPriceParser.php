<?php

namespace App\Services\Prices\Carrefour;

use InvalidArgumentException;

final class CarrefourPriceParser
{
    public function parse(array $payload, string $serviceId): array
    {
        $products = $payload['data']['products'] ?? null;
        if (!is_array($products) || $serviceId === '') {
            throw new InvalidArgumentException('Invalid Carrefour payload or service identifier.');
        }

        $rows = [];
        foreach ($products as $product) {
            $a = $product['attributes'] ?? [];
            $gtin = $a['ean'] ?? null;
            if (!is_string($gtin) || !preg_match('/^\d{13}$/D', $gtin)) {
                continue;
            }

            $offer = $a['offers'][$gtin][$serviceId]['attributes'] ?? [];
            $price = $offer['price']['price'] ?? null;
            if (($offer['ean'] ?? null) !== $gtin
                || ($offer['offerServiceId'] ?? null) !== $serviceId
                || ($offer['availability']['purchasable'] ?? false) !== true
                || ($offer['variableWeight'] ?? true) !== false
                || (!is_int($price) && !is_float($price))
                || !is_finite((float) $price) || $price < 0 || $price > 1000000) {
                continue;
            }

            $rows[$gtin] = [
                'gtin' => $gtin,
                'name' => $a['title'] ?? null,
                'brand' => $a['brand'] ?? null,
                'packaging' => $a['packaging'] ?? null,
                'service_id' => $serviceId,
                'amount_minor' => (int) round($price * 100),
                'currency' => 'EUR',
                'price_basis' => 'item',
                'unit_price' => $offer['price']['perUnit'] ?? null,
                'unit_of_measure' => $offer['price']['unitOfMeasure'] ?? null,
                'promotions' => $offer['promotions'] ?? [],
            ];
        }

        return array_values($rows);
    }
}
