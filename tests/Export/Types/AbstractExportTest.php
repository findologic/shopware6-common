<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Types;

use FINDOLOGIC\Shopware6Common\Export\Enums\ExportType;
use FINDOLOGIC\Shopware6Common\Export\Types\AbstractExport;
use FINDOLOGIC\Shopware6Common\Export\Types\ProductIdExport;
use FINDOLOGIC\Shopware6Common\Export\Types\XmlExport;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ServicesHelper;
use PHPUnit\Framework\TestCase;

class AbstractExportTest extends TestCase
{
    use AdapterHelper;
    use ServicesHelper;

    public function exportProvider(): array
    {
        return [
            'XML export' => [
                'type' => ExportType::XML,
                'expectedInstance' => XmlExport::class,
            ],
            'Product ID export' => [
                'type' => ExportType::DEBUG,
                'expectedInstance' => ProductIdExport::class,
            ],
        ];
    }

    /**
     * @dataProvider exportProvider
     */
    public function testProperInstanceIsCreated(ExportType $type, string $expectedInstance): void
    {
        $export = AbstractExport::getInstance(
            $type,
            $this->getDynamicProductGroupServiceMock(),
            $this->getProductSearcherMock(),
            $this->getPluginConfig(),
            $this->getExportItemAdapter(),
            $this->getLogger(),
        );

        $this->assertInstanceOf($expectedInstance, $export);
    }
}
