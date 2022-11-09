<?php

declare(strict_types=1);

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

    public const PARENT_CATEGORY_EXTENSION = 'parentCategories';
}
