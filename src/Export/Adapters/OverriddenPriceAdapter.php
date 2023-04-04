<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\OverriddenPrice;
use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class OverriddenPriceAdapter
{
    public function __construct(
        protected readonly ExportContext $exportContext,
        protected readonly PluginConfig $pluginConfig,
    ) {
    }

    /**
     * @return OverriddenPrice[]
     */
    public function adapt(ProductEntity $product): array
    {
        return $this->getOverriddenPricesFromProduct($product);
    }

    /**
     * @return OverriddenPrice[]
     */
    protected function getOverriddenPricesFromProduct(ProductEntity $product): array
    {
        $overriddenPrices = [];
        $productPrices = $product->price;
        if (!$productPrices || !$productPrices[0]) {
            return [];
        }

        $currencyPrice = Utils::getCurrencyPrice($productPrices, $this->exportContext->getCurrencyId());

        // If no currency price is available, fallback to the default price.
        if (!$currencyPrice) {
            $currencyPrice = $productPrices[0];
        }

        if (!$listPrice = $currencyPrice['listPrice']) {
            return [];
        }

        if (!$this->pluginConfig->useXmlVariants()) {
            foreach ($this->exportContext->getCustomerGroups() as $customerGroup) {
                $userGroupHash = $customerGroup->id;
                if (Utils::isEmpty($userGroupHash)) {
                    continue;
                }

                $netPrice = $listPrice['net'];
                $grossPrice = $listPrice['gross'];
                $overriddenPrice = new OverriddenPrice();
                if ($customerGroup->displayGross) {
                    $overriddenPrice->setValue(round($grossPrice, 2), $userGroupHash);
                } else {
                    $overriddenPrice->setValue(round($netPrice, 2), $userGroupHash);
                }

                $overriddenPrices[] = $overriddenPrice;
            }
        }

        $overriddenPrice = new OverriddenPrice();
        $overriddenPrice->setValue(round($listPrice['gross'], 2));
        $overriddenPrices[] = $overriddenPrice;

        return $overriddenPrices;
    }
}
