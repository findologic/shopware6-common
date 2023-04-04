<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Enums;

enum MainVariant: string
{
    case SHOPWARE_DEFAULT = 'default';
    case MAIN_PARENT = 'parent';
    case CHEAPEST = 'cheapest';
}
