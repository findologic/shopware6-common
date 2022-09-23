<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Traits;

use Vin\ShopwareSdk\Data\Defaults;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryCollection;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryEntity;
use Vin\ShopwareSdk\Data\Entity\Entity;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;
use Vin\ShopwareSdk\Data\Entity\ProductConfiguratorSetting\ProductConfiguratorSettingCollection;
use Vin\ShopwareSdk\Data\Entity\ProductConfiguratorSetting\ProductConfiguratorSettingEntity;
use Vin\ShopwareSdk\Data\Entity\ProductManufacturer\ProductManufacturerEntity;
use Vin\ShopwareSdk\Data\Entity\ProductManufacturerTranslation\ProductManufacturerTranslationCollection;
use Vin\ShopwareSdk\Data\Entity\ProductManufacturerTranslation\ProductManufacturerTranslationEntity;
use Vin\ShopwareSdk\Data\Entity\PropertyGroupOption\PropertyGroupOptionCollection;
use Vin\ShopwareSdk\Data\Entity\PropertyGroupOption\PropertyGroupOptionEntity;
use Vin\ShopwareSdk\Data\Entity\SeoUrl\SeoUrlCollection;
use Vin\ShopwareSdk\Data\Entity\SeoUrl\SeoUrlEntity;
use Vin\ShopwareSdk\Data\Uuid\Uuid;

trait ProductHelper
{
    use CategoryHelper;
    use Constants;

