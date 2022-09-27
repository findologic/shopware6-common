<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoNameException;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\TestCase;

class NameAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;

    public function testExceptionIsThrownIfNameOnlyContainsWhiteSpaces(): void
    {
        $this->expectException(ProductHasNoNameException::class);

        $product = $this->createTestProduct();

        // Setting an empty name does not pass the builder validation
        $product->name = null;
        $product->setTranslated([]);

        $this->getNameAdapter()->adapt($product);
    }

    public function testNameContainsTheNameOfTheProduct(): void
    {
        $productName = 'Best product that has ever existed';

        $product = $this->createTestProduct([
            'name' => $productName,
            'translated' => [
                'name' => $productName,
            ],
        ]);

        $name = $this->getNameAdapter()->adapt($product);

        $this->assertCount(1, $name->getValues());
        $this->assertSame($productName, $name->getValues()['']);
    }
}
