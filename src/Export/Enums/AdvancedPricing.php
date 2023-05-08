<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Enums;

enum AdvancedPricing: string
{
    case OFF = 'off';
    case CHEAPEST = 'cheapest';
    case UNIT = 'unit';
}
