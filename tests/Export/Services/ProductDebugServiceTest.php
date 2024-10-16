<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Services;

use FINDOLOGIC\Export\XML\XMLItem;
use FINDOLOGIC\Shopware6Common\Export\Constants;
use FINDOLOGIC\Shopware6Common\Export\Errors\ExportErrors;
use FINDOLOGIC\Shopware6Common\Export\Search\ProductDebugSearcherInterface;
use FINDOLOGIC\Shopware6Common\Export\Services\ProductDebugService;
use FINDOLOGIC\Shopware6Common\Tests\CommonConstants;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ServicesHelper;
use PHPUnit\Framework\TestCase;
use Vin\ShopwareSdk\Data\Criteria;
use Vin\ShopwareSdk\Data\Uuid\Uuid;

class ProductDebugServiceTest extends TestCase
{
    use ProductHelper;
    use ServicesHelper;

    public ProductDebugService $productDebugService;

    public function setUp(): void
    {
        parent::setUp();

        $productCriteriaBuilder = $this->getProductCriteriaBuilderMock();

        $productDebugSearcher = $this->createMock(ProductDebugSearcherInterface::class);

        $productDebugSearcher->expects($this->once())
            ->method('getProductById')
            ->willReturnCallback(fn (string $productId) => $this->createTestProduct([
                'id' => $productId,
                'parentId' => Uuid::randomHex(),
            ]));
        $productDebugSearcher->expects($this->once())
            ->method('buildCriteria')
            ->willReturnCallback(static function () {
                $criteria = new Criteria();
                $criteria->addAssociations(Constants::PRODUCT_ASSOCIATIONS);

                return $criteria;
            });
        $productDebugSearcher->expects($this->once())
            ->method('getSiblings')
            ->willReturnCallback(fn (string $parentId, int $count) => [
                $this->createTestProduct(),
                $this->createTestProduct(),
                $this->createTestProduct(),
            ]);

        $this->productDebugService = new ProductDebugService(
            $this->getExportContext(),
            $productDebugSearcher,
            $productCriteriaBuilder,
            'export',
        );
    }

    public function testProductIdIsSet(): void
    {
        $productId = Uuid::randomHex();

        $data = $this->getDebugInformation($productId);

        $this->assertSame(
            $data['export']['productId'],
            $productId,
        );
    }

    public function testExportedMainProductIdIsSet(): void
    {
        $productId = Uuid::randomHex();
        $mainProductId = Uuid::randomHex();

        $data = $this->getDebugInformation($productId, $mainProductId);

        $this->assertSame(
            $data['export']['productId'],
            $productId,
        );
        $this->assertSame(
            $data['export']['exportedMainProductId'],
            $mainProductId,
        );
    }

    public function testIsExportedFalseWithoutXmlItem(): void
    {
        $productId = Uuid::randomHex();
        $mainProductId = Uuid::randomHex();

        $data = $this->getDebugInformation($productId, $mainProductId, null);

        $this->assertFalse($data['export']['isExported']);
        $this->assertContains(
            'Product is not visible for search',
            $data['export']['reasons'],
        );
    }

    public function testWithDifferentProduct(): void
    {
        $productId = Uuid::randomHex();
        $mainProductId = Uuid::randomHex();

        $data = $this->getDebugInformation($productId, $mainProductId);

        $this->assertFalse($data['export']['isExported']);
        $this->assertContains(
            'Product is not visible for search',
            $data['export']['reasons'],
        );
        $this->assertContains(
            'Product is not the exported variant.',
            $data['export']['reasons'],
        );

        $this->assertStringContainsString($mainProductId, $data['debugLinks']['exportUrl']);
        $this->assertStringContainsString($mainProductId, $data['debugLinks']['debugUrl']);

        $this->assertFalse($data['data']['isExportedMainVariant']);
        $this->assertSame($productId, $data['data']['product']['id']);
    }

    public function testWithCorrectProductGiven(): void
    {
        $productId = Uuid::randomHex();

        $data = $this->getDebugInformation($productId, $productId);

        $this->assertTrue($data['export']['isExported']);
        $this->assertEmpty($data['export']['reasons']);

        $this->assertStringContainsString($productId, $data['debugLinks']['exportUrl']);
        $this->assertStringContainsString($productId, $data['debugLinks']['debugUrl']);

        $this->assertTrue($data['data']['isExportedMainVariant']);
        $this->assertSame($productId, $data['data']['product']['id']);
    }

    public function testSiblingsAreSet(): void
    {
        $product = $this->createTestProduct(['parentId' => Uuid::randomHex()]);

        $xmlItem = new XMLItem($product->id);

        $data = $this->productDebugService->getDebugInformation(
            $product->id,
            CommonConstants::VALID_SHOPKEY,
            $xmlItem,
            $product,
            new ExportErrors(),
        )->getContent();
        $json = json_decode($data, true);

        $this->assertCount(3, $json['data']['siblings']);
    }

    public function testAssociationsSet(): void
    {
        $data = $this->getDebugInformation();

        $this->assertNotEmpty($data['data']['associations']);
    }

    private function getDebugInformation(
        ?string $productId = null,
        ?string $mainProductId = null,
        ?bool $withXmlItem = true
    ): array {
        $product = $this->createTestProduct([
            'id' => $productId ?? Uuid::randomHex(),
            'productNumber' => 'FINDOLOGIC1',
        ]);

        $mainProduct = $productId === $mainProductId
            ? $product
            : $this->createTestProduct([
                'id' => $mainProductId ?? Uuid::randomHex(),
                'productNumber' => 'FINDOLOGIC2',
            ]);
        $xmlItem = $withXmlItem ? new XMLItem($mainProduct->id) : null;

        $data = $this->productDebugService->getDebugInformation(
            $product->id,
            CommonConstants::VALID_SHOPKEY,
            $xmlItem,
            $mainProduct,
            new ExportErrors(),
        )->getContent();

        return json_decode($data, true);
    }
}
