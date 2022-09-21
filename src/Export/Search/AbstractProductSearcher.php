<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Search;

use FINDOLOGIC\Shopware6Common\Export\Config\MainVariant;
use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use InvalidArgumentException;
use Vin\ShopwareSdk\Data\Entity\Product\ProductCollection;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

abstract class AbstractProductSearcher
{
    protected PluginConfig $pluginConfig;

    protected AbstractProductCriteriaBuilder $productCriteriaBuilder;

    public function __construct(
        PluginConfig $pluginConfig,
        AbstractProductCriteriaBuilder $productCriteriaBuilder
    ) {
        $this->pluginConfig = $pluginConfig;
        $this->productCriteriaBuilder = $productCriteriaBuilder;
    }

    public function findVisibleProducts(
        ?int $limit = null,
        ?int $offset = null,
        ?string $productId = null
    ): ProductCollection {
        $products = $this->fetchProducts($limit, $offset, $productId);

        $mainVariantConfig = $this->pluginConfig->getMainVariant();
        if ($mainVariantConfig === MainVariant::CHEAPEST) {
            return $this->getCheapestProducts($products);
        }

        return $this->getConfiguredMainVariants($products) ?: $products;
    }

    abstract protected function fetchProducts(
        ?int $limit = null,
        ?int $offset = null,
        ?string $productId = null
    ): ProductCollection;

    abstract public function findTotalProductCount(): int;

    abstract public function findMaxPropertiesCount(
        string $productId,
        ?string $parentId,
        ?array $propertyIds
    ): int;

    abstract public function buildVariantIterator(ProductEntity $product, int $pageSize): VariantIteratorInterface;

    protected function adaptCriteriaBasedOnConfiguration(): void
    {
        $mainVariantConfig = $this->pluginConfig->getMainVariant();

        switch ($mainVariantConfig) {
            case MainVariant::SHOPWARE_DEFAULT:
                $this->adaptParentCriteriaByShopwareDefault();
                break;
            case MainVariant::MAIN_PARENT:
            case MainVariant::CHEAPEST:
                $this->adaptParentCriteriaByMainOrCheapestProduct();
                break;
            default:
                throw new InvalidArgumentException($mainVariantConfig);
        }
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

    protected function getCheapestProducts(ProductCollection $products): ProductCollection
    {
        $cheapestVariants = new ProductCollection();

        /** @var ProductEntity $product */
        foreach ($products as $product) {
            $currencyId = $this->exportContext->getCurrencyId();
            $productPrice = Utils::getCurrencyPrice($product->price, $currencyId);

            if (!$cheapestVariant = $this->getCheapestChild($product->id)) {
                if ($productPrice['gross'] > 0.0 && $product->active) {
                    $cheapestVariants->add($product);
                }

                continue;
            }

            /** @var string[] $productPrice */
            $cheapestVariantPrice = Utils::getCurrencyPrice($cheapestVariant->price, $currencyId);

            if ($productPrice['gross'] === 0.0) {
                $realCheapestProduct = $cheapestVariant;
            } else {
                $realCheapestProduct = $productPrice['gross'] <= $cheapestVariantPrice['gross']
                    ? $product
                    : $cheapestVariant;
            }

            $cheapestVariants->add($realCheapestProduct);
        }

        return $cheapestVariants;
    }

    protected function getConfiguredMainVariants(ProductCollection $products): ?ProductCollection
    {
        $realProductIds = [];

        foreach ($products as $product) {
            if ($mainVariantId = $product->mainVariantId) {
                $realProductIds[] = $mainVariantId;

                continue;
            }

            /**
             * If product is inactive, try to fetch first variant product.
             * This is related to main product by parent configuration.
             */
            if ($product->active) {
                $realProductIds[] = $product->id;
            } elseif ($childrenProductId = $this->getFirstVisibleChildId($product->id)) {
                $realProductIds[] = $childrenProductId;
            }
        }

        if (empty($realProductIds)) {
            return null;
        }

        return $this->getRealMainVariants($realProductIds);
    }

    abstract protected function getCheapestChild(string $productId): ?ProductEntity;

    abstract protected function getFirstVisibleChildId(string $productId): ?string;

    abstract protected function getRealMainVariants(array $productIds): ProductCollection;
}
