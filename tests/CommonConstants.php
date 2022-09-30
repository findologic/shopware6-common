<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests;

use Vin\ShopwareSdk\Data\Defaults;

class CommonConstants
{
    public const VALID_SHOPKEY = 'ABCDABCDABCDABCDABCDABCDABCDABCD';

    public const VALID_SHOPKEY2 = 'DCBADCBADCBADCBADCBADCBADCBADCBA';

    public const SALES_CHANNEL_ID = Defaults::SALES_CHANNEL;

    public const SALES_CHANNEL2_ID = 'b7d2554b0ce847cd82f3ac9bd1c0dfca';

    public const LANGUAGE_ID = Defaults::LANGUAGE_SYSTEM;

    public const LANGUAGE2_ID = '37ac18b20c8a49e882611d531e3e926f';

    public const CURRENCY_ID = Defaults::CURRENCY;

    public const CURRENCY2_ID = 'd45115cfb8c548648779654e8dea3d45';

    public const NET_CUSTOMER_GROUP_ID = '3cb6c58f3bff4588a1084550d6a27481';

    public const GROSS_CUSTOMER_GROUP_ID = '8d1690afd857412dbec2ce094e60548c';

    public const NAVIGATION_CATEGORY_ID = '83428a6b53874a3e80ec54b840a9db03';
}
