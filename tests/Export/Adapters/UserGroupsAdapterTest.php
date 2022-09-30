<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Export\Data\Usergroup;
use FINDOLOGIC\Shopware6Common\Tests\CommonConstants;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\TestCase;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupCollection;
use Vin\ShopwareSdk\Data\Entity\CustomerGroup\CustomerGroupEntity;

class UserGroupsAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;

    public function testUserGroupsContainsTheUserGroupsOfTheProduct(): void
    {
        $customerGroup = new CustomerGroupEntity();
        $customerGroup->id = CommonConstants::NET_CUSTOMER_GROUP_ID;

        $expectedUserGroup = new Usergroup($customerGroup->id);

        $adapter = $this->getUserGroupAdapter(
            new CustomerGroupCollection([$customerGroup]),
        );
        $product = $this->createTestProduct([]);

        $userGroups = $adapter->adapt($product);

        $this->assertCount(1, $userGroups);
        $this->assertEquals($expectedUserGroup, $userGroups[0]);
    }
}
