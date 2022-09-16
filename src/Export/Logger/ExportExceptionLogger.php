<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Logger;

use FINDOLOGIC\Export\Exceptions\EmptyValueNotAllowedException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoCategoriesException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoNameException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoPricesException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductInvalidException;
use Psr\Log\LoggerInterface;
use Throwable;

class ExportExceptionLogger
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function log($product, Throwable $e): void
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

    public function setLogger(LoggerInterface $logger): ExportExceptionLogger
    {
        $this->logger = $logger;

        return $this;
    }

    private function logProductInvalidException($product, ProductInvalidException $e): void {
        switch (get_class($e)) {
            case AccessEmptyPropertyException::class:
                $message = sprintf(
                    'Product "%s" with id %s was not exported because the property does not exist',
                    $product->getTranslation('name'),
                    $e->getProduct()->getId()
                );
                break;
            case ProductHasNoAttributesException::class:
                $message = sprintf(
                    'Product "%s" with id %s was not exported because it has no attributes',
                    $product->getTranslation('name'),
                    $e->getProduct()->getId()
                );
                break;
            case ProductHasNoNameException::class:
                $message = sprintf(
                    'Product with id %s was not exported because it has no name set',
                    $e->getProduct()->getId()
                );
                break;
            case ProductHasNoPricesException::class:
                $message = sprintf(
                    'Product "%s" with id %s was not exported because it has no price associated to it',
                    $product->getTranslation('name'),
                    $e->getProduct()->getId()
                );
                break;
            case ProductHasNoCategoriesException::class:
                $message = sprintf(
                    'Product "%s" with id %s was not exported because it has no categories assigned',
                    $product->getTranslation('name'),
                    $e->getProduct()->getId()
                );
                break;
            default:
                $message = sprintf(
                    'Product "%s" with id %s could not be exported.',
                    $product->getTranslation('name'),
                    $e->getProduct()->getId()
                );
        }

        $this->logger->warning($message, ['exception' => $e]);
    }

    private function logEmptyValueNotAllowedException($product, EmptyValueNotAllowedException $e): void {
        $error = sprintf(
            'Product "%s" with id "%s" could not be exported.',
            $product->getTranslation('name'),
            $product->getId()
        );
        $reason = 'It appears to have empty values assigned to it.';
        $help = 'If you see this message in your logs, please report this as a bug.';

        $this->logger->warning(implode(' ', [$error, $reason, $help]), ['exception' => $e]);
    }

    private function logGenericException($product, Throwable $e): void {
        $error = sprintf(
            'Error while exporting the product "%s" with id "%s".',
            $product->getTranslation('name'),
            $product->getId()
        );
        $help = 'If you see this message in your logs, please report this as a bug.';
        $errorDetails = sprintf('Error message: %s', $e->getMessage());

        $this->logger->warning(implode(' ', [$error, $help, $errorDetails]), ['exception' => $e]);
    }
}
