<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Types;

use FINDOLOGIC\Shopware6Common\Export\Logger\Handler\ProductErrorHandler;
use FINDOLOGIC\Shopware6Common\Export\Search\AbstractProductSearcher;
use FINDOLOGIC\Shopware6Common\Export\Search\VariantIteratorInterface;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractDynamicProductGroupService;
use FINDOLOGIC\Shopware6Common\Export\Types\AbstractExport;
use FINDOLOGIC\Shopware6Common\Export\Types\XmlExport;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ServicesHelper;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryCollection;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryEntity;
use Vin\ShopwareSdk\Data\Entity\Product\ProductCollection;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class XmlExportTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;
    use ServicesHelper;

    protected AbstractDynamicProductGroupService|MockObject $dynamicProductGroupService;

    protected AbstractProductSearcher|MockObject $productSearcher;

    protected ProductErrorHandler $productErrorHandler;

    protected Logger $logger;

    /** @var string[] */
    protected array $crossSellCategories = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->dynamicProductGroupService = $this->getDynamicProductGroupServiceMock();
        $this->productSearcher = $this->getProductSearcherMock();

        $this->productErrorHandler = new ProductErrorHandler();
        $this->logger = $this->getLogger();
    }

    public function testWrapsItemProperly(): void
    {
        $product = $this->createTestProduct();

        $items = $this->getExport()->buildItems([$product]);
        $this->getExport()->buildResponse($items, 0, 1);

        $this->assertCount(1, $items);
        $this->assertSame($product->id, $items[0]->getId());
    }

    public function testManuallyAssignedProductsInCrossSellCategoriesAreNotWrappedAndErrorIsLogged(): void
    {
        $product = $this->createTestProduct(['productNumber' => 'FINDOLOGIC1']);

        $category = $product->categories->first();
        $this->crossSellCategories = [$category->id];

        $this->dynamicProductGroupService
            ->expects($this->once())
            ->method('getCategories')
            ->willReturn(new CategoryCollection());

        $this->buildItemsAndAssertError($product, $category);
    }

    public function testProductsInDynamicProductGroupCrossSellCategoriesAreNotWrappedAndErrorIsLogged(): void
    {
        $product = $this->createTestProduct(['productNumber' => 'FINDOLOGIC1']);

        $category = $this->createTestCategory();
        $this->crossSellCategories = [$category->id];

        $categoryCollection = new CategoryCollection([$category]);
        $this->dynamicProductGroupService->expects($this->any())
            ->method('getCategories')
            ->willReturn($categoryCollection);

        $this->buildItemsAndAssertError($product, $category);
    }

    public function buildItemsAndAssertError(ProductEntity $product, CategoryEntity $category)
    {
        $this->logger->pushHandler($this->productErrorHandler);

        $items = $this->getExport()->buildItems([$product]);
        $this->getExport()->buildResponse($items, 0, 1);
        $this->assertEmpty($items);

        $errors = $this->productErrorHandler->getExportErrors()->getProductError($product->id)->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals(
            sprintf(
                'Product with id %s (%s) was not exported because it is assigned to cross selling category %s (%s)',
                $product->id,
                $product->name,
                $category->id,
                implode(' > ', $category->breadcrumb),
            ),
            $errors[0],
        );
    }

    public function testKeywordsAreNotRequired(): void
    {
        $product = $this->createTestProduct(['tags' => []]);
        $items = $this->getExport()->buildItems([$product]);
        $this->getExport()->buildResponse($items, 0, 1);

        $this->assertCount(1, $items);
        $this->assertSame($product->id, $items[0]->getId());
    }

    public function testFallbackToValidVariant(): void
    {
        $product = $this->createTestProduct();
        $product->categories = new CategoryCollection();

        $firstChild = $this->createTestProduct();
        $firstChild->categories = new CategoryCollection();

        $children = new ProductCollection([
            $firstChild,
            $this->createTestProduct(),
            $this->createTestProduct(),
        ]);

        $variantIteratorMock = $this->getMockBuilder(VariantIteratorInterface::class)
            ->getMock();
        $variantIteratorMock->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls($children, null);

        $this->productSearcher->expects($this->once())
            ->method('buildVariantIterator')
            ->willReturn($variantIteratorMock);

        $items = $this->getExport()->buildItems([$product]);
        $this->getExport()->buildResponse($items, 0, 1);

        $this->assertCount(1, $items);
        $this->assertNotEquals($product->id, $items[0]->getId());
    }

    /**
     * @return XmlExport
     */
    protected function getExport(): AbstractExport
    {
        return new XmlExport(
            $this->dynamicProductGroupService,
            $this->productSearcher,
            $this->getPluginConfig(['crossSellingCategories' => $this->crossSellCategories]),
            $this->getExportItemAdapter(),
            $this->logger,
            $this->getEventDispatcherMock(),
        );
    }
}
