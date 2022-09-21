<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\TestCase;

class SalesFrequencyAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;

    public function testSalesFrequencyContainsTheOrderCount(): void
    {
        $expectedSalesFrequency = 1337;

        $productEntity = $this->createTestProduct();

        $adapter = $this->getSalesFrequencyAdapter();
        $actualSalesFrequency = $adapter->adapt($productEntity);

        $this->assertSame($expectedSalesFrequency, $actualSalesFrequency->getValues()['']);
    }
}
