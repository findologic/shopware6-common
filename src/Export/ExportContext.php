<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Content\Category\CategoryEntity;

class ExportContext
{
    public const CONTAINER_ID = 'fin_search.export_context';

    protected string $shopkey;

    protected string $salesChannelId;

    protected string $navigationCategoryId;

    protected array $customerGroups;

    /** @var string[] */
    protected array $navigationCategoryBreadcrumbs;

    public function __construct(
        string $shopkey,
        string $salesChannelId,
        string $navigationCategoryId,
        array $customerGroups,
        array $navigationCategoryBreadcrumbs
    ) {
        $this->shopkey = $shopkey;
        $this->salesChannelId = $salesChannelId;
        $this->navigationCategoryId = $navigationCategoryId;
        $this->customerGroups = $customerGroups;
        $this->navigationCategoryBreadcrumbs = $navigationCategoryBreadcrumbs;
    }

    public function getShopkey(): string
    {
        return $this->shopkey;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function getCustomerGroups(): array
    {
        return $this->customerGroups;
    }

    public function getNavigationCategoryId(): string
    {
        return $this->navigationCategoryId;
    }

    /**
     * @return string[]
     */
    public function getNavigationCategoryBreadcrumbs(): array
    {
        return $this->navigationCategoryBreadcrumbs;
    }
}
