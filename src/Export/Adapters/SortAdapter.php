<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Sort;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class SortAdapter implements AdapterInterface
{
    public function adapt(ProductEntity $product): ?Sort
    {
        return null;
    }
}
