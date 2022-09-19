<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\SalesFrequency;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

abstract class AbstractSalesFrequencyAdapter
{
    public function adapt(ProductEntity $product): ?SalesFrequency
    {
        $salesFrequency = new SalesFrequency();
        $salesFrequency->setValue($this->getOrderCount($product));

        return $salesFrequency;
    }

    abstract protected function getOrderCount(ProductEntity $product): int;
}
