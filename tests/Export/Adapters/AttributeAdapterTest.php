<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Export\Data\Attribute;
use FINDOLOGIC\Export\XML\XMLItem;
use FINDOLOGIC\Shopware6Common\Export\Adapters\AttributeAdapter;
use FINDOLOGIC\Shopware6Common\Export\Enums\IntegrationType;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\AccessEmptyPropertyException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoCategoriesException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoNameException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoPricesException;
use FINDOLOGIC\Shopware6Common\Tests\CommonConstants;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AttributeHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vin\ShopwareSdk\Data\Uuid\Uuid;

class AttributeAdapterTest extends TestCase
{
    use AdapterHelper;
    use AttributeHelper;
    use ProductHelper;

    public AttributeAdapter $attributeAdapter;

    public function setUp(): void
    {
        $this->attributeAdapter = $this->getAttributeAdapter();
    }

    public function testAttributeContainsAttributeOfTheProduct(): void
    {
        $id = Uuid::randomHex();
        $variantId = Uuid::randomHex();
        $variantProductNumber = Uuid::randomHex();

        $product = $this->createTestProduct([
            'id' => $id,
        ]);

        $variantProduct = $this->createTestProduct([
            'id' => $variantId,
            'parentId' => $id,
            'productNumber' => $variantProductNumber,
            'shippingFree' => false,
        ]);

        $expected = array_merge(
            $this->getAttributes($product),
            $this->getAttributes($variantProduct),
        );

        $attributes = array_merge(
            $this->attributeAdapter->adapt($product),
            $this->attributeAdapter->adapt($variantProduct),
        );

        $this->assertEquals($expected, $attributes);
    }

    #[DataProvider('attributeProvider')]
    public function testAttributesAreProperlyEscaped(
        IntegrationType $integrationType,
        string $attributeName,
        string $expectedName
    ): void {
        $config = $this->getPluginConfig([
            'integrationType' => $integrationType,
        ]);

        $adapter = $this->getAttributeAdapter($config);

        $productEntity = $this->createTestProduct(
            [
                'properties' => [
                    [
                        'id' => Uuid::randomHex(),
                        'name' => 'some value',
                        'group' => [
                            'id' => Uuid::randomHex(),
                            'name' => $attributeName,
                            'filterable' => true,
                            'translated' => [
                                'name' => $attributeName,
                            ],
                        ],
                        'translated' => [
                            'name' => 'some value',
                        ],
                    ],
                ],
            ],
        );

        $attributes = $adapter->adapt($productEntity);

        $foundAttributes = array_filter(
            $attributes,
            static function (Attribute $attribute) use ($expectedName) {
                return $attribute->getKey() === $expectedName;
            },
        );

        /** @var Attribute $attribute */
        $attribute = reset($foundAttributes);
        $this->assertInstanceOf(
            Attribute::class,
            $attribute,
            sprintf('Attribute "%s" not present in attributes.', $expectedName),
        );
    }

    #[DataProvider('multiSelectCustomFieldsProvider')]
    public function testProductWithMultiSelectCustomFields(
        array $customFields,
        array $expectedCustomFieldAttributes,
        bool $expectAssertions
    ): void {
        if (!$expectAssertions) {
            $this->expectNotToPerformAssertions();
        }

        $data['customFields'] = $customFields;
        $productEntity = $this->createTestProduct($data, true);
        $attributes = $this->attributeAdapter->adapt($productEntity);

        foreach ($attributes as $attribute) {
            if ($attribute->getKey() !== 'multi') {
                continue;
            }

            $this->assertEquals($expectedCustomFieldAttributes[$attribute->getKey()], $attribute->getValues());
        }
    }

    public function testProductWithLongCustomFieldValuesAreIgnored(): void
    {
        $data['customFields'] = ['long_value' => str_repeat('und wieder, ', 20000)];
        $productEntity = $this->createTestProduct($data, true);
        $attributes = $this->attributeAdapter->adapt($productEntity);
        $customFieldAttributes = $this->getCustomFields($attributes, $data);

        $this->assertEmpty($customFieldAttributes);
    }

