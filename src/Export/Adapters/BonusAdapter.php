<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Bonus;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class BonusAdapter
{
    public function adapt(ProductEntity $product): ?Bonus
    {
        return null;
    }
}
