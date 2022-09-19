<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Exceptions\Product;

use FINDOLOGIC\Shopware6Common\Export\Exceptions\ExportException;
use Throwable;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class ProductInvalidException extends ExportException
{
    private ProductEntity $failedProduct;

    public function __construct(ProductEntity $failedProduct, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->failedProduct = $failedProduct;

        parent::__construct($message, $code, $previous);
    }

    public function getProduct(): ProductEntity
    {
        return $this->failedProduct;
    }
}
