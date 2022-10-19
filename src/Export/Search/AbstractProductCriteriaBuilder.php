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
    ): self {
        $this->withCreatedAtSorting()
            ->withIdSorting()
            ->withPagination($limit, $offset)
            ->withProductIdFilter($productId)
            ->withOutOfStockFilter()
            ->withProductAssociations();

        return $this;
    }

    public function withChildCriteria(string $parentId): self
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

    public function withPagination(?int $limit, ?int $offset): self
    {
        $this->withLimit($limit);
        $this->withOffset($offset);

        return $this;
    }

    public function withParentIdFilterWithVisibility(string $productId, ?string $parentId = null): self
    {
        if ($parentId) {
            $this->adaptProductIdCriteriaWithoutParentId($productId, $parentId);
        } else {
            $this->adaptProductIdCriteriaWithParentId($productId);
        }

        return $this;
    }

    abstract public function withLimit(?int $limit): self;

    abstract public function withOffset(?int $offset): self;

    abstract public function withCreatedAtSorting(): self;

    abstract public function withIdSorting(): self;

    abstract public function withPriceSorting(): self;

    abstract public function withIds(array $ids): self;

    abstract public function withOutOfStockFilter(): self;

    abstract public function withVisibilityFilter(): self;

    abstract public function withDisplayGroupFilter(): self;

    abstract public function withParentIdFilter(string $parentId): self;

    abstract public function withProductIdFilter(?string $productId, ?bool $considerVariants = false): self;

    abstract public function withProductAssociations(): self;

    /**
     * @param string[] $categoryIds
     * @param string[] $propertyIds
     */
    abstract public function withVariantAssociations(array $categoryIds, array $propertyIds): self;

    abstract public function withPriceZeroFilter(): self;

    abstract public function withActiveParentOrInactiveParentWithVariantsFilter(): self;

    abstract protected function adaptProductIdCriteriaWithParentId(string $productId): void;

    abstract protected function adaptProductIdCriteriaWithoutParentId(string $productId, string $parentId): void;
}
