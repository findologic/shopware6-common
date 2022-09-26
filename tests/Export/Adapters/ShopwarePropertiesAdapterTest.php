<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Export\Data\Property;
use FINDOLOGIC\Shopware6Common\Export\Adapters\ShopwarePropertiesAdapter;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\PropertiesHelper;
use PHPUnit\Framework\TestCase;
use Vin\ShopwareSdk\Data\Uuid\Uuid;

class ShopwarePropertiesAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;
    use PropertiesHelper;

    public ShopwarePropertiesAdapter $shopwarePropertiesAdapter;

    public function setUp(): void
    {
        $this->shopwarePropertiesAdapter = $this->getShopwarePropertiesAdapter();
    }

    public function testNonFilterablePropertiesAreExportedAsPropertiesInsteadOfAttributes(): void
    {
        $expectedPropertyName1 = 'blub';
        $expectedPropertyName2 = 'blub1';
        $expectedPropertyName3 = 'blub2';
        $expectedPropertyValue1 = 'some value';
        $expectedPropertyValue2 = 'some value1';
        $expectedPropertyValue3 = 'some value2';

        $expectedPropertiesNames = [
            $expectedPropertyName1,
            $expectedPropertyName2
        ];

        $productEntity = $this->createTestProduct(
            [
                'properties' => [
                    [
                        'id' => Uuid::randomHex(),
                        'name' => $expectedPropertyValue1,
                        'group' => [
                            'id' => Uuid::randomHex(),
                            'name' => $expectedPropertyName1,
                            'filterable' => false,
                            'translated' => [
                                'name' => $expectedPropertyName1
                            ]
                        ],
                        'translated' => [
                            'name' => $expectedPropertyValue1
                        ]
                    ],
                    [
                        'id' => Uuid::randomHex(),
                        'name' => $expectedPropertyValue2,
                        'group' => [
                            'id' => Uuid::randomHex(),
                            'name' => $expectedPropertyName2,
                            'filterable' => false,
                            'translated' => [
                                'name' => $expectedPropertyName2
                            ]
                        ],
                        'translated' => [
                            'name' => $expectedPropertyValue2
                        ]
                    ],
                    [
                        'id' => Uuid::randomHex(),
                        'name' => $expectedPropertyValue3,
                        'group' => [
                            'id' => Uuid::randomHex(),
                            'name' => $expectedPropertyName3,
                            'filterable' => true,
                            'translated' => [
                                'name' => $expectedPropertyName3
                            ]
                        ],
                        'translated' => [
                            'name' => $expectedPropertyValue3
                        ]
                    ]
                ]
            ]
        );

        $properties = array_merge(
            $this->shopwarePropertiesAdapter->adapt($productEntity)
        );

        $foundProperties = array_filter(
            $properties,
            static function (Property $property) use ($expectedPropertiesNames) {
                return in_array($property->getKey(), $expectedPropertiesNames);
            }
        );
        $foundPropertyValues = array_map(
            static function (Property $property) {
                return $property->getAllValues()[''];
            },
            array_values($foundProperties)
        );

        $this->assertCount(2, $foundProperties);
        $this->assertContains($expectedPropertyValue1, $foundPropertyValues);
        $this->assertContains($expectedPropertyValue2, $foundPropertyValues);
    }
}
