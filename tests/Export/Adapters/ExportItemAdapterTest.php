<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use Exception;
use FINDOLOGIC\Export\Exceptions\EmptyValueNotAllowedException;
use FINDOLOGIC\Export\XML\XMLItem;
use FINDOLOGIC\Export\XML\XmlVariant;
use FINDOLOGIC\Shopware6Common\Export\Adapters\AdapterFactory;
use FINDOLOGIC\Shopware6Common\Export\Adapters\AttributeAdapter;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoCategoriesException;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ServicesHelper;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Throwable;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;
use Vin\ShopwareSdk\Data\Uuid\Uuid;

class ExportItemAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;
    use ServicesHelper;

    public function testEventsAreDispatched(): void
    {
        $xmlItem = new XMLItem(Uuid::randomHex());
        $xmlVariant = new XmlVariant(Uuid::randomHex(), Uuid::randomHex());
        $product = $this->createTestProduct();

        $eventDispatcherMock = $this->getEventDispatcherMock();
        $eventDispatcherMock->expects($this->exactly(6))
            ->method('dispatch')
            ->with();

        /** @var EventDispatcher $eventDispatcherMock */
        $adapter = $this->getExportItemAdapter(null, null, null, $eventDispatcherMock);
        $adapter->adapt($xmlItem, $product);
        $adapter->adaptVariant($xmlItem, $product);
        $adapter->adaptXmlVariant($xmlVariant, $product);
    }

    public function testExceptionIsThrownForProductWithNoCategories(): void
    {
        $this->expectException(ProductHasNoCategoriesException::class);

        $id = Uuid::randomHex();
        $product = $this->createTestProduct([
            'id' => $id,
            'categories' => [],
        ]);

        // Test.

        $adapter = $this->getExportItemAdapter();
        $adapter->adaptProduct(new XMLItem($id), $product);
    }

    public function testExceptionIsNotThrownForVariantWithNoCategories(): void
    {
        $id = Uuid::randomHex();
        $product = $this->createTestProduct([
            'id' => $id,
            'categories' => [],
        ]);

        $adapter = $this->getExportItemAdapter();
        $item = $adapter->adaptVariant(new XMLItem($id), $product);

        $this->assertEquals($id, $item->getId());
    }

    public function testExceptionIsNotThrownForXmlVariantWithNoCategories(): void
    {
        $id = Uuid::randomHex();
        $parentId = Uuid::randomHex();
        $product = $this->createTestProduct([
            'id' => $id,
            'categories' => [],
        ]);

        $adapter = $this->getExportItemAdapter();
        $item = $adapter->adaptXmlVariant(new XmlVariant($id, $parentId), $product);

        $this->assertEquals($id, $item->getId());
    }

    public function testProductInvalidExceptionIsLogged(): void
    {
        $productEntity = $this->createTestProduct();

        $expectedMessage = sprintf(
            'Product "%s" with id %s was not exported because it has no categories assigned',
            $productEntity->getTranslation('name'),
            $productEntity->id,
        );

        $this->expectAdapterException(
            $productEntity,
            new ProductHasNoCategoriesException($productEntity),
            $expectedMessage,
        );
    }

    public function testEmptyValueIsNotAllowedExceptionIsLogged(): void
    {
        $productEntity = $this->createTestProduct();

        $error = sprintf(
            'Product "%s" with id "%s" could not be exported.',
            $productEntity->getTranslation('name'),
            $productEntity->id,
        );
        $reason = 'It appears to have empty values assigned to it.';
        $help = 'If you see this message in your logs, please report this as a bug.';
        $expectedMessage = implode(' ', [$error, $reason, $help]);

        $this->expectAdapterException(
            $productEntity,
            new EmptyValueNotAllowedException(''),
            $expectedMessage,
        );
    }

    public function testThrowableExceptionIsLogged(): void
    {
        $errorMessage = 'This product failed, because it is faulty.';
        $productEntity = $this->createTestProduct();

        $error = sprintf(
            'Error while exporting the product "%s" with id "%s".',
            $productEntity->getTranslation('name'),
            $productEntity->id,
        );
        $help = 'If you see this message in your logs, please report this as a bug.';
        $reason = sprintf('Error message: %s', $errorMessage);
        $expectedMessage = implode(' ', [$error, $help, $reason]);

        $this->expectAdapterException(
            $productEntity,
            new Exception($errorMessage),
            $expectedMessage,
        );
    }

    public function expectAdapterException(ProductEntity $productEntity, Throwable $throwable, string $message): void
    {
        $attributeAdapterMock = $this->getMockBuilder(AttributeAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeAdapterMock->expects($this->once())
            ->method('adapt')
            ->willThrowException($throwable);

        $adapterFactoryMock = $this->getMockBuilder(AdapterFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adapterFactoryMock->expects($this->once())
            ->method('getAttributeAdapter')
            ->willReturn($attributeAdapterMock);

        $loggerMock = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock->expects($this->exactly(1))
            ->method('warning')
            ->with($message);

        $adapter = $this->getExportItemAdapter($adapterFactoryMock, null, $loggerMock);
        $adapter->adapt(new XMLItem('123'), $productEntity);
    }
}