    /**
     * @param float[] $ratings
     */
    #[DataProvider('ratingProvider')]
    public function testProductRatings(array $ratings, float $expectedRating): void
    {
        $productEntity = $this->createTestProduct();

        if (count($ratings)) {
            $productEntity->ratingAverage = $expectedRating;
        }

        $attributes = $this->attributeAdapter->adapt($productEntity);

        $ratingAttribute = end($attributes);
        $this->assertSame('rating', $ratingAttribute->getKey());
        $this->assertEquals($expectedRating, current($ratingAttribute->getValues()));
    }

    #[DataProvider('emptyAttributeNameProvider')]
    public function testEmptyAttributeNamesAreSkipped(?string $value): void
    {
        $config = $this->getPluginConfig([
            'integrationType' => IntegrationType::API,
        ]);
        $adapter = $this->getAttributeAdapter($config);

        $data = [
            'description' => 'Really interesting',
            'referenceunit' => 'cm',
            'customFields' => [$value => 'something'],
        ];

        $productEntity = $this->createTestProduct($data);
        $attributes = $adapter->adapt($productEntity);
        $customFieldAttributes = $this->getCustomFields($attributes, $data);

        $this->assertEmpty($customFieldAttributes);
    }

    #[DataProvider('categoryAndCatUrlWithIntegrationTypeProvider')]
    public function testCategoryAndCatUrlExportBasedOnIntegrationType(
        ?IntegrationType $integrationType,
        array $categories,
        array $expectedCategories,
        array $expectedCatUrls
    ): void {
        $config = $this->getPluginConfig([
            'integrationType' => $integrationType,
        ]);
        $adapter = $this->getAttributeAdapter($config);

        $productEntity = $this->createTestProduct(['categories' => $categories]);
        $attributes = $adapter->adapt($productEntity);

        $this->assertSame('cat_url', $attributes[0]->getKey());
        $this->assertSameSize($expectedCatUrls, $attributes[0]->getValues());
        $this->assertSame($expectedCatUrls, $attributes[0]->getValues());

        $this->assertSame('cat', $attributes[1]->getKey());
        $this->assertSameSize($expectedCategories, $attributes[1]->getValues());
        $this->assertSame($expectedCategories, $attributes[1]->getValues());
    }

    public function testAttributesAreHtmlEntityEncoded(): void
    {
        $expectedAttributeValue = '>80';
        $data['customFields'] = ['length' => '&gt;80'];

        $productEntity = $this->createTestProduct($data, true);

        $attributes = $this->attributeAdapter->adapt($productEntity);
        $relatedAttributes = $this->getCustomFields($attributes, $data);

        $this->assertCount(1, $relatedAttributes);
        $this->assertSame($expectedAttributeValue, $relatedAttributes[0]->getValues()[0]);
    }

    public function testProductWithCustomFields(): void
    {
        $data = [
            'customFields' => [
                'findologic_size' => 100,
                'findologic_color' => 'yellow',
            ],
        ];
        $productEntity = $this->createTestProduct($data, true);
        $productFields = $productEntity->getCustomFields();
        $attributes = $this->attributeAdapter->adapt($productEntity);
        $customAttributes = $this->getCustomFields($attributes, $data);

        $this->assertCount(2, $customAttributes);
        foreach ($customAttributes as $attribute) {
            $this->assertEquals($productFields[$attribute->getKey()], current($attribute->getValues()));
        }
    }

    public function testMultiDimensionalCustomFieldsAreIgnored(): void
    {
        $data = [
            'customFields' => [
                'multidimensional' => [
                    ['interesting' => 'this is some multidimensional data wow!'],
                ],
            ],
        ];
        $productEntity = $this->createTestProduct($data, true);
        $attributes = $this->attributeAdapter->adapt($productEntity);
        $customAttributes = $this->getCustomFields($attributes, $data);

        $this->assertEmpty($customAttributes);
    }

