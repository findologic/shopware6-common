<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Url;
use FINDOLOGIC\Shopware6Common\Export\Services\ProductUrlService;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class UrlAdapter
{
    public function __construct(
        protected readonly ProductUrlService $productUrlService,
    ) {
    }

    public function adapt(ProductEntity $product): ?Url
    {
        $rawUrl = $this->productUrlService->buildProductUrl($product);

        $url = new Url();
        $url->setValue($rawUrl);

        return $url;
    }
}
