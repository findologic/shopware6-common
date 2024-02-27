<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Price;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoPricesException;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class PriceAdapter implements AdapterInterface
{
    protected ExportContext $exportContext;

    public function __construct(ExportContext $exportContext)
    {
        $this->exportContext = $exportContext;
    }

    /**
     * @return Price[]
     * @throws ProductHasNoPricesException
     */
    public function adapt(ProductEntity $product): array
    {
        $prices = $this->getPricesFromProduct($product);
        if (Utils::isEmpty($prices)) {
            throw new ProductHasNoPricesException($product);
        }

        return $prices;
    }

    /**
     * @return Price[]
     */
    protected function getPricesFromProduct(ProductEntity $product): array
    {
        $prices = [];
        $productPrices = $product->price;
        if (!$productPrices || !$productPrices[0]) {
            return [];
        }

        $currencyPrice = Utils::getCurrencyPrice($productPrices, $this->exportContext->getCurrencyId());

        // If no currency price is available, fallback to the default price.
        if (!$currencyPrice) {
            $currencyPrice = $productPrices[0];
        }

        foreach ($this->exportContext->getCustomerGroups() as $customerGroup) {
            $userGroupHash = $customerGroup->id;
            if (Utils::isEmpty($userGroupHash)) {
                continue;
            }

            $netPrice = $currencyPrice['net'];
            $grossPrice = $currencyPrice['gross'];
            $price = new Price();
            if ($customerGroup->displayGross) {
                $price->setValue(round($grossPrice, 2), $userGroupHash);
            } else {
                $price->setValue(round($netPrice, 2), $userGroupHash);
            }

            $prices[] = $price;
        }

        $price = new Price();
        $price->setValue(round($currencyPrice['gross'], 2));
        $prices[] = $price;

        return $prices;
    }
}
