<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Group;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class GroupsAdapter
{
    public function __construct(
        protected readonly ExportContext $exportContext,
    ) {
    }

    /**
     * @return Group[]
     */
    public function adapt(ProductEntity $product): array
    {
        $groups = [];

        foreach ($this->exportContext->getCustomerGroups() as $customerGroupEntity) {
            $groups[] = new Group($customerGroupEntity->id);
        }

        return $groups;
    }
}
