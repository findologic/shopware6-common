<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

class AdapterFactory
{
    public function __construct(
        private readonly AttributeAdapter $attributeAdapter,
        private readonly BonusAdapter $bonusAdapter,
        private readonly DateAddedAdapter $dateAddedAdapter,
        private readonly DescriptionAdapter $descriptionAdapter,
        private readonly DefaultPropertiesAdapter $defaultPropertiesAdapter,
        private readonly GroupsAdapter $groupsAdapter,
        private readonly ImagesAdapter $imagesAdapter,
        private readonly KeywordsAdapter $keywordsAdapter,
        private readonly NameAdapter $nameAdapter,
        private readonly OptionsAdapter $optionsAdapter,
        private readonly OrderNumberAdapter $orderNumberAdapter,
        private readonly OverriddenPriceAdapter $overriddenPriceAdapter,
        private readonly PriceAdapter $priceAdapter,
        private readonly AbstractSalesFrequencyAdapter $salesFrequencyAdapter,
        private readonly SortAdapter $sortAdapter,
        private readonly SummaryAdapter $summaryAdapter,
        private readonly ShopwarePropertiesAdapter $shopwarePropertiesAdapter,
        private readonly UrlAdapter $urlAdapter,
        private readonly VariantConfigurationAdapter $variantConfigurationAdapter,
    ) {
    }

    public function getAttributeAdapter(): AttributeAdapter
    {
        return $this->attributeAdapter;
    }

    public function getBonusAdapter(): BonusAdapter
    {
        return $this->bonusAdapter;
    }

    public function getDateAddedAdapter(): DateAddedAdapter
    {
        return $this->dateAddedAdapter;
    }

    public function getDescriptionAdapter(): DescriptionAdapter
    {
        return $this->descriptionAdapter;
    }

    public function getDefaultPropertiesAdapter(): DefaultPropertiesAdapter
    {
        return $this->defaultPropertiesAdapter;
    }

    public function getGroupsAdapter(): GroupsAdapter
    {
        return $this->groupsAdapter;
    }

    public function getImagesAdapter(): ImagesAdapter
    {
        return $this->imagesAdapter;
    }

    public function getKeywordsAdapter(): KeywordsAdapter
    {
        return $this->keywordsAdapter;
    }

    public function getNameAdapter(): NameAdapter
    {
        return $this->nameAdapter;
    }

    public function getOptionsAdapter(): OptionsAdapter
    {
        return $this->optionsAdapter;
    }

    public function getOrderNumbersAdapter(): OrderNumberAdapter
    {
        return $this->orderNumberAdapter;
    }

    public function getOverriddenPriceAdapter(): OverriddenPriceAdapter
    {
        return $this->overriddenPriceAdapter;
    }

    public function getPriceAdapter(): PriceAdapter
    {
        return $this->priceAdapter;
    }

    public function getSalesFrequencyAdapter(): AbstractSalesFrequencyAdapter
    {
        return $this->salesFrequencyAdapter;
    }

    public function getSortAdapter(): SortAdapter
    {
        return $this->sortAdapter;
    }

    public function getSummaryAdapter(): SummaryAdapter
    {
        return $this->summaryAdapter;
    }

    public function getShopwarePropertiesAdapter(): ShopwarePropertiesAdapter
    {
        return $this->shopwarePropertiesAdapter;
    }

    public function getUrlAdapter(): UrlAdapter
    {
        return $this->urlAdapter;
    }

    public function getVariantConfigurationAdapter(): VariantConfigurationAdapter
    {
        return $this->variantConfigurationAdapter;
    }
}
