<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Attribute;
use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use FINDOLOGIC\Shopware6Common\Traits\AdapterHelper;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class OptionsAdapter implements AdapterInterface
{
    use AdapterHelper;

    public function __construct(PluginConfig $pluginConfig)
    {
        $this->pluginConfig = $pluginConfig;
    }

    /**
     * @return Attribute[]
     */
    public function adapt(ProductEntity $product): array
    {
        if (!$product->options || !$product->options->count()) {
            return [];
        }

        return $this->getPropertyGroupOptionAttributes($product->options);
    }
}
