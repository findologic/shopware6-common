<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Traits;

use FINDOLOGIC\Export\Data\Attribute;
use FINDOLOGIC\Shopware6Common\Export\Config\IntegrationType;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

trait AttributeHelper
{
    /**
     * @return Attribute[]
     */
    public function getAttributes(ProductEntity $productEntity, string $integrationType = IntegrationType::DI): array
    {
        $catUrl1 = '/FINDOLOGIC-Category/';
        $defaultCatUrl = '';

        foreach ($productEntity->categories as $category) {
            if ($category->name === 'FINDOLOGIC Category') {
                $defaultCatUrl = sprintf('/navigation/%s', $category->id);
            }
        }

        $attributes = [];
        $catUrlAttribute = new Attribute('cat_url', [$catUrl1, $defaultCatUrl]);
        $catAttribute = new Attribute('cat', ['FINDOLOGIC Category']);
        $vendorAttribute = new Attribute('vendor', ['FINDOLOGIC']);

//        // TODO: cat_url implementation
//        if ($integrationType === 'Direct Integration') {
//            $attributes[] = $catUrlAttribute;
//        }

        $attributes[] = $catAttribute;
        $attributes[] = $vendorAttribute;
        $attributes[] = new Attribute(
            $productEntity->properties
                ->first()
                ->group
                ->name,
            [
                $productEntity->properties
                    ->first()
                    ->name
            ]
        );

        $shippingFree = $this->translateBooleanValue(!!$productEntity->shippingFree);
        $attributes[] = new Attribute('shipping_free', [$shippingFree]);

        $rating = $productEntity->ratingAverage ?? 0.0;
        $attributes[] = new Attribute('rating', [$rating]);

        // Custom fields as attributes
        $productFields = $productEntity->getCustomFields();
        if ($productFields) {
            foreach ($productFields as $key => $value) {
                if (is_bool($value)) {
                    $value = $this->translateBooleanValue($value);
                }
                $attributes[] = new Attribute(Utils::removeSpecialChars($key), [$value]);
            }
        }

        return $attributes;
    }

    private function translateBooleanValue(bool $value): string
    {
        $translationKey = $value ? 'finSearch.general.yes' : 'finSearch.general.no';

        return $value ? 'Yes' : 'No';
    }
}
