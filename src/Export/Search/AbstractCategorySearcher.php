<?php

namespace FINDOLOGIC\Shopware6Common\Export\Search;

use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryCollection;

abstract class AbstractCategorySearcher
{
    protected ExportContext $exportContext;

    public function __construct(
        ExportContext $exportContext
    ) {
        $this->exportContext = $exportContext;
    }

    abstract public function fetchParentsFromCategoryPath(string $categoryPath): CategoryCollection;

    abstract public function getProductStreamCategories(?int $count = null, ?int $offset = null): CategoryCollection;
}
