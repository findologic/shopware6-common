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
        'variantListingConfig',
        'visibilities',
    ];

    public const VARIANT_ASSOCIATIONS = [
        'seoUrls',
        'media',
        'categories',
        'categories.seoUrls',
        'options',
        'options.group',
        'properties',
        'properties.group',
    ];

    public const PARENT_CATEGORY_EXTENSION = 'parentCategories';
}
