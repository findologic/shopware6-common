<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\TestCase;

class DescriptionAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;

    public function testDescriptionContainsTheDescriptionOfTheProduct(): void
    {
        $expectedDescription = 'FINDOLOGIC Description';
        $product = $this->createTestProduct();

        $description = $this->getDescriptionAdapter()->adapt($product);

        $this->assertCount(1, $description->getValues());
        $this->assertSame($expectedDescription, $description->getValues()['']);
    }

    /**
     * @dataProvider emptyValuesProvider
     */
    public function testEmptyDescriptionValuesAreSkipped(?string $value): void
    {
        $data = [
            'description' => $value,
            'translated' => [
                'description' => $value,
            ],
        ];

        $product = $this->createTestProduct($data);
        $description = $this->getDescriptionAdapter()->adapt($product);

        $this->assertNull($description);
    }

    public function emptyValuesProvider(): array
    {
        return [
            'null values provided' => ['value' => null],
            'empty string values provided' => ['value' => ''],
            'values containing empty spaces provided' => ['value' => '    '],
        ];
    }
}
