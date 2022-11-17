<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Attribute;
use FINDOLOGIC\Export\Helpers\DataHelper;
use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoCategoriesException;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractDynamicProductGroupService;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractCatUrlBuilderService;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryCollection;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;
use Vin\ShopwareSdk\Data\Entity\PropertyGroupOption\PropertyGroupOptionCollection;
use Vin\ShopwareSdk\Data\Entity\PropertyGroupOption\PropertyGroupOptionEntity;

class AttributeAdapter
{
    protected AbstractDynamicProductGroupService $dynamicProductGroupService;

    protected AbstractCatUrlBuilderService $catUrlBuilderService;

    protected ExportContext $exportContext;

    protected PluginConfig $pluginConfig;

    protected TranslatorInterface $translator;

    public function __construct(
        AbstractDynamicProductGroupService $dynamicProductGroupService,
        AbstractCatUrlBuilderService $catUrlBuilderService,
        ExportContext $exportContext,
        PluginConfig $pluginConfig,
        TranslatorInterface $translator
    ) {
        $this->dynamicProductGroupService = $dynamicProductGroupService;
        $this->catUrlBuilderService = $catUrlBuilderService;
        $this->exportContext = $exportContext;
        $this->pluginConfig = $pluginConfig;
        $this->translator = $translator;
    }

    /**
     * @return Attribute[]
     * @throws ProductHasNoCategoriesException
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

        if ($product->categories && $product->categories->count()) {
            $this->parseCategoryAttributes($product->categories, $catUrls, $categories);
        }

        foreach ($product->streamIds ?? [] as $streamId) {
            $dynamicGroupCategories = $this->dynamicProductGroupService->getCategories($streamId);
            $this->parseCategoryAttributes($dynamicGroupCategories, $catUrls, $categories);
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
     */
    protected function getPropertyAttributes(ProductEntity $product): array
    {
        if (!$product->properties || !$product->properties->count()) {
            return [];
        }

        return $this->getPropertyGroupOptionAttributes($product->properties);
    }

    /**
     * @return Attribute[]
     */
    protected function getOptionAttributes(ProductEntity $product): array
    {
        if (!$product->options || !$product->options->count()) {
            return [];
        }

        return $this->getPropertyGroupOptionAttributes($product->options);
    }

    /**
     * @return Attribute[]
     */
    protected function getPropertyGroupOptionAttributes(PropertyGroupOptionCollection $collection): array
    {
        $attributes = [];

        foreach ($collection as $propertyGroupOptionEntity) {
            $group = $propertyGroupOptionEntity->group;
            if ($group && !$group->filterable) {
                continue;
            }

            $attributes = array_merge($attributes, $this->getAttributePropertyAsAttribute($propertyGroupOptionEntity));
        }

        return $attributes;
    }

    /**
     * @return Attribute[]
     */
    protected function getAttributePropertyAsAttribute(PropertyGroupOptionEntity $propertyGroupOptionEntity): array
    {
        $attributes = [];

        $group = $propertyGroupOptionEntity->group;
        if ($group && $propertyGroupOptionEntity->getTranslation('name') && $group->getTranslation('name')) {
            $groupName = $this->getAttributeKey($group->getTranslation('name'));
            $propertyGroupOptionName = $propertyGroupOptionEntity->getTranslation('name');
            if (!Utils::isEmpty($groupName) && !Utils::isEmpty($propertyGroupOptionName)) {
                $propertyGroupAttrib = new Attribute($groupName);
                $propertyGroupAttrib->addValue(Utils::removeControlCharacters($propertyGroupOptionName));

                $attributes[] = $propertyGroupAttrib;
            }
        }

        return $attributes;
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

    /**
     * @param mixed $value
     * @return string|mixed
     */
    protected function decodeHtmlEntity($value)
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
     * For API Integrations, we have to remove special characters from the attribute key as a requirement for
     * sending data via API.
     */
    protected function getAttributeKey(?string $key): ?string
    {
        if ($this->pluginConfig->isIntegrationTypeApi()) {
            return Utils::removeSpecialChars($key);
        }

        return $key;
    }

    /**
     * @param array<string, int, bool>|string|int|bool $value
     *
     * @return array<string, int, bool>|string|int|bool
     */
    protected function getCleanedAttributeValue($value)
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