    public function createTestProduct(
        array $overrideData = [],
        bool $overrideRecursively = false,
        bool $withManufacturer = true
    ): ProductEntity {
        $id = Uuid::randomHex();
        $redId = Uuid::randomHex();
        $colorId = Uuid::randomHex();

        $productData = [];
        $productData = array_merge(
            $productData,
            $this->getNameValues($overrideData['name'] ?? 'FINDOLOGIC Product')
        );

        $productData = array_merge($productData, [
            'id' => $id,
            'productNumber' => 'FINDOLOGIC001',
            'stock' => 10,
            'ean' => 'FL001',
            'description' => 'FINDOLOGIC Description',
            'tags' => [
                ['id' => Uuid::randomHex(), 'name' => 'FINDOLOGIC Tag']
            ],
            'cover' => $this->getDefaultCoverData(),
            'price' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => 15,
                    'net' => 10,
                    'linked' => false,
                    'listPrice' => null,
                ]
            ],
            'tax' => ['id' => Uuid::randomHex(),  'name' => '9%', 'taxRate' => 9],
            'seoUrls' => $this->getDefaultSeoUrlsData($id),
            'customFields' => [],
            'translated' => [
                'description' => 'FINDOLOGIC Description',
            ]
        ]);

        $productData = array_merge($productData, $this->getDefaultPropertySettingsData($redId, $colorId));

        if ($withManufacturer) {
            $productData = array_merge($productData, [
                'manufacturerNumber' => 'MAN001',
                'manufacturer' => [
                    'name' => 'FINDOLOGIC',
                    'translated' => [
                        'name' => 'FINDOLOGIC'
                    ],
                    'translations' => [
                        'de-DE' => [
                            'name' => 'FINDOLOGIC DE',
                        ],
                        'en-GB' => [
                            'name' => 'FINDOLOGIC EN',
                        ],
                    ]
                ]
            ]);
        }

        $productData = $overrideRecursively
            ? array_replace_recursive($productData, $overrideData)
            : array_merge($productData, $overrideData);

        unset($productData['categories']);

        /** @var ProductEntity $product */
        $product = Entity::createFromArray(ProductEntity::class, $productData);

        $product->categories = array_key_exists('categories', $overrideData)
            ? $this->buildCustomCategories($overrideData['categories'])
            : $this->getDefaultCategories();

        return $this->generateCategoryPathsForProduct($product);
    }

    public function getNameValues(string $name): array
    {
        return [
            'name' => $name,
            'translated' => [
                'name' => $name,
            ],
            'translations' => [
                'de-DE' => [
                    'name' => $name . ' DE',
                ],
                'en-GB' => [
                    'name' => $name . ' EN',
                ],
            ],
        ];
    }

    public function getDefaultSeoUrlsData(string $productId): array
    {
        return [
            [
                'pathInfo' => '/detail/' . $productId,
                'seoPathInfo' => 'FINDOLOGIC-Product/FINDOLOGIC001',
                'isCanonical' => true,
                'routeName' => 'frontend.detail.page',
                'salesChannelId' => Defaults::SALES_CHANNEL,
                'languageId' => Defaults::LANGUAGE_SYSTEM,
            ],
            [
                'pathInfo' => '/detail/' . $productId,
                'seoPathInfo' => 'FINDOLOGIC-Product-EN/FINDOLOGIC001',
                'isCanonical' => true,
                'routeName' => 'frontend.detail.page',
                'salesChannelId' => Defaults::SALES_CHANNEL,
                'languageId' => Uuid::randomHex(),
            ],
            [
                'pathInfo' => '/detail/' . $productId,
                'seoPathInfo' => 'Awesome-Seo-Url/&ecause/SÄÖ/is/$mportant+',
                'isCanonical' => true,
                'routeName' => 'frontend.detail.page',
                'salesChannelId' => Defaults::SALES_CHANNEL,
                'languageId' => Defaults::LANGUAGE_SYSTEM,
            ]
        ];
    }

    public function getDefaultPropertySettingsData(string $optionId, string $groupId): array
    {
        return [
            'properties' => [
                [
                    'id' => $optionId,
                    'name' => 'red',
                    'group' => [
                        'id' => $groupId,
                        'name' => 'color',
                        'filterable' => true,
                        'translated' => [
                            'name' => 'color'
                        ]
                    ],
                    'translated' => [
                        'name' => 'red'
                    ]
                ]
            ],
            'options' => [
                [
                    'id' => $optionId,
                    'name' => 'red',
                    'group' => [
                        'id' => $groupId,
                        'name' => $groupId,
                        'translated' => [
                            'name' => $groupId
                        ]
                    ],
                    'translated' => [
                        'name' => 'red'
                    ]
                ]
            ],
            'configuratorSettings' => [
                [
                    'id' => $optionId,
                    'price' => ['currencyId' => Defaults::CURRENCY, 'gross' => 50, 'net' => 25, 'linked' => false],
                    'option' => [
                        'id' => $optionId,
                        'name' => 'red',
                        'group' => [
                            'id' => $groupId,
                            'name' => $groupId,
                            'translated' => [
                                'name' => $groupId
                            ]
                        ],
                    ],
                ]
            ],
        ];
    }

    public function getDefaultCoverData(): array
    {
        return [
            'media' => [
                'url' => 'https://via.placeholder.com/1000',
                'private' => false,
                'mediaType' => ['name' => 'IMAGE'],
                'mimeType' => 'image/png',
                'fileExtension' => 'png',
                'fileName' => 'findologic',
                'thumbnails' => [
                    [
                        'width' => 600,
                        'height' => 600,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/600'
                    ]
                ]
            ],
        ];
    }

    public function getDefaultCategories(): CategoryCollection
    {
        $categoryId = Uuid::randomHex();
        $childId = Uuid::randomHex();

        $category1 = Entity::createFromArray(CategoryEntity::class, [
            'id' => $categoryId,
            'parentId' => $this->navigationCategoryId,
            'name' => 'FINDOLOGIC Category',
            'active' => true,
            'seoUrls' => [
                [
                    'pathInfo' => 'navigation/' . $categoryId,
                    'seoPathInfo' => 'Findologic-Category',
                    'isCanonical' => true,
                    'routeName' => 'frontend.navigation.page'
                ]
            ]
        ]);
        $category2 = Entity::createFromArray(CategoryEntity::class, [
            'id' => Uuid::randomHex(),
            'name' => 'FINDOLOGIC Main 2',
            'active' => true,
            'children' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'FINDOLOGIC Sub',
                    'active' => true,
                    'children' => [
                        [
                            'id' => Uuid::randomHex(),
                            'name' => 'Very deep',
                            'active' => true,
                        ],
                        [
                            'id' => $childId,
                            'name' => 'FINDOLOGIC Sub of Sub',
                            'active' => true,
                        ]
                    ]
                ]
            ]
        ]);

        return $this->generateProductCategoriesWithRelations(
            new CategoryCollection([$category1, $category2]),
            [$categoryId, $childId]
        );
    }

    public function buildCustomCategories(array $categories): CategoryCollection
    {
        $categoryCollection = new CategoryCollection();

        foreach ($categories as $categoryData) {
            /** @var CategoryEntity $category */
            $category = Entity::createFromArray(CategoryEntity::class, $categoryData);

            $categoryCollection->add($category);
        }

        return $this->generateProductCategoriesWithRelations(
            $categoryCollection,
        );
    }
}