    public function testCustomFieldsContainingZeroAsStringAreProperlyExported(): void
    {
        $data['customFields'] = ['nice' => "0\n"];
        $productEntity = $this->createTestProduct($data, true);

        $attributes = $this->attributeAdapter->adapt($productEntity);
        $customAttributes = $this->getCustomFields($attributes, $data);

        $this->assertCount(1, $customAttributes);
        $this->assertSame(['0'], $customAttributes[0]->getValues());
    }

    #[DataProvider('emptyValuesProvider')]
    public function testEmptyAttributeValuesAreSkipped(?string $value): void
    {
        $data = [
            'customFields' => [$value => 100, 'findologic_color' => $value],
        ];

        $productEntity = $this->createTestProduct($data);
        $attributes = $this->attributeAdapter->adapt($productEntity);
        $customAttributes = $this->getCustomFields($attributes, $data);

        $this->assertEmpty($customAttributes);
    }

    /**
     * @throws AccessEmptyPropertyException
     * @throws ProductHasNoCategoriesException
     * @throws ProductHasNoNameException
     * @throws ProductHasNoPricesException
     */
    #[DataProvider('categorySeoProvider')]
    public function testProductCategoriesUrlWithoutSeoOrEmptyPath(array $data, string $categoryId): void
    {
        $categoryData['categories'] = $data;
        $productEntity = $this->createTestProduct($categoryData);

        $attributes = $this->attributeAdapter->adapt($productEntity);

        $attribute = current($attributes);
        $this->assertSame('cat_url', $attribute->getKey());
        $this->assertContains(sprintf('/navigation/%s', $categoryId), $attribute->getValues());
    }

    public function testEmptyCategoryNameShouldStillExportCategory(): void
    {
        $categoryId = Uuid::randomHex();

        $productEntity = $this->createTestProduct(
            [
                'categories' => [
                    [
                        'parentId' => CommonConstants::NAVIGATION_CATEGORY_ID,
                        'id' => $categoryId,
                        'name' => ' ',
                        'active' => true,
                        'seoUrls' => [
                            [
                                'pathInfo' => 'navigation/' . $categoryId,
                                'seoPathInfo' => '/FINDOLOGIC-Category/',
                                'isCanonical' => true,
                                'routeName' => 'frontend.navigation.page',
                            ],
                        ],
                    ],
                ],
            ],
        );

        $attributes = $this->attributeAdapter->adapt($productEntity);

        $this->assertCount(5, $attributes);
        $this->assertSame('cat_url', $attributes[0]->getKey());

        $catUrls = $attributes[0]->getValues();
        $this->assertCount(1, $catUrls);
        $this->assertSame([sprintf('/navigation/%s', $categoryId)], $catUrls);
    }

    public static function parentAndChildrenCategoryProvider(): array
    {
        return [
            'Parent and children have the same categories assigned' => [
                'isParentAssigned' => true,
                'isVariantAssigned' => true,
            ],
            'Parent has no categories and children have some categories assigned' => [
                'isParentAssigned' => false,
                'isVariantAssigned' => true,
            ],
            'Parent has categories and children have no categories assigned' => [
                'isParentAssigned' => true,
                'isVariantAssigned' => false,
            ],
        ];
    }

