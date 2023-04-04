<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Exceptions\Product;

use FINDOLOGIC\Shopware6Common\Export\Exceptions\ExportException;
use Throwable;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class ProductInvalidException extends ExportException
{
    public function __construct(
        private readonly ProductEntity $failedProduct,
        $message = '',
        $code = 0,
        Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getProduct(): ProductEntity
    {
        return $this->failedProduct;
    }
}
