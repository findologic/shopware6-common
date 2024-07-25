<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Image;
use FINDOLOGIC\Shopware6Common\Export\Services\ProductImageService;

class ImagesAdapter
{
    public function __construct(
        protected readonly ProductImageService $productImageService,
    ) {
    }

    /**
     * @return Image[]
     */
    public function adapt($product): array
    {
        return $this->productImageService->getProductImages($product, false);
    }
}
