<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\DateAdded;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class DateAddedAdapter
{
    public function adapt(ProductEntity $product): ?DateAdded
    {
        if (!$releaseDate = $product->releaseDate) {
            return null;
        }

        $dateAdded = new DateAdded();
        $dateAdded->setDateValue($releaseDate);

        return $dateAdded;
    }
}
