<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Enums;

enum ImplementationType: string
{
    case APP = 'App';
    case PLUGIN = 'Plugin';
}
