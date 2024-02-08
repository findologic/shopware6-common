<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Usergroup;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupEntity;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class UserGroupsAdapter implements AdapterInterface
{
    protected ExportContext $exportContext;

    public function __construct(ExportContext $exportContext)
    {
        $this->exportContext = $exportContext;
    }

    /**
     * @return Usergroup[]
     */
    public function adapt(ProductEntity $product): array
    {
        $userGroups = [];

        /** @var CustomerGroupEntity $customerGroupEntity */
        foreach ($this->exportContext->getCustomerGroups() as $customerGroupEntity) {
            $userGroups[] = new Usergroup($customerGroupEntity->id);
        }

        return $userGroups;
    }
}
