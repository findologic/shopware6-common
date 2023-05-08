<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Enums;

enum IntegrationType: string
{
    case DI = 'Direct Integration';
    case API = 'API';
}
