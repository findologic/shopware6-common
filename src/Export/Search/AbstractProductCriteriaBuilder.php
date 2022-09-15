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
    }

    abstract public function reset(): void;

    abstract public function build(): mixed;

    public function withDefaultCriteria(?int $limit = null, ?int $offset = null, ?string $productId = null): static
    {
        $this->withCreatedAtSorting()
            ->withIdSorting()
            ->withPagination($limit, $offset)
            ->withProductIdFilter($productId)
            ->withOutOfStockFilter()
            ->withProductAssociations();

        return $this;
    }

    public function withChildCriteria(string $parentId): static
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

    public function withPagination(?int $limit, ?int $offset): static
    {
        $this->withLimit($limit);
        $this->withOffset($offset);

        return $this;
    }

    public function withParentIdFilterWithVisibility(string $productId, ?string $parentId): static
    {
        if ($parentId) {
            $this->adaptProductIdCriteriaWithoutParentId($productId, $parentId);
        } else {
            $this->adaptProductIdCriteriaWithParentId($productId);
        }

        return $this;
    }

    abstract public function withLimit(?int $limit): static;

    abstract public function withOffset(?int $offset): static;

    abstract public function withCreatedAtSorting(): static;

    abstract public function withIdSorting(): static;

    abstract public function withPriceSorting(): static;

    abstract public function withIds(array $ids): static;

    abstract public function withOutOfStockFilter(): static;

    abstract public function withVisibilityFilter(): static;

    abstract public function withDisplayGroupFilter(): static;

    abstract public function withParentIdFilter(string $parentId): static;

    abstract public function withProductIdFilter(?string $productId, ?bool $considerVariants = false): static;

    abstract public function withProductAssociations(): static;

    abstract public function withVariantAssociations(): static;

    abstract public function withPriceZeroFilter(): static;

    abstract public function withActiveParentOrInactiveParentWithVariantsFilter(): static;

    abstract protected function adaptProductIdCriteriaWithParentId(string $productId): void;

    abstract protected function adaptProductIdCriteriaWithoutParentId(string $productId, string $parentId): void;
}
