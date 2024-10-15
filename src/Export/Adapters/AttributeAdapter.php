<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Attribute;
use FINDOLOGIC\Export\Helpers\DataHelper;
use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use FINDOLOGIC\Shopware6Common\Export\Search\AbstractProductStreamSearcher;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractDynamicProductGroupService;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractCatUrlBuilderService;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use FINDOLOGIC\Shopware6Common\Traits\AdapterHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryCollection;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class AttributeAdapter implements AdapterInterface
{
    use AdapterHelper;

    public function __construct(
        protected readonly AbstractDynamicProductGroupService $dynamicProductGroupService,
        protected readonly AbstractCatUrlBuilderService $catUrlBuilderService,
        protected readonly AbstractProductStreamSearcher $productStreamSearcher,
        protected readonly ExportContext $exportContext,
        protected readonly PluginConfig $pluginConfig,
        protected readonly TranslatorInterface $translator,
    ) {
    }
    /**
     * @return Attribute[]
     */
    public function adapt(ProductEntity $product): array
    {
        $categoryAttributes = $this->getCategoryAndCatUrlAttributes($product);
        $manufacturerAttributes = $this->getManufacturerAttributes($product);
        $propertyAttributes = $this->getPropertyAttributes($product);
        $optionAttributes = $this->getOptionAttributes($product);
        $customFieldAttributes = $this->getCustomFieldAttributes($product);
        $additionalAttributes = $this->getAdditionalAttributes($product);

        return array_merge(
            $categoryAttributes,
            $manufacturerAttributes,
            $propertyAttributes,
            $optionAttributes,
            $customFieldAttributes,
            $additionalAttributes,
        );
    }

    /**
     * @return Attribute[]
     */
    protected function getCategoryAndCatUrlAttributes(ProductEntity $product): array
    {
        $catUrls = [];
        $categories = [];

        if ($product->categories?->count()) {
            $this->parseCategoryAttributes($product->categories, $catUrls, $categories);
        }

        foreach ($product->streamIds ?? [] as $streamId) {
            if ($this->productStreamSearcher->isProductInDynamicProductGroup($product->id, $streamId)) {
                $dynamicGroupCategories = $this->dynamicProductGroupService->getCategories($streamId);
                $this->parseCategoryAttributes($dynamicGroupCategories, $catUrls, $categories);
            }
        }

        $attributes = [];
        if (!Utils::isEmpty($catUrls)) {
            $catUrlAttribute = new Attribute('cat_url');
            $catUrlAttribute->setValues($this->decodeHtmlEntities(Utils::flattenWithUnique($catUrls)));
            $attributes[] = $catUrlAttribute;
        }

        if (!Utils::isEmpty($categories)) {
            $categoryAttribute = new Attribute('cat');
            $categoryAttribute->setValues($this->decodeHtmlEntities(array_unique($categories)));
            $attributes[] = $categoryAttribute;
        }

        return $attributes;
    }

    protected function parseCategoryAttributes(
        CategoryCollection $categoryCollection,
        array &$catUrls,
        array &$categories
    ): void {
        $navigationCategoryId = $this->exportContext->getNavigationCategoryId();

        foreach ($categoryCollection as $categoryEntity) {
            if (!$categoryEntity->active) {
                continue;
            }

            // If the category is not in the current sales channel's root category, we do not need to export it.
            if (!$categoryEntity->path || !strpos($categoryEntity->path, $navigationCategoryId)) {
                continue;
            }

            $categoryPath = Utils::buildCategoryPath(
                $categoryEntity->breadcrumb,
                $this->exportContext->getNavigationCategory()->breadcrumb,
            );

            if (!Utils::isEmpty($categoryPath)) {
                $categories = array_merge($categories, [$categoryPath]);
            }

            $catUrls = array_merge(
                $catUrls,
                $this->catUrlBuilderService->getCategoryUrls($categoryEntity),
            );
        }
    }

    protected function getManufacturerAttributes(ProductEntity $product): array
    {
        $attributes = [];
        if (!$manufacturer = $product->manufacturer) {
            return $attributes;
        }

        $name = $manufacturer->getTranslation('name');
        if (Utils::isEmpty($name)) {
            return $attributes;
        }

        $attributes[] = new Attribute('vendor', [Utils::removeControlCharacters($name)]);

        return $attributes;
    }

    /**
     * @return Attribute[]
     * @deprecated tag:6.0.0 - Logic was moved to the OptionsAdapter
     */
    protected function getOptionAttributes(ProductEntity $product): array
    {
        return [];
    }

    protected function getPropertyAttributes(ProductEntity $product): array
    {
        return $this->getPropertyGroupOptionAttributes($product->properties);
    }

    protected function getCustomFieldAttributes(ProductEntity $product): array
    {
        $attributes = [];

        $productFields = $product->getCustomFields();
        if (empty($productFields)) {
            return [];
        }

        foreach ($productFields as $key => $value) {
            $key = $this->getAttributeKey($key);
            $cleanedValue = $this->getCleanedAttributeValue($value);

            if (!Utils::isEmpty($key) && !Utils::isEmpty($cleanedValue)) {
                // Third-Party plugins may allow setting multidimensional custom-fields. As those can not really
                // be properly sanitized, they need to be skipped.
                if (is_array($cleanedValue) && is_array(array_values($cleanedValue)[0])) {
                    continue;
                }

                // Filter null, false and empty strings, but not "0". See: https://stackoverflow.com/a/27501297/6281648
                $customFieldAttribute = new Attribute(
                    $key,
                    $this->decodeHtmlEntities(array_filter((array) $cleanedValue, 'strlen')),
                );
                $attributes[] = $customFieldAttribute;
            }
        }

        return $attributes;
    }

    protected function decodeHtmlEntities(array $values): array
    {
        foreach ($values as $key => $value) {
            $values[$key] = $this->decodeHtmlEntity($value);
        }

        return $values;
    }

    protected function decodeHtmlEntity(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return html_entity_decode($value);
    }

    /**
     * @return Attribute[]
     */
    protected function getAdditionalAttributes(ProductEntity $product): array
    {
        $attributes = [];

        $shippingFree = $this->translateBooleanValue((bool) $product->shippingFree);
        $attributes[] = new Attribute('shipping_free', [$shippingFree]);
        $rating = $product->ratingAverage ?? 0.0;
        $attributes[] = new Attribute('rating', [$rating]);

        return $attributes;
    }

    /**
     * @param array<string, int, bool>|string|int|bool $value
     *
     * @return array<string, int, bool>|string|int|bool
     */
    protected function getCleanedAttributeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $values = [];
            foreach ($value as $item) {
                $values[] = $this->getCleanedAttributeValue($item);
            }

            return $values;
        }

        if (is_string($value)) {
            if (mb_strlen($value) > DataHelper::ATTRIBUTE_CHARACTER_LIMIT) {
                return '';
            }

            return Utils::cleanString($value);
        }

        if (is_bool($value)) {
            return $this->translateBooleanValue($value);
        }

        return $value;
    }

    protected function translateBooleanValue(bool $value): string
    {
        $translationKey = $value ? 'finSearch.general.yes' : 'finSearch.general.no';

        return $this->translator->trans($translationKey);
    }
}
