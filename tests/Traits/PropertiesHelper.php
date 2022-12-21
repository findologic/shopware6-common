<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Traits;

use FINDOLOGIC\Export\Data\Property;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

trait PropertiesHelper
{
    use ServicesHelper;

    /**
     * @return Property[]
     */
    public function getProperties(ProductEntity $product): array
    {
        $properties = [];

        if ($product->tax) {
            $property = new Property('tax');
            $property->addValue((string) $product->tax->taxRate);
            $properties[] = $property;
        }

        if ($product->purchaseUnit) {
            $property = new Property('purchaseunit');
            $property->addValue((string) $product->purchaseUnit);
            $properties[] = $property;
        }

        if ($product->referenceUnit) {
            $property = new Property('referenceunit');
            $property->addValue((string) $product->referenceUnit);
            $properties[] = $property;
        }

        if ($product->getTranslation('packUnit')) {
            $property = new Property('packunit');
            $property->addValue((string) $product->getTranslation('packUnit'));
            $properties[] = $property;
        }

        if ($product->stock) {
            $property = new Property('stock');
            $property->addValue((string) $product->stock);
            $properties[] = $property;
        }

        if ($product->availableStock) {
            $property = new Property('availableStock');
            $property->addValue((string) $product->availableStock);
            $properties[] = $property;
        }

        if ($product->weight) {
            $property = new Property('weight');
            $property->addValue((string) $product->weight);
            $properties[] = $property;
        }

        if ($product->width) {
            $property = new Property('width');
            $property->addValue((string) $product->width);
            $properties[] = $property;
        }

        if ($product->height) {
            $property = new Property('height');
            $property->addValue((string) $product->height);
            $properties[] = $property;
        }

        if ($product->length) {
            $property = new Property('length');
            $property->addValue((string) $product->length);
            $properties[] = $property;
        }

        if ($product->releaseDate) {
            $property = new Property('releasedate');
            $property->addValue((string) $product->releaseDate->format(DATE_ATOM));
            $properties[] = $property;
        }

        if ($product->manufacturer && $product->manufacturer->media) {
            $property = new Property('vendorlogo');
            $property->addValue($product->manufacturer->media->url);
            $properties[] = $property;
        }

        if ($product->price) {
            /** @var array<string, mixed> $price */
            $price = Utils::getCurrencyPrice($product->price, $this->getExportContext()->getCurrencyId());
            if ($price) {
                if ($listPrice = $price['listPrice']) {
                    $property = new Property('old_price');
                    $property->addValue((string) $listPrice['gross']);
                    $properties[] = $property;

                    $property = new Property('old_price_net');
                    $property->addValue((string) $listPrice['net']);
                    $properties[] = $property;
                }
            }
        }

        $isMarkedAsTopseller = $product->markAsTopseller ?? false;
        $promotionValue = $this->translateBooleanValue($isMarkedAsTopseller);
        $property = new Property('product_promotion');
        $property->addValue($promotionValue);
        $properties[] = $property;

        return $properties;
    }

    private function translateBooleanValue(bool $value): string
    {
        $translationKey = $value ? 'finSearch.general.yes' : 'finSearch.general.no';

        return $value ? 'Yes' : 'No';
    }
}
