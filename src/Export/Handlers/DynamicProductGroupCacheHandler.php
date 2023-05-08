<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Handlers;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryEntity;

class DynamicProductGroupCacheHandler
{
    private const CACHE_ID_PRODUCT_GROUP = 'fl_product_groups';
    private const CACHE_LIFETIME_PRODUCT_GROUP = 60 * 11;

    public function __construct(
        protected readonly CacheItemPoolInterface $cache,
        protected readonly ?string $shopkey,
    ) {
    }

    public function setWarmedUpCacheItem(): void
    {
        $cacheItem = $this->getDynamicProductGroupWarmedUpCacheItem();
        $cacheItem->set(true);
        $cacheItem->expiresAfter(self::CACHE_LIFETIME_PRODUCT_GROUP);
        $this->cache->save($cacheItem);
    }

    public function isDynamicProductGroupTotalCached(): bool
    {
        $totalCacheItem = $this->getDynamicGroupsTotalCacheItem();

        return $totalCacheItem->isHit();
    }

    public function areDynamicProductGroupsCached(): bool
    {
        $cacheItem = $this->getDynamicProductGroupWarmedUpCacheItem();
        if ($cacheItem->isHit()) {
            $cacheItem->set(true);
            $cacheItem->expiresAfter(self::CACHE_LIFETIME_PRODUCT_GROUP);
            $this->cache->save($cacheItem);

            return true;
        }

        return false;
    }

    /**
     * @return CategoryEntity[]
     */
    public function getCachedCategoriesForProductStream(string $streamId): array
    {
        $categories = [];
        $cacheItem = $this->getProductStreamCacheItem($streamId);
        if ($cacheItem->isHit()) {
            $categories = (array) $cacheItem->get();
        }

        return $categories;
    }

    public function setDynamicProductGroupTotal(int $total): void
    {
        $totalCacheItem = $this->getDynamicGroupsTotalCacheItem();
        $this->setTotalInCache($totalCacheItem, $total);
    }

    public function cacheDynamicProductStreams(array $productStreams): void
    {
        foreach ($productStreams as $productStreamId => $productStreamCategories) {
            $productStreamCacheItem = $this->getProductStreamCacheItem($productStreamId);

            $categories = $productStreamCacheItem->isHit()
                ? array_merge($productStreamCacheItem->get(), $productStreamCategories)
                : $productStreamCategories;

            $productStreamCacheItem->set($categories);
            $productStreamCacheItem->expiresAfter(self::CACHE_LIFETIME_PRODUCT_GROUP);
            $this->cache->save($productStreamCacheItem);
        }
    }

    public function clearGeneralCache(): void
    {
        $totalCacheItem = $this->getDynamicGroupsTotalCacheItem();
        $warmedUpCacheItem = $this->getDynamicProductGroupWarmedUpCacheItem();

        $this->cache->deleteItems([
            $totalCacheItem->getKey(),
            $warmedUpCacheItem->getKey(),
        ]);
    }

    public function getDynamicProductGroupsCachedTotal(): int
    {
        return $this->getDynamicProductGroupTotalFromCache();
    }

    protected function getDynamicProductGroupTotalFromCache(): int
    {
        $cacheItem = $this->getDynamicGroupsTotalCacheItem();
        if ($cacheItem->isHit()) {
            $cacheItem->expiresAfter(self::CACHE_LIFETIME_PRODUCT_GROUP);
            $this->cache->save($cacheItem);

            return $cacheItem->get();
        }

        return 0;
    }

    protected function setTotalInCache(CacheItemInterface $cacheItem, int $total): void
    {
        $cacheItem->set($total);
        $cacheItem->expiresAfter(self::CACHE_LIFETIME_PRODUCT_GROUP);
        $this->cache->save($cacheItem);
    }

    protected function getProductStreamCacheItem(string $streamId): CacheItemInterface
    {
        $id = sprintf('%s_%s_%s', self::CACHE_ID_PRODUCT_GROUP, $this->shopkey, $streamId);

        return $this->cache->getItem($id);
    }

    protected function getDynamicProductGroupWarmedUpCacheItem(): CacheItemInterface
    {
        $id = sprintf('%s_%s_dynamic_product_warmup', self::CACHE_ID_PRODUCT_GROUP, $this->shopkey);

        return $this->cache->getItem($id);
    }

    protected function getDynamicGroupsTotalCacheItem(): CacheItemInterface
    {
        $id = sprintf('%s_%s_total', self::CACHE_ID_PRODUCT_GROUP, $this->shopkey);

        return $this->cache->getItem($id);
    }
}
