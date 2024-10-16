<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Attribute;
use FINDOLOGIC\Shopware6Common\Export\Enums\MainVariant;
use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use FINDOLOGIC\Shopware6Common\Traits\AdapterHelper;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;
use Vin\ShopwareSdk\Data\Entity\PropertyGroupOption\PropertyGroupOptionEntity;

class VariantConfigurationAdapter implements AdapterInterface
{
    use AdapterHelper;

    protected PluginConfig $pluginConfig;

    public function __construct(PluginConfig $pluginConfig)
    {
        $this->pluginConfig = $pluginConfig;
    }

    /**
     * @return Attribute[]
     */
    public function adapt(ProductEntity $product): array
    {
        $options = $product->options;

        if (!$options->count()) {
            return [];
        }

        $variantlisting = array_filter(
            $product->variantListingConfig['configuratorGroupConfig'] ?? [],
            function (array $listing) {
                return $listing['expressionForListings'];
            },
        );
        $variantListingGroupId = array_map(fn ($listing) => $listing['id'], $variantlisting);

        if (
            $this->pluginConfig->getMainVariant()->name === MainVariant::SHOPWARE_DEFAULT->name &&
            !$product->variantListingConfig['displayParent'] &&
            count($variantlisting)
        ) {
            $options = $options->filter(function (PropertyGroupOptionEntity $option) use ($variantListingGroupId) {
                return !in_array($option->groupId, $variantListingGroupId);
            });
        }

        return $this->getPropertyGroupOptionAttributes($options);
    }
}