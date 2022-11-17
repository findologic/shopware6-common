<?php

declare(strict_types=1);

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
        $defaultCatUrl = '';

        foreach ($productEntity->categories as $category) {
            if ($category->name === 'FINDOLOGIC Category') {
                $defaultCatUrl = sprintf('/navigation/%s', $category->id);
            }
        }

        $attributes = [];
        $catUrlAttribute = new Attribute('cat_url', [$defaultCatUrl]);
        $catAttribute = new Attribute('cat', ['FINDOLOGIC Category']);
        $vendorAttribute = new Attribute('vendor', ['FINDOLOGIC']);

        if ($integrationType === 'Direct Integration') {
            $attributes[] = $catUrlAttribute;
        }

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
                    ->name,
            ],
        );

        foreach ($productEntity->options ?? [] as $option) {
            $attributes[] = new Attribute(
                $option->group->name,
                [$option->name],
            );
        }

        $shippingFree = $this->translateBooleanValue((bool) $productEntity->shippingFree);
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
