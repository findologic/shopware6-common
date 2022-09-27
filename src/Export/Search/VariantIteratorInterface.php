<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Search;

use Vin\ShopwareSdk\Data\Entity\Product\ProductCollection;

interface VariantIteratorInterface
{
    public function fetch(): ?ProductCollection;
}
