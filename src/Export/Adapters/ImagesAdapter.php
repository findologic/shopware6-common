<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Image;
use FINDOLOGIC\Shopware6Common\Export\Services\ProductImageService;

class ImagesAdapter implements AdapterInterface
{
    protected ProductImageService $productImageService;

    public function __construct(ProductImageService $productImageService)
    {
        $this->productImageService = $productImageService;
    }

    /**
     * @return Image[]
     */
    public function adapt($product): array
    {
        return $this->productImageService->getProductImages($product, false);
    }
}
