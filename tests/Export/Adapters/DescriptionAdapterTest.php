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

    public function testControlCharactersAreRemoved(): void
    {
        $value = "Descri\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F" .
            "\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7Fption";
        $data = [
            'description' => $value,
            'translated' => [
                'description' => $value,
            ],
        ];

        $product = $this->createTestProduct($data);
        $description = $this->getDescriptionAdapter()->adapt($product);

        $this->assertEquals('Description', current($description->getValues()));
    }
}
