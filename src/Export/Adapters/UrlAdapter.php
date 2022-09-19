<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Url;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractUrlBuilderService;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class UrlAdapter
{
    protected AbstractUrlBuilderService $urlBuilderService;

    public function __construct(AbstractUrlBuilderService $urlBuilderService) {
        $this->urlBuilderService = $urlBuilderService;
    }

    public function adapt(ProductEntity $product): ?Url
    {
        $rawUrl = $this->urlBuilderService->buildProductUrl($product);

        $url = new Url();
        $url->setValue($rawUrl);

        return $url;
    }
}
