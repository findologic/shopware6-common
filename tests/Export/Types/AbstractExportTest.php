<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Types;

use FINDOLOGIC\Shopware6Common\Export\Types\AbstractExport;
use FINDOLOGIC\Shopware6Common\Export\Types\ProductIdExport;
use FINDOLOGIC\Shopware6Common\Export\Types\XmlExport;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ServicesHelper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AbstractExportTest extends TestCase
{
    use AdapterHelper;
    use ServicesHelper;

    public function exportProvider(): array
    {
        return [
            'XML export' => [
                'type' => AbstractExport::TYPE_XML,
                'expectedInstance' => XmlExport::class
            ],
            'Product ID export' => [
                'type' => AbstractExport::TYPE_PRODUCT_ID,
                'expectedInstance' => ProductIdExport::class
            ]
        ];
    }

    /**
     * @dataProvider exportProvider
     */
    public function testProperInstanceIsCreated(int $type, string $expectedInstance): void
    {
        $export = AbstractExport::getInstance(
            $type,
            $this->getDynamicProductGroupServiceMock(),
            $this->getProductSearcherMock(),
            $this->getPluginConfig(),
            $this->getExportItemAdapter(),
            $this->getLogger()
        );

        $this->assertInstanceOf($expectedInstance, $export);
    }

    public function testUnknownInstanceThrowsException(): void
    {
        $unknownExportType = 420;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unknown export type %d.', $unknownExportType));

        AbstractExport::getInstance(
            $unknownExportType,
            $this->getDynamicProductGroupServiceMock(),
            $this->getProductSearcherMock(),
            $this->getPluginConfig(),
            $this->getExportItemAdapter(),
            $this->getLogger()
        );
    }
}
