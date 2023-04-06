<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Services;

use FINDOLOGIC\Shopware6Common\Export\Constants;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use FINDOLOGIC\Shopware6Common\Export\Handlers\DynamicProductGroupCacheHandler;
use FINDOLOGIC\Shopware6Common\Export\Search\AbstractCategorySearcher;
use Psr\Cache\CacheItemPoolInterface;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryCollection;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryEntity;

abstract class AbstractDynamicProductGroupService
{
    protected DynamicProductGroupCacheHandler $cacheHandler;

    public function __construct(
        protected readonly CacheItemPoolInterface $cache,
        protected readonly ExportContext $exportContext,
        protected readonly AbstractCategorySearcher $categorySearcher,
    ) {
        $this->setCacheHandler();
    }

    public function warmUp(): void
    {
        $this->cacheDynamicProductGroupsTotal();

        $productStreams = $this->parseProductGroups();
        if ($this->isLastPage()) {
            $this->cacheHandler->setWarmedUpCacheItem();
        }

        $this->cacheDynamicProductStream($productStreams);
    }

    public function getCategories(string $streamId): CategoryCollection
    {
        /** @var CategoryCollection $categories */
        $categories = $this->cacheHandler->getCachedCategoriesForProductStream($streamId);
        if (count($categories)) {
            return new CategoryCollection($categories);
        }

        return new CategoryCollection();
    }

    /**
     * @return array<string, array<string, CategoryEntity>>
     */
    protected function parseProductGroups(): array
    {
        $categories = $this->getProductStreamCategories();
        $productStreams = [];

        foreach ($categories as $categoryEntity) {
            if (!$categoryEntity->productStream) {
                continue;
            }

            $categoryEntity->addExtension(
                Constants::PARENT_CATEGORY_EXTENSION,
                $this->categorySearcher->fetchParentsFromCategoryPath($categoryEntity->path),
            );

            $productStreams[$categoryEntity->productStreamId][$categoryEntity->id] = $categoryEntity;
        }

        return $productStreams;
    }

    public function areDynamicProductGroupsCached(): bool
    {
        return $this->cacheHandler->areDynamicProductGroupsCached();
    }

    public function getDynamicProductGroupsTotal(): int
    {
        return $this->cacheHandler->getDynamicProductGroupsCachedTotal();
    }

    public function clearGeneralCache(): void
    {
        $this->cacheHandler->clearGeneralCache();
    }

    protected function setCacheHandler(): void
    {
        $this->cacheHandler = new DynamicProductGroupCacheHandler($this->cache, $this->exportContext->getShopkey());
    }

    /**
     * Sets the dynamic product groups total count in cache if it is not already set. This is important
     * as otherwise we wouldn't know when we're done fetching all dynamic product groups during the export.
     */
    protected function cacheDynamicProductGroupsTotal(): void
    {
        if (
            !$this->cacheHandler->isDynamicProductGroupTotalCached() ||
            $this->isFirstPage()
        ) {
            $total = $this->getDynamicProductGroupsCount();
            $this->cacheHandler->setDynamicProductGroupTotal($total);
        }
    }

    protected function getDynamicProductGroupsCount(): int
    {
        $total = 0;
        $categories = $this->categorySearcher->getProductStreamCategories();

        foreach ($categories as $categoryEntity) {
            if (!$categoryEntity->productStream) {
                continue;
            }

            $total++;
        }

        return $total;
    }

    protected function cacheDynamicProductStream(array $productStreams): void
    {
        $this->cacheHandler->cacheDynamicProductStreams($productStreams);
    }

    abstract protected function getProductStreamCategories(): CategoryCollection;

    abstract protected function isFirstPage(): bool;

    abstract protected function isLastPage(): bool;

    abstract protected function getCurrentOffset(): int;
}
