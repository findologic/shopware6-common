<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Search;

use Vin\ShopwareSdk\Data\Entity\EntityCollection;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

interface ProductDebugSearcherInterface
{
    public function buildCriteria(?int $limit = null, ?int $offset = null, ?string $productId = null);

    public function getMainProductById(string $productId): ?ProductEntity;

    public function getProductById(string $productId): ?ProductEntity;

    public function searchProduct($criteria): ?ProductEntity;

    /**
     * @return ProductEntity[]
     */
    public function getSiblings(string $parentId, int $count): array;

    public function searchProducts($criteria): EntityCollection;
}
