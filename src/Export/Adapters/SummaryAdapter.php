<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Summary;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class SummaryAdapter implements AdapterInterface
{
    public function adapt(ProductEntity $product): ?Summary
    {
        return null;
    }
}
