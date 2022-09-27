<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\TestCase;

class UrlAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;

    public function testUrlContainsTheUrlOfTheProduct(): void
    {
        $expectedUrl = 'https://test.uk/FINDOLOGIC-Product/FINDOLOGIC001';

        $product = $this->createTestProduct();

        $url = $this->getUrlAdapter()->adapt($product);

        $this->assertCount(1, $url->getValues());
        $this->assertSame($expectedUrl, $url->getValues()['']);
    }
}
