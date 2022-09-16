<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Exceptions\Product;

use FINDOLOGIC\Shopware6Common\Export\Exceptions\ExportException;
use Throwable;

class ProductInvalidException extends ExportException
{
    private $failedProduct;

    public function __construct($failedProduct, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->failedProduct = $failedProduct;

        parent::__construct($message, $code, $previous);
    }

    public function getProduct()
    {
        return $this->failedProduct;
    }
}
