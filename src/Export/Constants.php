<?php

namespace FINDOLOGIC\Shopware6Common\Export;

class Constants
{
    public const PRODUCT_ASSOCIATIONS = [
        'seoUrls',
        'translations',
        'searchKeywords',
        'media',
        'manufacturer',
        'manufacturer.translations',
        'cover',
    ];

    public const VARIANT_ASSOCIATIONS = [
        'categories',
        'categories.seoUrls',
        'properties',
        'properties.group',
    ];
}
