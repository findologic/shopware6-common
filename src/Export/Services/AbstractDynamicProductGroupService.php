<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Services;

use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use FINDOLOGIC\Shopware6Common\Export\Handlers\DynamicProductGroupCacheHandler;
use Psr\Cache\CacheItemPoolInterface;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryCollection;

abstract class AbstractDynamicProductGroupService
{
    public const CONTAINER_ID = 'fin_search.dynamic_product_group';

    protected DynamicProductGroupCacheHandler $cacheHandler;

    protected CacheItemPoolInterface $cache;

    protected ExportContext $exportContext;

    public function __construct(
        CacheItemPoolInterface $cache,
        ExportContext $exportContext
    ) {
        $this->cache = $cache;
        $this->exportContext = $exportContext;

        $this->setCacheHandler();
    }

    public function warmUp(): void
    {
        $this->cacheDynamicProductGroupsTotal();

        $products = $this->parseProductGroups();
        if ($this->isLastPage()) {
            $this->cacheHandler->setWarmedUpCacheItem();
        }

        $this->cacheDynamicProductPage($products);
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
        $categories = $this->getProductStreamCategories();

        foreach ($categories as $categoryEntity) {
            if (!$this->hasProductStream($categoryEntity)) {
                continue;
            }

            $total++;
        }

        return $total;
    }

    protected function cacheDynamicProductPage(array $products): void
    {
        $this->cacheHandler->setDynamicProductGroupsPage($products, $this->getCurrentOffset());
    }

    abstract public function getCategories(string $productId): CategoryCollection;

    /**
     * @return array<string, array<string, string>>
     */
    abstract protected function parseProductGroups(): array;

    abstract protected function getProductStreamCategories(): ?array;

    abstract protected function hasProductStream($categoryEntity): bool;

    abstract protected function isFirstPage(): bool;

    abstract protected function isLastPage(): bool;

    abstract protected function getCurrentOffset(): int;
}