    #[DataProvider('parentAndChildrenCategoryProvider')]
    public function testOnlyUniqueCategoriesAreExported(bool $isParentAssigned, bool $isVariantAssigned): void
    {
        $id = Uuid::randomHex();
        $category = [
            'id' => 'cce80a72bc3481d723c38cccf592d45a',
            'name' => 'Category1',
            'active' => true,
            'parentId' => CommonConstants::NAVIGATION_CATEGORY_ID,
        ];

        $expectedCategories = ['Category1'];
        $expectedCatUrls = [
            '/navigation/cce80a72bc3481d723c38cccf592d45a',
        ];

        $productEntity = $this->createTestProduct([
            'id' => $id,
            'categories' => $isParentAssigned ? [$category] : [],
        ]);

        $childEntity = $this->createTestProduct([
            'parentId' => $id,
            'productNumber' => Uuid::randomHex(),
            'categories' => $isVariantAssigned ? [$category] : [],
            'shippingFree' => false,
        ]);

        $config = $this->getPluginConfig([
            'integrationType' => IntegrationType::DI,
        ]);

        $initialItem = new XMLItem('123');
        $exportItemAdapter = $this->getExportItemAdapter(null, $config);

        $item = $exportItemAdapter->adapt($initialItem, $productEntity);

        if ($item === null) {
            $item = $initialItem;
        }

        $exportItemAdapter->adaptVariant($item, $childEntity);
        $reflector = new ReflectionClass($item);
        $attributes = $reflector->getProperty('attributes');
        $value = $attributes->getValue($item);

        $this->assertArrayHasKey('cat_url', $value);
        $categoryUrlAttributeValues = $value['cat_url']->getValues();
        $this->assertSame($expectedCatUrls, $categoryUrlAttributeValues);

        $this->assertArrayHasKey('cat', $value);
        $categoryAttributeValues = $value['cat']->getValues();
        $this->assertSame($expectedCategories, $categoryAttributeValues);
    }

