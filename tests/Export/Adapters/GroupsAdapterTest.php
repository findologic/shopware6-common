<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Export\Data\Group;
use FINDOLOGIC\Shopware6Common\Tests\CommonConstants;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\TestCase;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupCollection;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupEntity;

class GroupsAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;

    public function testgroupsContainsTheGroupsOfTheProduct(): void
    {
        $customerGroup = new CustomerGroupEntity();
        $customerGroup->id = CommonConstants::NET_CUSTOMER_GROUP_ID;

        $expectedGroup = new Group($customerGroup->id);

        $adapter = $this->getGroupAdapter(
            new CustomerGroupCollection([$customerGroup]),
        );
        $product = $this->createTestProduct([]);

        $groups = $adapter->adapt($product);

        $this->assertCount(1, $groups);
        $this->assertEquals($expectedGroup, $groups[0]);
    }
}
