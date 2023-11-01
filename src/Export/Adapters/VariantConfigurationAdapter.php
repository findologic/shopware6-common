<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class VariantConfigurationAdapter
{
    private array $matchingOriginalAttributes = [];

    public function adapt(ProductEntity $product, AdapterFactory $adapterFactory): array
    {
        foreach ($product->configuratorGroupConfig as $attribute) {
            if (!$attribute['expressionForListings']) {
                $this->processConfigurationAttribute($product, $attribute, $adapterFactory);
            }
        }

        return $this->matchingOriginalAttributes;
    }

    private function processConfigurationAttribute(
        ProductEntity $product,
        array $attribute,
        AdapterFactory $adapterFactory,
    ): void {
        $optionAttributes = $product->options->getElements();
        $matchingOptionAttributes = $this->filterOptionAttributes($optionAttributes, $attribute);

        $originalAttributes = $adapterFactory->getAttributeAdapter()->adapt($product);

        $this->matchingOriginalAttributes += $this->filterOriginalAttributes(
            $originalAttributes,
            $matchingOptionAttributes,
        );
    }

    private function filterOptionAttributes(array $optionAttributes, array $attribute): array
    {
        return array_filter(
            $optionAttributes,
            fn ($optionAttribute) => $attribute['id'] == $optionAttribute->groupId,
        );
    }

    private function filterOriginalAttributes(array $originalAttributes, array $matchingOptionAttributes): array
    {
        $optionAttributeNames = array_map(
            fn ($optionAttribute) => $optionAttribute->name,
            $matchingOptionAttributes,
        );

        return array_filter(
            $originalAttributes,
            function ($originalAttribute) use ($optionAttributeNames) {
                return in_array($originalAttribute->getValues()[0], $optionAttributeNames);
            },
        );
    }
}
