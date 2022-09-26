<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Shopware6Common\Export\Adapters\PriceAdapter;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoPricesException;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\TestCase;
use Vin\ShopwareSdk\Data\Defaults;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupCollection;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupEntity;
use Vin\ShopwareSdk\Data\Uuid\Uuid;

class PriceAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;

    public PriceAdapter $priceAdapter;

    public function setUp(): void
    {
        $this->priceAdapter = $this->getPriceAdapter();
    }

    public function testExceptionIsThrownIfProductHasNoPrices(): void
    {
        $this->expectException(ProductHasNoPricesException::class);

        $product = $this->createTestProduct(['price' => []]);

        $this->priceAdapter->adapt($product);
    }

    public function testPriceContainsConfiguredProductPrice(): void
    {
        $expectedPrice = 13.37;

        $product = $this->createTestProduct([
            'price' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => $expectedPrice,
                    'net' => 10,
                    'linked' => false
                ]
            ]
        ]);

        $prices = $this->priceAdapter->adapt($product);

        $this->assertCount(1, $prices);
        $this->assertCount(1, $prices[0]->getValues());
        $this->assertEquals($expectedPrice, $prices[0]->getValues()['']);
    }

    public function customerGroupsProvider(): array
    {
        $grossCustomerGroup = new CustomerGroupEntity();
        $grossCustomerGroup->id = Uuid::randomHex();
        $grossCustomerGroup->displayGross = true;

        $netCustomerGroup = new CustomerGroupEntity();
        $netCustomerGroup->id = Uuid::randomHex();
        $netCustomerGroup->displayGross = false;

        return [
            'Gross customer group' => [
                'grossPrice' => 13.37,
                'netPrice' => 10.11,
                'customerGroups' => [
                    $grossCustomerGroup
                ],
                'expectedPrices' => [
                    $grossCustomerGroup->id => 13.37
                ]
            ],
            'Net customer group' => [
                'grossPrice' => 13.37,
                'netPrice' => 10.11,
                'customerGroups' => [
                    $netCustomerGroup
                ],
                'expectedPrices' => [
                    $netCustomerGroup->id => 10.11
                ]
            ],
            'Net and gross customer group' => [
                'grossPrice' => 13.37,
                'netPrice' => 10.11,
                'customerGroups' => [
                    $grossCustomerGroup,
                    $netCustomerGroup
                ],
                'expectedPrices' => [
                    $grossCustomerGroup->id => 13.37,
                    $netCustomerGroup->id => 10.11
                ]
            ]
        ];
    }

    /**
     * @runInSeparateProcess
     * @dataProvider customerGroupsProvider
     * @param CustomerGroupEntity[] $customerGroups
     * @param array<string, float> $expectedPrices
     * @throws ProductHasNoPricesException
     */
    public function testPriceIsExportedForCustomerGroups(
        float $grossPrice,
        float $netPrice,
        array $customerGroups,
        array $expectedPrices
    ): void {
        $adapter = $this->getPriceAdapter(
            new CustomerGroupCollection($customerGroups)
        );

        $product = $this->createTestProduct([
            'price' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => $grossPrice,
                    'net' => $netPrice,
                    'linked' => false
                ]
            ]
        ]);

        $prices = $adapter->adapt($product);

        $expectedGroupPrices = count($expectedPrices);
        $actualGroupPrices = 0;
        foreach ($customerGroups as $customerGroup) {
            $userGroup = $customerGroup->id;

            foreach ($prices as $price) {
                foreach ($price->getValues() as $group => $value) {
                    if ($userGroup === $group) {
                        $this->assertEquals($expectedPrices[$customerGroup->id], $value);
                        $actualGroupPrices++;
                    }
                }
            }
        }

        $this->assertEquals($expectedGroupPrices, $actualGroupPrices, sprintf(
            'Expected %d group(s) to have prices. Actual price count: %d',
            $expectedGroupPrices,
            $actualGroupPrices
        ));
    }

    public function testProductPriceWithCurrency(): void
    {
        $currencyId = Uuid::randomHex();
        $salesChannel = $this->buildSalesChannel();
        $salesChannel->currencyId = $currencyId;

        $adapter = $this->getPriceAdapter(null, $salesChannel);
        $testProduct = $this->createTestProduct([
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false],
                ['currencyId' => $currencyId, 'gross' => 7.5, 'net' => 5, 'linked' => false]
            ]
        ]);

        $prices = $adapter->adapt($testProduct);
        $priceValues = current($prices)->getValues();

        $this->assertEquals(1, count($prices));
        $this->assertEquals(7.5, current($priceValues));
    }
}
