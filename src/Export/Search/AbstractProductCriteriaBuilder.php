<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Search;

use FINDOLOGIC\Shopware6Common\Export\ExportContext;

abstract class AbstractProductCriteriaBuilder
{
    protected ExportContext $exportContext;

    public function __construct(ExportContext $exportContext)
    {
        $this->exportContext = $exportContext;

        $this->reset();
    }

    abstract public function reset(): void;

    abstract public function build();

    public function withDefaultCriteria(
        ?int $limit = null,
        ?int $offset = null,
        ?string $productId = null
    ): AbstractProductCriteriaBuilder {
        $this->withCreatedAtSorting()
            ->withIdSorting()
            ->withPagination($limit, $offset)
            ->withProductIdFilter($productId)
            ->withOutOfStockFilter()
            ->withProductAssociations();

        return $this;
    }

    public function withChildCriteria(string $parentId): AbstractProductCriteriaBuilder
    {
        $this->withParentIdFilter($parentId)
            ->withPriceSorting()
            ->withCreatedAtSorting()
            ->withLimit(1)
            ->withOutOfStockFilter()
            ->withPriceZeroFilter()
            ->withVisibilityFilter();

        return $this;
    }

    public function withPagination(?int $limit, ?int $offset): AbstractProductCriteriaBuilder
    {
        $this->withLimit($limit);
        $this->withOffset($offset);

        return $this;
    }

    public function withParentIdFilterWithVisibility(string $productId, ?string $parentId = null): AbstractProductCriteriaBuilder
    {
        if ($parentId) {
            $this->adaptProductIdCriteriaWithoutParentId($productId, $parentId);
        } else {
            $this->adaptProductIdCriteriaWithParentId($productId);
        }

        return $this;
    }

    abstract public function withLimit(?int $limit): AbstractProductCriteriaBuilder;

    abstract public function withOffset(?int $offset): AbstractProductCriteriaBuilder;

    abstract public function withCreatedAtSorting(): AbstractProductCriteriaBuilder;

    abstract public function withIdSorting(): AbstractProductCriteriaBuilder;

    abstract public function withPriceSorting(): AbstractProductCriteriaBuilder;

    abstract public function withIds(array $ids): AbstractProductCriteriaBuilder;

    abstract public function withOutOfStockFilter(): AbstractProductCriteriaBuilder;

    abstract public function withVisibilityFilter(): AbstractProductCriteriaBuilder;

    abstract public function withDisplayGroupFilter(): AbstractProductCriteriaBuilder;

    abstract public function withParentIdFilter(string $parentId): AbstractProductCriteriaBuilder;

    abstract public function withProductIdFilter(?string $productId, ?bool $considerVariants = false): AbstractProductCriteriaBuilder;

    abstract public function withProductAssociations(): AbstractProductCriteriaBuilder;

    abstract public function withVariantAssociations(): AbstractProductCriteriaBuilder;

    abstract public function withPriceZeroFilter(): AbstractProductCriteriaBuilder;

    abstract public function withActiveParentOrInactiveParentWithVariantsFilter(): AbstractProductCriteriaBuilder;

    abstract protected function adaptProductIdCriteriaWithParentId(string $productId): void;

    abstract protected function adaptProductIdCriteriaWithoutParentId(string $productId, string $parentId): void;
}
