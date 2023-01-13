<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Description;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class DescriptionAdapter
{
    public function adapt(ProductEntity $product): ?Description
    {
        if (!$descriptionValue = $this->getDescription($product)) {
            return null;
        }

        $description = new Description();
        $description->setValue($descriptionValue);

        return $description;
    }

    protected function getDescription(ProductEntity $product): ?string
    {
        $description = $product->getTranslation('description');
        if (Utils::isEmpty($description)) {
            return null;
        }

        $description = preg_replace('/[\x00-\x1F\x7F]/u', '', $description);
        if (Utils::isEmpty($description)) {
            return null;
        }

        return $description;
    }
}
