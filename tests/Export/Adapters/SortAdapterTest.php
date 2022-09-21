<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\TestCase;

class SortAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;

    public function testNullIsReturned(): void
    {
        $product = $this->createTestProduct();

        $this->assertNull($this->getSortAdapter()->adapt($product));
    }
}
