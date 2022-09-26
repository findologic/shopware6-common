<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Types;

use FINDOLOGIC\Shopware6Common\Export\Types\AbstractExport;
use FINDOLOGIC\Shopware6Common\Export\Types\ProductIdExport;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryCollection;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryEntity;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class ProductIdExportTest extends XmlExportTest
{
    public function buildItemsAndAssertError(ProductEntity $product, CategoryEntity $category): void
    {
        $product = $this->createTestProduct();

        $category = $product->categories->first();
        $this->crossSellCategories = [$category->id];

        $exporter = $this->getExport();
        $items = $exporter->buildItems([$product]);
        $this->getExport()->buildResponse($items, 0, 1);

        $errors = $exporter->getErrorHandler()->getExportErrors()->getProductError($product->id)->getErrors();

        $this->assertEmpty($items);
        $this->assertCount(1, $errors);
        $this->assertEquals(
            sprintf(
                'Product with id %s (%s) was not exported because it is assigned to cross selling category %s (%s)',
                $product->id,
                $product->getTranslation('name'),
                $category->id,
                implode(' > ', $category->breadcrumb)
            ),
            $errors[0]
        );
    }

    public function testWarnsIfNoProductsAreReceived(): void
    {
        $export = $this->getExport();

        $items = $export->buildItems([]);
        $this->getExport()->buildResponse($items, 0, 1);

        $errors = $export->getErrorHandler()->getExportErrors()->getGeneralErrors();
        $this->assertEmpty($items);
        $this->assertCount(1, $errors);
        $this->assertSame('Product could not be found or is not available for search.', $errors[0]);
    }

    public function testProductCanNotBeExported(): void
    {
        $export = $this->getExport();
        $product = $this->createTestProduct();
        $product->categories = new CategoryCollection();

        $items = $export->buildItems([$product]);
        $response = $export->buildResponse($items, 0, 200);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));
        $errors = json_decode($response->getContent(), true);

        $expectedName = 'FINDOLOGIC Product';
        $expectedErrors = [
            'general' => [],
            'products' => [
                [
                    'id' => $product->id,
                    'errors' => [
                        sprintf(
                            'Product "%s" with id %s was not exported because it has no categories assigned',
                            $expectedName,
                            $product->id
                        )
                    ]
                ]
            ]
        ];

        $this->assertSame($expectedErrors, $errors);
    }

    /**
     * @return ProductIdExport
     */
    protected function getExport(): AbstractExport
    {
        return new ProductIdExport(
            $this->dynamicProductGroupService,
            $this->productSearcher,
            $this->getPluginConfig(['crossSellingCategories' => $this->crossSellCategories]),
            $this->getExportItemAdapter(null, null, $this->logger),
            $this->logger,
            $this->getEventDispatcherMock()
        );
    }
}
