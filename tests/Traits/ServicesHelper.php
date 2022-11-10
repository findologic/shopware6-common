<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Traits;

use FINDOLOGIC\Shopware6Common\Export\Config\ImplementationType;
use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use FINDOLOGIC\Shopware6Common\Export\Search\AbstractCategorySearcher;
use FINDOLOGIC\Shopware6Common\Export\Search\AbstractProductCriteriaBuilder;
use FINDOLOGIC\Shopware6Common\Export\Search\AbstractProductSearcher;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractDynamicProductGroupService;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractCatUrlBuilderService;
use FINDOLOGIC\Shopware6Common\Export\Services\ProductImageService;
use FINDOLOGIC\Shopware6Common\Export\Services\ProductUrlService;
use FINDOLOGIC\Shopware6Common\Tests\CommonConstants;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\Translator;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryCollection;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryEntity;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupCollection;
use Vin\ShopwareSdk\Data\Entity\Entity;
use Vin\ShopwareSdk\Data\Entity\SalesChannel\SalesChannelEntity;
use Vin\ShopwareSdk\Data\Entity\SalesChannelDomain\SalesChannelDomainCollection;
use Vin\ShopwareSdk\Data\Entity\SalesChannelDomain\SalesChannelDomainEntity;

trait ServicesHelper
{
    use CategoryHelper;

    public function getRouterMock(): Router
    {
        $routerContext = new RequestContext();

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $router->method('getContext')->willReturn($routerContext);

        return $router;
    }

    public function getLogger(?string $name = 'test'): Logger
    {
        return new Logger($name);
    }

    public function getEventDispatcherMock(): MockObject
    {
        return $this->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /** @return Translator|MockObject */
    public function getTranslatorMock(string $locale = 'en'): MockObject
    {
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $returnMap = [
            ['finSearch.general.yes', [], null, null, $locale === 'de' ? 'Ja' : 'Yes'],
            ['finSearch.general.no', [], null, null, $locale === 'de' ? 'Nein' : 'No'],
        ];
        $translator->method('trans')
            ->willReturnMap($returnMap);

        return $translator;
    }

    /** @return AbstractDynamicProductGroupService|MockObject */
    public function getDynamicProductGroupServiceMock(): MockObject
    {
        return $this->getMockBuilder(AbstractDynamicProductGroupService::class)
            ->onlyMethods(['getCategories'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    public function getCatUrlBuilderService(): AbstractCatUrlBuilderService
    {
        return new class($this->getExportContext(), $this->getCategorySearcher(), $this->getRouterMock()) extends AbstractCatUrlBuilderService {
            public function __construct(
                ExportContext $exportContext,
                AbstractCategorySearcher $categorySearcher,
                ?RouterInterface $router = null
            ) {
                parent::__construct($exportContext, $categorySearcher, $router);
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
        return new ProductImageService($this->getRouterMock());
    }

    public function getExportContext(
        ?CustomerGroupCollection $customerGroupCollection = null,
        ?SalesChannelEntity $salesChannel = null
    ): ExportContext {
        return new ExportContext(
            CommonConstants::VALID_SHOPKEY,
            $salesChannel ?? $this->buildSalesChannel(),
            $this->buildNavigationCategory(),
            $customerGroupCollection ?? new CustomerGroupCollection(),
            true,
            ImplementationType::PLUGIN,
        );
    }

    public function getPluginConfig(?array $overrides = []): PluginConfig
    {
        return PluginConfig::createFromArray(array_merge([
            'shopkey' => 'ABCDABCDABCDABCDABCDABCDABCDABCD',
            'active' => true,
        ], $overrides));
    }

    public function getProductCriteriaBuilderMock(): AbstractProductCriteriaBuilder
    {
        return $this->getMockBuilder(AbstractProductCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    public function getProductSearcherMock(): AbstractProductSearcher
    {
        return $this->getMockBuilder(AbstractProductSearcher::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function getCategorySearcher(): AbstractCategorySearcher
    {
        return new class($this->getExportContext()) extends AbstractCategorySearcher {
            public function fetchParentsFromCategoryPath(string $categoryPath): CategoryCollection
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

            public function getProductStreamCategories(?int $count = null, ?int $offset = null): CategoryCollection
            {
                return new CategoryCollection();
            }
        };
    }

    public function buildSalesChannel(?string $salesChannelId = CommonConstants::SALES_CHANNEL_ID): SalesChannelEntity
    {
        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = Entity::createFromArray(SalesChannelEntity::class, [
            'id' => $salesChannelId,
            'languageId' => CommonConstants::LANGUAGE_ID,
            'currencyId' => CommonConstants::CURRENCY_ID,
        ]);
        $storeFrontDomain = Entity::createFromArray(SalesChannelDomainEntity::class, [
            'url' => 'https://test.uk',
            'languageId' => CommonConstants::LANGUAGE_ID,
            'currencyId' => CommonConstants::CURRENCY_ID,
        ]);
        $storeFrontDomain2 = Entity::createFromArray(SalesChannelDomainEntity::class, [
            'url' => 'https://test.de',
            'languageId' => CommonConstants::LANGUAGE2_ID,
            'currencyId' => CommonConstants::CURRENCY2_ID,
        ]);
        $headlessDomain = Entity::createFromArray(SalesChannelDomainEntity::class, [
            'url' => 'default.headless',
            'languageId' => CommonConstants::LANGUAGE_ID,
            'currencyId' => CommonConstants::CURRENCY_ID,
        ]);
        $salesChannel->domains = new SalesChannelDomainCollection([
            $storeFrontDomain,
            $storeFrontDomain2,
            $headlessDomain,
        ]);

        return $salesChannel;
    }
}
