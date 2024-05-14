<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Shopware6Common\Export\Adapters\DefaultPropertiesAdapter;
use FINDOLOGIC\Shopware6Common\Tests\CommonConstants;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\PropertiesHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DefaultPropertiesAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;
    use PropertiesHelper;

    public DefaultPropertiesAdapter $defaultPropertiesAdapter;

    public function setUp(): void
    {
        $this->defaultPropertiesAdapter = $this->getDefaultPropertiesAdapter();
    }

    public function testPropertiesContainsThePropertiesOfTheProduct(): void
    {
        $product = $this->createTestProduct([
            'weight' => 50,
            'width' => 8,
            'height' => 8,
            'length' => 20,
        ]);

        $expectedProperties = $this->getProperties($product);
        $actualProperties = $this->defaultPropertiesAdapter->adapt($product);

        $this->assertEquals($expectedProperties, $actualProperties);
    }

    #[DataProvider('productPromotionProvider')]
    public function testProductPromotionIsExported(?bool $markAsTopSeller, string $expected): void
    {
        $productEntity = $this->createTestProduct(
            ['markAsTopseller' => $markAsTopSeller],
            true,
        );
        $properties = $this->defaultPropertiesAdapter->adapt($productEntity);

        $promotion = end($properties);
        $values = $promotion->getAllValues();

        $this->assertNotNull($promotion);
        $this->assertSame('product_promotion', $promotion->getKey());

        $this->assertNotEmpty($values);
        $this->assertSame($expected, current($values));
    }

    #[DataProvider('listPriceProvider')]
    public function testProductListPrice(?string $currencyId, bool $isPriceAvailable): void
    {
        $productEntity = $this->createTestProduct(
            [
                'price' => [
                    [
                        'currencyId' => $currencyId,
                        'gross' => 50,
                        'net' => 40,
                        'linked' => false,
                        'listPrice' => [
                            'net' => 20,
                            'gross' => 25,
                            'linked' => false,
                        ],
                    ],
                ],
            ],
        );

        $properties = $this->defaultPropertiesAdapter->adapt($productEntity);

        $hasListPrice = false;
        $hasListPriceNet = false;

        foreach ($properties as $property) {
            if ($property->getKey() === 'old_price') {
                $hasListPrice = true;
                $this->assertEquals(25, current($property->getAllValues()));
            }
            if ($property->getKey() === 'old_price_net') {
                $hasListPriceNet = true;
                $this->assertEquals(20, current($property->getAllValues()));
            }
        }

        $this->assertSame($isPriceAvailable, $hasListPrice);
        $this->assertSame($isPriceAvailable, $hasListPriceNet);
    }

    public static function listPriceProvider(): array
    {
        return [
            'List price is available for the sales channel currency' => [
                'currencyId' => CommonConstants::CURRENCY_ID,
                'isPriceAvailable' => true,
            ],
            'List price is available for a different currency' => [
                'currencyId' => null,
                'isPriceAvailable' => false,
            ],
        ];
    }

    public static function productPromotionProvider(): array
    {
        return [
            'Product has promotion set to false' => [false, 'No'],
            'Product has promotion set to true' => [true, 'Yes'],
            'Product promotion is set to null' => [null, 'No'],
        ];
    }
}