    /**
     * @param Attribute[] $attributes
     * @param array<string, string|array> $customFields
     * @return array
     */
    public function getCustomFields(array $attributes, array $customFields): array
    {
        $customFieldAttributes = [];

        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute->getKey(), $customFields['customFields'])) {
                $customFieldAttributes[] = $attribute;
            }
        }

        return $customFieldAttributes;
    }

    public static function categorySeoProvider(): array
    {
        $categoryId = Uuid::randomHex();

        return [
            'Category does not have SEO path assigned' => [
                'data' => [
                    [
                        'parentId' => CommonConstants::NAVIGATION_CATEGORY_ID,
                        'id' => $categoryId,
                        'name' => 'FINDOLOGIC Category',
                        'active' => true,
                        'seoUrls' => [
                            [
                                'id' => Uuid::randomHex(),
                                'salesChannelId' => CommonConstants::SALES_CHANNEL_ID,
                                'pathInfo' => 'navigation/' . $categoryId,
                                'seoPathInfo' => 'Main',
                                'isCanonical' => true,
                                'routeName' => 'frontend.navigation.page',
                            ],
                            [
                                'id' => Uuid::randomHex(),
                                'salesChannelId' => CommonConstants::SALES_CHANNEL2_ID,
                                'pathInfo' => 'navigation/' . $categoryId,
                                'seoPathInfo' => 'Additional Main',
                                'isCanonical' => true,
                                'routeName' => 'frontend.navigation.page',
                            ],
                        ],
                    ],
                ],
                'categoryId' => $categoryId,
            ],
            'Category have a pseudo empty SEO path assigned' => [
                'data' => [
                    [
                        'parentId' => CommonConstants::NAVIGATION_CATEGORY_ID,
                        'id' => $categoryId,
                        'name' => 'FINDOLOGIC Category',
                        'active' => true,
                        'seoUrls' => [
                            [
                                'pathInfo' => 'navigation/' . $categoryId,
                                'seoPathInfo' => ' ',
                                'isCanonical' => true,
                                'routeName' => 'frontend.navigation.page',
                            ],
                        ],
                    ],
                ],
                'categoryId' => $categoryId,
            ],
        ];
    }

    public static function attributeProvider(): array
    {
        return [
            'API Integration filter with some special characters' => [
                'integrationType' => IntegrationType::API,
                'attributeName' => 'Special Characters /#+*()()=§(=\'\'!!"$.|',
                'expectedName' => 'SpecialCharacters',
            ],
            'API Integration filter with brackets' => [
                'integrationType' => IntegrationType::API,
                'attributeName' => 'Farbwiedergabe (Ra/CRI)',
                'expectedName' => 'FarbwiedergabeRaCRI',
            ],
            'API Integration filter with special UTF-8 characters' => [
                'integrationType' => IntegrationType::API,
                'attributeName' => 'Ausschnitt D ø (mm)',
                'expectedName' => 'AusschnittDmm',
            ],
            'API Integration filter dots and dashes' => [
                'integrationType' => IntegrationType::API,
                'attributeName' => 'free_shipping.. Really Cool--__',
                'expectedName' => 'free_shippingReallyCool--__',
            ],
            'API Integration filter with umlauts' => [
                'integrationType' => IntegrationType::API,
                'attributeName' => 'Umläüts äre cööl',
                'expectedName' => 'Umläütsärecööl',
            ],
            'Direct Integration filter with some special characters' => [
                'integrationType' => IntegrationType::DI,
                'attributeName' => 'Special Characters /#+*()()=§(=\'\'!!"$.|',
                'expectedName' => 'Special Characters /#+*()()=§(=\'\'!!"$.|',
            ],
            'Direct Integration filter with brackets' => [
                'integrationType' => IntegrationType::DI,
                'attributeName' => 'Farbwiedergabe (Ra/CRI)',
                'expectedName' => 'Farbwiedergabe (Ra/CRI)',
            ],
            'Direct Integration filter with special UTF-8 characters' => [
                'integrationType' => IntegrationType::DI,
                'attributeName' => 'Ausschnitt D ø (mm)',
                'expectedName' => 'Ausschnitt D ø (mm)',
            ],
            'Direct Integration filter dots and dashes' => [
                'integrationType' => IntegrationType::DI,
                'attributeName' => 'free_shipping.. Really Cool--__',
                'expectedName' => 'free_shipping.. Really Cool--__',
            ],
            'Direct Integration filter with umlauts' => [
                'integrationType' => IntegrationType::DI,
                'attributeName' => 'Umläüts äre cööl',
                'expectedName' => 'Umläüts äre cööl',
            ],
        ];
    }

    public static function multiSelectCustomFieldsProvider(): array
    {
        return [
            'multiple values' => [
                'customFields' => [
                    'multi' => [
                        'one value',
                        'another value',
                        'even a third one!',
                    ],
                ],
                'expectedCustomFieldAttributes' => [
                    'multi' => [
                        'one value',
                        'another value',
                        'even a third one!',
                    ],
                ],
                'expectAssertions' => true,
            ],
            'multiple values with one null value' => [
                'customFields' => [
                    'multiWithNull' => [
                        'one value',
                        'another value',
                        'even a third one!',
                        null,
                    ],
                ],
                'expectedCustomFieldAttributes' => [
                    'multiWithNull' => [
                        'one value',
                        'another value',
                        'even a third one!',
                    ],
                ],
                'expectAssertions' => false,
            ],
            'multiple values with one empty value' => [
                'customFields' => [
                    'multiWithEmptyValue' => [
                        'one value',
                        'another value',
                        'even a third one!',
                        '',
                    ],
                ],
                'expectedCustomFieldAttributes' => [
                    'multiWithEmptyValue' => [
                        'one value',
                        'another value',
                        'even a third one!',
                    ],
                ],
                'expectAssertions' => false,
            ],
        ];
    }

    public static function ratingProvider(): array
    {
        $multipleRatings = [2.0, 4.0, 5.0, 1.0];
        $average = array_sum($multipleRatings) / count($multipleRatings);

        return [
            'No rating is provided' => ['ratings' => [], 'expectedRating' => 0.0],
            'Single rating is provided' => ['ratings' => [2.0], 'expectedRating' => 2.0],
            'Multiple ratings is provided' => ['ratings' => $multipleRatings, 'expectedRating' => $average],
        ];
    }

    public static function emptyAttributeNameProvider(): array
    {
        return [
            'Attribute name is null' => ['value' => null],
            'Attribute name is an empty string' => ['value' => ''],
            'Attribute name only contains empty spaces' => ['value' => '    '],
            'Attribute name only containing special characters' => ['value' => '$$$$%'],
        ];
    }

    public static function emptyValuesProvider(): array
    {
        return [
            'null values provided' => ['value' => null],
            'empty string values provided' => ['value' => ''],
            'values containing empty spaces provided' => ['value' => '    '],
        ];
    }

    public static function categoryAndCatUrlWithIntegrationTypeProvider(): array
    {
        $firstLevelCategories = [
            [
                'id' => 'cce80a72bc3481d723c38cccf592d45a',
                'parentId' => CommonConstants::NAVIGATION_CATEGORY_ID,
                'name' => 'Category1',
                'active' => true,
            ],
        ];
        $nestedCategories = [
            [
                'id' => 'cce80a72bc3481d723c38cccf592d45a',
                'parentId' => CommonConstants::NAVIGATION_CATEGORY_ID,
                'name' => 'Category1',
                'active' => true,
                'children' => [
                    [
                        'id' => 'f03d845e0abf31e72409cf7c5c704a2e',
                        'name' => 'Category2',
                        'active' => true,
                        'children' => [
                            [
                                'id' => '6a753ffefab44667b87d9260fbcb9fac',
                                'name' => 'Category3',
                                'active' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return [
            'Integration type is API and category is at first level' => [
                'integrationType' => IntegrationType::API,
                'categories' => $firstLevelCategories,
                'expectedCategories' => [
                    'Category1',
                ],
                'expectedCatUrls' => [
                    '/navigation/cce80a72bc3481d723c38cccf592d45a',
                ],
            ],
            'Integration type is API with nested categories' => [
                'integrationType' => IntegrationType::API,
                'categories' => $nestedCategories,
                'expectedCategories' => [
                    'Category1_Category2_Category3',
                ],
                'expectedCatUrls' => [
                    '/navigation/6a753ffefab44667b87d9260fbcb9fac',
                    '/navigation/cce80a72bc3481d723c38cccf592d45a',
                    '/navigation/f03d845e0abf31e72409cf7c5c704a2e',
                ],
            ],
            'Integration type is DI and category is at first level' => [
                'integrationType' => IntegrationType::DI,
                'categories' => $firstLevelCategories,
                'expectedCategories' => [
                    'Category1',
                ],
                'expectedCatUrls' => [
                    '/navigation/cce80a72bc3481d723c38cccf592d45a',
                ],
            ],
            'Integration type is DI with nested categories' => [
                'integrationType' => IntegrationType::DI,
                'categories' => $nestedCategories,
                'expectedCategories' => [
                    'Category1_Category2_Category3',
                ],
                'expectedCatUrls' => [
                    '/navigation/6a753ffefab44667b87d9260fbcb9fac',
                    '/navigation/cce80a72bc3481d723c38cccf592d45a',
                    '/navigation/f03d845e0abf31e72409cf7c5c704a2e',
                ],
            ],
            'Integration type is unknown and category is at first level' => [
                'integrationType' => null,
                'categories' => $firstLevelCategories,
                'expectedCategories' => [
                    'Category1',
                ],
                'expectedCatUrls' => [
                    '/navigation/cce80a72bc3481d723c38cccf592d45a',
                ],
            ],
            'Integration type is unknown with nested categories' => [
                'integrationType' => null,
                'categories' => $nestedCategories,
                'expectedCategories' => [
                    'Category1_Category2_Category3',
                ],
                'expectedCatUrls' => [
                    '/navigation/6a753ffefab44667b87d9260fbcb9fac',
                    '/navigation/cce80a72bc3481d723c38cccf592d45a',
                    '/navigation/f03d845e0abf31e72409cf7c5c704a2e',
                ],
            ],
        ];
    }
}
