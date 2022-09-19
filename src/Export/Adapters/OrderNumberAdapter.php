<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Ordernumber;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class OrderNumberAdapter
{
    public function adapt(ProductEntity $product): array
    {
        return $this->getOrderNumbers($product);
    }

    protected function getOrderNumbers(ProductEntity $product): array
    {
        $orderNumbers = [];

        if (!Utils::isEmpty($product->productNumber)) {
            $orderNumbers[] = new Ordernumber($product->productNumber);
        }

        if (!Utils::isEmpty($product->ean)) {
            $orderNumbers[] = new Ordernumber($product->ean);
        }

        if (!Utils::isEmpty($product->manufacturerNumber)) {
            $orderNumbers[] = new Ordernumber($product->manufacturerNumber);
        }

        return $orderNumbers;
    }
}
