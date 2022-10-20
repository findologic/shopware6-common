<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export;

use FINDOLOGIC\Shopware6Common\Export\Config\ImplementationType;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryEntity;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupCollection;
use Vin\ShopwareSdk\Data\Entity\SalesChannel\SalesChannelEntity;

class ExportContext
{
    public const CONTAINER_ID = 'fin_search.export_context';

    protected string $shopkey;

    protected SalesChannelEntity $salesChannel;

    protected CategoryEntity $navigationCategory;

    protected CustomerGroupCollection $customerGroups;

    protected bool $shouldHideProductsOutOfStock;

    protected string $implementationType;

    public function __construct(
        string $shopkey,
        SalesChannelEntity $salesChannel,
        CategoryEntity $navigationCategory,
        CustomerGroupCollection $customerGroups,
        bool $shouldHideProductsOutOfStock,
        string $implementationType,
    ) {
        $this->shopkey = $shopkey;
        $this->salesChannel = $salesChannel;
        $this->navigationCategory = $navigationCategory;
        $this->customerGroups = $customerGroups;
        $this->shouldHideProductsOutOfStock = $shouldHideProductsOutOfStock;
        $this->implementationType = $implementationType;
    }

    public function getShopkey(): string
    {
        return $this->shopkey;
    }

    public function getSalesChannel(): SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannel->id;
    }

    public function getCurrencyId(): string
    {
        return $this->salesChannel->currencyId;
    }

    public function getLanguageId(): string
    {
        return $this->salesChannel->languageId;
    }

    public function getNavigationCategory(): CategoryEntity
    {
        return $this->navigationCategory;
    }

    public function getNavigationCategoryId(): string
    {
        return $this->navigationCategory->id;
    }

    public function getCustomerGroups(): CustomerGroupCollection
    {
        return $this->customerGroups;
    }

    public function shouldHideProductsOutOfStock(): bool
    {
        return $this->shouldHideProductsOutOfStock;
    }

    public function getImplementationType(): string
    {
        return $this->implementationType;
    }

    public function isAppExport(): bool
    {
        return $this->implementationType === ImplementationType::APP;
    }

    public function isPluginExport(): bool
    {
        return $this->implementationType === ImplementationType::PLUGIN;
    }
}
