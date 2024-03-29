<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Logger;

use FINDOLOGIC\Export\Exceptions\EmptyValueNotAllowedException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\AccessEmptyPropertyException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoAttributesException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoCategoriesException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoNameException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoPricesException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductInvalidException;
use Psr\Log\LoggerInterface;
use Throwable;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class ExportExceptionLogger
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function log(ProductEntity $product, Throwable $e): void
    {
        switch (true) {
            case $e instanceof ProductInvalidException:
                $this->logProductInvalidException($product, $e);
                break;
            case $e instanceof EmptyValueNotAllowedException:
                $this->logEmptyValueNotAllowedException($product, $e);
                break;
            case $e instanceof Throwable:
            default:
                $this->logGenericException($product, $e);
        }
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    private function logProductInvalidException(ProductEntity $product, ProductInvalidException $e): void
    {
        $message = match (get_class($e)) {
            AccessEmptyPropertyException::class => sprintf(
                'Product "%s" with id %s was not exported because the property does not exist',
                $product->getTranslation('name'),
                $e->getProduct()->id,
            ),
            ProductHasNoAttributesException::class => sprintf(
                'Product "%s" with id %s was not exported because it has no attributes',
                $product->getTranslation('name'),
                $e->getProduct()->id,
            ),
            ProductHasNoNameException::class => sprintf(
                'Product with id %s was not exported because it has no name set',
                $e->getProduct()->id,
            ),
            ProductHasNoPricesException::class => sprintf(
                'Product "%s" with id %s was not exported because it has no price associated to it',
                $product->getTranslation('name'),
                $e->getProduct()->id,
            ),
            ProductHasNoCategoriesException::class => sprintf(
                'Product "%s" with id %s was not exported because it has no categories assigned',
                $product->getTranslation('name'),
                $e->getProduct()->id,
            ),
            default => sprintf(
                'Product "%s" with id %s could not be exported.',
                $product->getTranslation('name'),
                $e->getProduct()->id,
            ),
        };

        $this->logger->warning($message, ['exception' => $e]);
    }

    private function logEmptyValueNotAllowedException(ProductEntity $product, EmptyValueNotAllowedException $e): void
    {
        $error = sprintf(
            'Product "%s" with id "%s" could not be exported.',
            $product->getTranslation('name'),
            $product->id,
        );
        $reason = 'It appears to have empty values assigned to it.';
        $help = 'If you see this message in your logs, please report this as a bug.';

        $this->logger->warning(implode(' ', [$error, $reason, $help]), ['exception' => $e]);
    }

    private function logGenericException(ProductEntity $product, Throwable $e): void
    {
        $error = sprintf(
            'Error while exporting the product "%s" with id "%s".',
            $product->getTranslation('name'),
            $product->id,
        );
        $help = 'If you see this message in your logs, please report this as a bug.';
        $errorDetails = sprintf('Error message: %s', $e->getMessage());

        $this->logger->warning(implode(' ', [$error, $help, $errorDetails]), ['exception' => $e]);
    }
}
