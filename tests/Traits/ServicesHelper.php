<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Traits;

use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractDynamicProductGroupService;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractUrlBuilderService;
use Symfony\Component\Routing\Router;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupCollection;
use Vin\ShopwareSdk\Data\Entity\Entity;
use Vin\ShopwareSdk\Data\Entity\SalesChannel\SalesChannelEntity;

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

    public function getUrlBuilderService(): AbstractUrlBuilderService
    {
        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new class($router, $this->getExportContext()) extends AbstractUrlBuilderService {
            protected function fetchParentsFromCategoryPath(string $categoryPath): ?array
            {
                return [];
            }
        };
    }

    public function getExportContext(): ExportContext
    {
        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = Entity::createFromArray(SalesChannelEntity::class, [
        ]);

        $navigationCategory = $this->createTestCategory([
            'id' => $this->navigationCategoryId,
            'breadcrumb' => [$this->navigationCategoryId]
        ]);

        return new ExportContext(
            $this->validShopkey,
            $salesChannel,
            $navigationCategory,
            new CustomerGroupCollection(),
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
}
