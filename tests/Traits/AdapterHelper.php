<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Traits;

use FINDOLOGIC\Shopware6Common\Export\Adapters\AbstractSalesFrequencyAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\AttributeAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\BonusAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\DateAddedAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\DefaultPropertiesAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\DescriptionAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\ImagesAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\KeywordsAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\NameAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\OrderNumberAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\PriceAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\ShopwarePropertiesAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\SortAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\SummaryAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\UrlAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\UserGroupsAdapter;
use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupCollection;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

trait AdapterHelper
{
    use ServicesHelper;

    public function getAttributeAdapter(?PluginConfig $config = null): AttributeAdapter
    {
        return new AttributeAdapter(
            $this->getDynamicProductGroupService(),
            $this->getUrlBuilderService(),
            $this->getExportContext(),
            $config ?? $this->getPluginConfig()
        );
    }

    public function getBonusAdapter(): BonusAdapter
    {
        return new BonusAdapter();
    }

    public function getDateAddedAdapter(): DateAddedAdapter
    {
        return new DateAddedAdapter();
    }

    public function getDefaultPropertiesAdapter(): DefaultPropertiesAdapter
    {
        return new DefaultPropertiesAdapter($this->getExportContext());
    }

    public function getDescriptionAdapter(): DescriptionAdapter
    {
        return new DescriptionAdapter();
    }

    public function getImagesAdapter(): ImagesAdapter
    {
        return new ImagesAdapter();
    }

    public function getKeywordsAdapter(): KeywordsAdapter
    {
        return new KeywordsAdapter();
    }

    public function getNameAdapter(): NameAdapter
    {
        return new NameAdapter();
    }

    public function getOrderNumberAdapter(): OrderNumberAdapter
    {
        return new OrderNumberAdapter();
    }

    public function getPriceAdapter(): PriceAdapter
    {
        return new PriceAdapter($this->getExportContext());
    }

    public function getSalesFrequencyAdapter(?int $orderCount = 1337): AbstractSalesFrequencyAdapter
    {
        return new class($orderCount) extends AbstractSalesFrequencyAdapter {
            private int $orderCount;

            public function __construct($orderCount) {
                $this->orderCount = $orderCount;
            }

            protected function getOrderCount(ProductEntity $product): int
            {
                return $this->orderCount;
            }
        };
    }

    public function getShopwarePropertiesAdapter(): ShopwarePropertiesAdapter
    {
        return new ShopwarePropertiesAdapter($this->getPluginConfig());
    }

    public function getSortAdapter(): SortAdapter
    {
        return new SortAdapter();
    }

    public function getSummaryAdapter(): SummaryAdapter
    {
        return new SummaryAdapter();
    }

    public function getUrlAdapter(): UrlAdapter
    {
        return new UrlAdapter($this->getUrlBuilderService());
    }

    public function getUserGroupAdapter(?CustomerGroupCollection $customerGroupCollection = null): UserGroupsAdapter
    {
        return new UserGroupsAdapter($this->getExportContext($customerGroupCollection));
    }
}
