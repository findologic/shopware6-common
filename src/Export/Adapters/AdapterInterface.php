<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

interface AdapterInterface
{
    public function adapt(ProductEntity $product);
}