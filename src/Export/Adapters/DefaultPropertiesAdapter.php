<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Property;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class DefaultPropertiesAdapter implements AdapterInterface
{
    protected ExportContext $exportContext;

    protected TranslatorInterface $translator;

    public function __construct(
        ExportContext $exportContext,
        TranslatorInterface $translator
    ) {
        $this->exportContext = $exportContext;
        $this->translator = $translator;
    }

    public function adapt(ProductEntity $product): array
    {
        $properties = [];

        if ($product->tax) {
            $value = (string) $product->tax->taxRate;
            $properties[] = $this->getProperty('tax', $value);
        }

        if ($purchaseUnit = $product->purchaseUnit) {
            $properties[] = $this->getProperty('purchaseunit', (string) $purchaseUnit);
        }

        if ($referenceUnit = $product->referenceUnit) {
            $properties[] = $this->getProperty('referenceunit', (string) $referenceUnit);
        }

        if ($packUnit = $product->getTranslation('packUnit')) {
            $properties[] = $this->getProperty('packunit', $packUnit);
        }

        if ($stock = $product->stock) {
            $properties[] = $this->getProperty('stock', (string) $stock);
        }

        if ($availableStock = $product->availableStock) {
            $properties[] = $this->getProperty('availableStock', (string) $availableStock);
        }

        if ($weight = $product->weight) {
            $properties[] = $this->getProperty('weight', (string) $weight);
        }

        if ($width = $product->width) {
            $properties[] = $this->getProperty('width', (string) $width);
        }

        if ($height = $product->height) {
            $properties[] = $this->getProperty('height', (string) $height);
        }

        if ($length = $product->length) {
            $properties[] = $this->getProperty('length', (string) $length);
        }

        if ($releaseDate = $product->releaseDate) {
            $value = $releaseDate->format(DATE_ATOM);
            $properties[] = $this->getProperty('releasedate', $value);
        }

        if ($product->manufacturer && $product->manufacturer->media) {
            $value = $product->manufacturer->media->url;
            $properties[] = $this->getProperty('vendorlogo', $value);
        }

        if ($product->price) {
            /** @var array<string, mixed> $price */
            $price = Utils::getCurrencyPrice($product->price, $this->exportContext->getCurrencyId());
            if ($price) {
                if ($listPrice = $price['listPrice']) {
                    $properties[] = $this->getProperty('old_price', (string) $listPrice['gross']);
                    $properties[] = $this->getProperty('old_price_net', (string) $listPrice['net']);
                }
            }
        }

        $isMarkedAsTopseller = $product->markAsTopseller ?? false;
        $translated = $this->translateBooleanValue($isMarkedAsTopseller);
        $properties[] = $this->getProperty('product_promotion', $translated);

        return $properties;
    }

    protected function getProperty(string $name, $value): ?Property
    {
        if (Utils::isEmpty($value)) {
            return null;
        }

        $property = new Property($name);
        $property->addValue($value);

        return $property;
    }

    protected function translateBooleanValue(bool $value): string
    {
        $translationKey = $value ? 'finSearch.general.yes' : 'finSearch.general.no';

        return $this->translator->trans($translationKey);
    }
}
