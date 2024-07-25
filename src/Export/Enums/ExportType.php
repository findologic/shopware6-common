<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Enums;

enum ExportType: string
{
    /*
     * Creates a Findologic-consumable XML file, containing all product data as XML representation.
     */
    case XML = 'product';

    /*
     * May be used for debugging purposes. In case any of the products can not be exported due to any reasons,
     * the reason will be shown in JSON format. When all products are valid, the default XML export will be used
     * to generate a Findologic-consumable XML file.
     */
    case DEBUG = 'debug';
}
