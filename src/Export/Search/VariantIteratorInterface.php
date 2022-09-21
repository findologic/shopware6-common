<?php

namespace FINDOLOGIC\Shopware6Common\Export\Search;

use Vin\ShopwareSdk\Data\Entity\Product\ProductCollection;

interface VariantIteratorInterface
{
    public function fetch(): ?ProductCollection;
}
