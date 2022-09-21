<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use DateTime;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\TestCase;

class DateAddedAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;

    public function testDateAddedIsBasedOnReleaseDate(): void
    {
        $releaseDate = DateTime::createFromFormat(DATE_ATOM, '2021-11-09T16:00:00+00:00');

        $product = $this->createTestProduct();
        $product->releaseDate = $releaseDate;

        $dateAdded = $this->getDateAddedAdapter()->adapt($product);

        $this->assertSame($releaseDate->format(DATE_ATOM), $dateAdded->getValues()['']);
    }
}
