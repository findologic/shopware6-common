<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Search;

abstract class AbstractProductSearcher
{
    protected AbstractProductCriteriaBuilder $productCriteriaBuilder;

    public function __construct(AbstractProductCriteriaBuilder $productCriteriaBuilder)
    {
        $this->productCriteriaBuilder = $productCriteriaBuilder;
    }

    public function findVisibleProducts(
        ?int $limit = null,
        ?int $offset = null,
        ?string $productId = null
    ): array {
        $products = $this->fetchProducts($limit, $offset, $productId);

        // TODO: Use config
//        $mainVariantConfig = '';
//        if ($mainVariantConfig === MainVariant::CHEAPEST) {
//            return $this->getCheapestProducts($products);
//        }

        return $this->getConfiguredMainVariants($products) ?: $products;
    }

    abstract protected function fetchProducts(
        ?int $limit = null,
        ?int $offset = null,
        ?string $productId = null
    ): array;

    abstract public function findTotalProductCount(): int;

    abstract public function findMaxPropertiesCount(
        string $productId,
        ?string $parentId,
        ?array $propertyIds,
    ): int;

    protected function adaptCriteriaBasedOnConfiguration(): void
    {
        $mainVariantConfig = '';

//        switch ($mainVariantConfig) {
//            case MainVariant::SHOPWARE_DEFAULT:
        $this->adaptParentCriteriaByShopwareDefault();
//                break;
//            case MainVariant::MAIN_PARENT:
//            case MainVariant::CHEAPEST:
//                $this->adaptParentCriteriaByMainOrCheapestProduct();
//                break;
//            default:
//                throw new InvalidArgumentException($mainVariantConfig);
//        }
    }

    protected function adaptParentCriteriaByShopwareDefault(): void
    {
        $this->productCriteriaBuilder
            ->withPriceZeroFilter()
            ->withVisibilityFilter()
            ->withDisplayGroupFilter();
    }

    protected function adaptParentCriteriaByMainOrCheapestProduct(): void
    {
        $this->productCriteriaBuilder
            ->withActiveParentOrInactiveParentWithVariantsFilter();
    }

    abstract protected function getCheapestProducts(array $products): array;

    abstract protected function getConfiguredMainVariants(array $products): ?array;
}
