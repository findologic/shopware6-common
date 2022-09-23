<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Traits;

use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractDynamicProductGroupService;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractCatUrlBuilderService;
use FINDOLOGIC\Shopware6Common\Export\Services\ProductImageService;
use FINDOLOGIC\Shopware6Common\Export\Services\ProductUrlService;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Vin\ShopwareSdk\Data\Defaults;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryCollection;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryEntity;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupCollection;
use Vin\ShopwareSdk\Data\Entity\Entity;
use Vin\ShopwareSdk\Data\Entity\SalesChannel\SalesChannelEntity;
use Vin\ShopwareSdk\Data\Entity\SalesChannelDomain\SalesChannelDomainCollection;
use Vin\ShopwareSdk\Data\Entity\SalesChannelDomain\SalesChannelDomainEntity;
use Vin\ShopwareSdk\Data\Uuid\Uuid;

trait ServicesHelper
{
    use CategoryHelper;
    use Constants;

    public function getDynamicProductGroupService(): AbstractDynamicProductGroupService
    {
        return $this->getMockBuilder(AbstractDynamicProductGroupService::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    public function getCatUrlBuilderService(): AbstractCatUrlBuilderService
    {
        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new class($this->getExportContext(), $router) extends AbstractCatUrlBuilderService {
            public function __construct(
                ExportContext $exportContext,
                ?RouterInterface $router = null
            ) {
                parent::__construct($exportContext, $router);
            }

            protected function fetchParentsFromCategoryPath(string $categoryPath): CategoryCollection
            {
                $categoryCollection = new CategoryCollection();
                $parentIds = array_filter(explode('|', $categoryPath));

                foreach ($parentIds as $id) {
                    $category = new CategoryEntity();
                    $category->id = $id;

                    $categoryCollection->add($category);
                }

                return $categoryCollection;
            }

            protected function buildCategoryUrls(CategoryEntity $category): array
            {
                return [$category->id];
            }
        };
    }

    public function getProductUrlService(): ProductUrlService
    {
        return new ProductUrlService($this->getExportContext());
    }

    public function getProductImageService(): ProductImageService
    {
        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new ProductImageService($router);
    }

    public function getExportContext(?CustomerGroupCollection $customerGroupCollection = null): ExportContext
    {
        return new ExportContext(
            $this->validShopkey,
            $this->buildSalesChannel(),
            $this->buildNavigationCategory(),
            $customerGroupCollection ?? new CustomerGroupCollection(),
            true,
        );
    }

    public function getPluginConfig(?array $overrides = []): PluginConfig
    {
        return PluginConfig::createFromArray(array_merge([
            'shopkey' => 'ABCDABCDABCDABCDABCDABCDABCDABCD',
            'active' => true
        ], $overrides));
    }

    public function buildSalesChannel(): SalesChannelEntity
    {
        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = Entity::createFromArray(SalesChannelEntity::class, [
            'id' => Defaults::SALES_CHANNEL,
            'languageId' => Defaults::LANGUAGE_SYSTEM
        ]);
        $storeFrontDomain = Entity::createFromArray(SalesChannelDomainEntity::class, [
            'url' => 'https://test.uk',
            'languageId' => Defaults::LANGUAGE_SYSTEM
        ]);
        $storeFrontDomain2 = Entity::createFromArray(SalesChannelDomainEntity::class, [
            'url' => 'https://test.de',
            'languageId' => Uuid::randomHex(),
        ]);
        $headlessDomain = Entity::createFromArray(SalesChannelDomainEntity::class, [
            'url' => 'default.headless',
            'languageId' => Defaults::LANGUAGE_SYSTEM,
        ]);
        $salesChannel->domains = new SalesChannelDomainCollection([
            $storeFrontDomain,
            $storeFrontDomain2,
            $headlessDomain
        ]);

        return $salesChannel;
    }
}
