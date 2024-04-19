<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Shopware6Common\Export\Adapters\OverriddenPriceAdapter;
use FINDOLOGIC\Shopware6Common\Tests\CommonConstants;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupCollection;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupEntity;

class OverriddenPriceAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;

    public OverriddenPriceAdapter $overriddenPriceAdapter;

    public function setUp(): void
    {
        $this->overriddenPriceAdapter = $this->getOverriddenPriceAdapter();
    }

    public function testOverriddenPriceContainsConfiguredProductPrice(): void
    {
        $expectedPrice = 13.37;

        $product = $this->createTestProduct([
            'price' => [
                [
                    'currencyId' => CommonConstants::CURRENCY_ID,
                    'gross' => 6,
                    'net' => 5,
                    'linked' => false,
                    'listPrice' => [
                        'gross' => $expectedPrice,
                        'net' => 10,
                    ],
                ],
            ],
        ]);

        $overriddenPrices = $this->overriddenPriceAdapter->adapt($product);

        $this->assertCount(1, $overriddenPrices);
        $this->assertCount(1, $overriddenPrices[0]->getValues());
        $this->assertEquals($expectedPrice, $overriddenPrices[0]->getValues()['']);
    }

    public static function customerGroupsProvider(): array
    {
        $grossCustomerGroup = new CustomerGroupEntity();
        $grossCustomerGroup->id = CommonConstants::GROSS_CUSTOMER_GROUP_ID;
        $grossCustomerGroup->displayGross = true;

        $netCustomerGroup = new CustomerGroupEntity();
        $netCustomerGroup->id = CommonConstants::NET_CUSTOMER_GROUP_ID;
        $netCustomerGroup->displayGross = false;

        return [
            'Gross customer group' => [
                'grossPrice' => 13.37,
                'netPrice' => 10.11,
                'customerGroups' => [
                    $grossCustomerGroup,
                ],
                'expectedOverriddenPrices' => [
                    $grossCustomerGroup->id => 13.37,
                ],
            ],
            'Net customer group' => [
                'grossPrice' => 13.37,
                'netPrice' => 10.11,
                'customerGroups' => [
                    $netCustomerGroup,
                ],
                'expectedOverriddenPrices' => [
                    $netCustomerGroup->id => 10.11,
                ],
            ],
            'Net and gross customer group' => [
                'grossPrice' => 13.37,
                'netPrice' => 10.11,
                'customerGroups' => [
                    $grossCustomerGroup,
                    $netCustomerGroup,
                ],
                'expectedOverriddenPrices' => [
                    $grossCustomerGroup->id => 13.37,
                    $netCustomerGroup->id => 10.11,
                ],
            ],
        ];
    }

    /**
     * @runInSeparateProcess
     * @param CustomerGroupEntity[] $customerGroups
     * @param array<string, float> $expectedOverriddenPrices
     */
    #[DataProvider('customerGroupsProvider')]
    public function testOverriddenPriceIsExportedForCustomerGroups(
        float $grossPrice,
        float $netPrice,
        array $customerGroups,
        array $expectedOverriddenPrices
    ): void {
        $adapter = $this->getOverriddenPriceAdapter(
            new CustomerGroupCollection($customerGroups),
        );

        $product = $this->createTestProduct([
            'price' => [
                [
                    'currencyId' => CommonConstants::CURRENCY_ID,
                    'gross' => 2,
                    'net' => 1,
                    'linked' => false,
                    'listPrice' => [
                        'gross' => $grossPrice,
                        'net' => $netPrice,
                    ],
                ],
            ],
        ]);

        $overriddenPrices = $adapter->adapt($product);

        $expectedGroupPrices = count($expectedOverriddenPrices);
        $actualGroupPrices = 0;
        foreach ($customerGroups as $customerGroup) {
            $userGroup = $customerGroup->id;

            foreach ($overriddenPrices as $overriddenPrice) {
                foreach ($overriddenPrice->getValues() as $group => $value) {
                    if ($userGroup === $group) {
                        $this->assertEquals($expectedOverriddenPrices[$customerGroup->id], $value);
                        $actualGroupPrices++;
                    }
                }
            }
        }

        $this->assertEquals($expectedGroupPrices, $actualGroupPrices, sprintf(
            'Expected %d group(s) to have prices. Actual price count: %d',
            $expectedGroupPrices,
            $actualGroupPrices,
        ));
    }

    public function testOverriddenProductPriceWithCurrency(): void
    {
        $salesChannel = $this->buildSalesChannel();
        $salesChannel->currencyId = CommonConstants::CURRENCY2_ID;

        $adapter = $this->getOverriddenPriceAdapter(null, $salesChannel);
        $testProduct = $this->createTestProduct([
            'price' => [
                [
                    'currencyId' => CommonConstants::CURRENCY_ID,
                    'gross' => 3,
                    'net' => 4,
                    'linked' => false,
                    'listPrice' => [
                        'gross' => 15,
                        'net' => 10,
                    ],
                ],
                [
                    'currencyId' => CommonConstants::CURRENCY2_ID,
                    'gross' => 2,
                    'net' => 1,
                    'linked' => false,
                    'listPrice' => [
                        'gross' => 7.5,
                        'net' => 5,
                    ],
                ],
            ],
        ]);

        $overriddenPrices = $adapter->adapt($testProduct);
        $priceValues = current($overriddenPrices)->getValues();

        $this->assertEquals(1, count($overriddenPrices));
        $this->assertEquals(7.5, current($priceValues));
    }
}
