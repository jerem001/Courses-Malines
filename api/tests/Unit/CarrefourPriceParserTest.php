<?php

namespace Tests\Unit;

use App\Services\Prices\Carrefour\CarrefourPriceParser;
use PHPUnit\Framework\TestCase;

final class CarrefourPriceParserTest extends TestCase
{
    private function product(string $gtin = '0000059032823', array $overrides = []): array
    {
        $service = '8138-150-1046';
        return ['attributes' => [
            'ean' => $gtin,
            'title' => 'Nutella',
            'packaging' => '630g',
            'offers' => [$gtin => [$service => ['attributes' => array_replace([
                'ean' => $gtin,
                'offerServiceId' => $service,
                'availability' => ['purchasable' => true],
                'variableWeight' => false,
                'price' => ['price' => 5.35, 'perUnit' => 8.49, 'unitOfMeasure' => 'KG'],
                'promotions' => [['label' => 'Offre fidélité', 'isLoyalty' => true]],
            ], $overrides)]]],
        ]];
    }

    public function test_normalizes_prices_and_preserves_gtin_and_promotions(): void
    {
        $product = $this->product();
        $rows = (new CarrefourPriceParser())->parse(
            ['data' => ['products' => [$product, $product]]], '8138-150-1046'
        );
        self::assertCount(1, $rows);
        self::assertSame('0000059032823', $rows[0]['gtin']);
        self::assertSame(535, $rows[0]['amount_minor']);
        self::assertSame('item', $rows[0]['price_basis']);
        self::assertSame('EUR', $rows[0]['currency']);
        self::assertSame(8.49, $rows[0]['unit_price']);
        self::assertTrue($rows[0]['promotions'][0]['isLoyalty']);
    }

    public function test_rejects_wrong_service_and_unusable_offers(): void
    {
        $parser = new CarrefourPriceParser();
        self::assertSame([], $parser->parse(
            ['data' => ['products' => [$this->product()]]], '9999-150-9999'
        ));
        $products = [
            $this->product(overrides: ['availability' => ['purchasable' => false]]),
            $this->product(overrides: ['variableWeight' => true]),
            $this->product(overrides: ['price' => ['price' => -1]]),
            $this->product(overrides: ['offerServiceId' => 'other']),
            $this->product(overrides: ['ean' => '3017620422003']),
        ];
        self::assertSame([], $parser->parse(['data' => ['products' => $products]], '8138-150-1046'));
    }

    public function test_rejects_invalid_payload(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new CarrefourPriceParser())->parse([], '8138-150-1046');
    }
}
