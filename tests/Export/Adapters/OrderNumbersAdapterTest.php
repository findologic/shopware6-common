<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Export\Data\Ordernumber;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\TestCase;
use Vin\ShopwareSdk\Data\Uuid\Uuid;

class OrderNumbersAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;

    public function testOrderNumberContainsTheOrderNumberOfTheProduct(): void
    {
        $variantProductNumber = Uuid::randomHex();
        $expectedOrderNumbers = [
            new Ordernumber('FINDOLOGIC001'),
            new Ordernumber('FL001'),
            new Ordernumber('MAN001'),
            new Ordernumber($variantProductNumber),
            new Ordernumber('childEan'),
            new Ordernumber('MAN002'),
        ];

        $product = $this->createTestProduct([
            'id' => Uuid::randomHex(),
        ]);

        $variantProduct = $this->createTestProduct([
            'id' => Uuid::randomHex(),
            'parentId' => $product->id,
            'productNumber' => $variantProductNumber,
            'ean' => 'childEan',
            'manufacturerNumber' => 'MAN002',
        ]);
        $variantProduct->parent = $product;

        $adapter = $this->getOrderNumberAdapter();
        $parentOrderNumbers = $adapter->adapt($product);
        $variantOrderNumbers = $adapter->adapt($variantProduct);

        $orderNumbers = array_merge($parentOrderNumbers, $variantOrderNumbers);
        $this->assertEquals($expectedOrderNumbers, $orderNumbers);
    }
}
