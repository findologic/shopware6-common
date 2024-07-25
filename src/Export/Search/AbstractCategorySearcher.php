<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Search;

use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryCollection;

abstract class AbstractCategorySearcher
{
    public function __construct(
        protected readonly ExportContext $exportContext,
    ) {
    }

    abstract public function fetchParentsFromCategoryPath(string $categoryPath): CategoryCollection;

    abstract public function getProductStreamCategories(?int $count = null, ?int $offset = null): CategoryCollection;
}
