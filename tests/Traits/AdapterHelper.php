<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Traits;

use FINDOLOGIC\Shopware6Common\Export\Adapters\AbstractSalesFrequencyAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\AdapterFactory;
use FINDOLOGIC\Shopware6Common\Export\Adapters\AttributeAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\BonusAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\DateAddedAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\DefaultPropertiesAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\DescriptionAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\ExportItemAdapter;
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
use Monolog\Logger;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupCollection;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;
use Vin\ShopwareSdk\Data\Entity\SalesChannel\SalesChannelEntity;

trait AdapterHelper
{
    use ServicesHelper;

    public function getAdapterFactory(?PluginConfig $config = null): AdapterFactory
    {
        return new AdapterFactory(
            $this->getAttributeAdapter($config),
            $this->getBonusAdapter(),
            $this->getDateAddedAdapter(),
            $this->getDescriptionAdapter(),
            $this->getDefaultPropertiesAdapter(),
            $this->getImagesAdapter(),
            $this->getKeywordsAdapter(),
            $this->getNameAdapter(),
            $this->getOrderNumberAdapter(),
            $this->getPriceAdapter(),
            $this->getSalesFrequencyAdapter(),
            $this->getSortAdapter(),
            $this->getSummaryAdapter(),
            $this->getShopwarePropertiesAdapter(),
            $this->getUrlAdapter(),
            $this->getUserGroupAdapter(),
        );
    }

    public function getExportItemAdapter(
        ?AdapterFactory $adapterFactory = null,
        ?PluginConfig $config = null,
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null
    ): ExportItemAdapter {
        return new ExportItemAdapter(
            $adapterFactory ?? $this->getAdapterFactory($config),
                $logger ?? new Logger('test_logger'),
                $eventDispatcher
        );
    }

    public function getAttributeAdapter(?PluginConfig $config = null): AttributeAdapter
    {
        return new AttributeAdapter(
            $this->getDynamicProductGroupServiceMock(),
            $this->getCatUrlBuilderService(),
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
        return new ImagesAdapter($this->getProductImageService());
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

    public function getPriceAdapter(
        ?CustomerGroupCollection $customerGroupCollection = null,
        ?SalesChannelEntity $salesChannel = null
    ): PriceAdapter {
        return new PriceAdapter(
            $this->getExportContext($customerGroupCollection, $salesChannel)
        );
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
        return new UrlAdapter($this->getProductUrlService());
    }

    public function getUserGroupAdapter(?CustomerGroupCollection $customerGroupCollection = null): UserGroupsAdapter
    {
        return new UserGroupsAdapter($this->getExportContext($customerGroupCollection));
    }
}
