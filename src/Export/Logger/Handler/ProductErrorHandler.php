<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Logger\Handler;

use BadMethodCallException;
use FINDOLOGIC\Shopware6Common\Export\Errors\ExportErrors;
use FINDOLOGIC\Shopware6Common\Export\Errors\ProductError;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductInvalidException;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\HandlerInterface;
use Throwable;

class ProductErrorHandler implements HandlerInterface
{
    private ExportErrors $exportErrors;

    public function __construct(?ExportErrors $exportErrors = null)
    {
        $this->exportErrors = $exportErrors ?? new ExportErrors();
    }

    public function isHandling(array $record): bool
    {
        return true;
    }

    public function handle(array $record): bool
    {
        $this->handleGeneralError($record);
        $this->handleProductError($record);

        return true;
    }

    public function handleBatch(array $records): void
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }

    public function pushProcessor($callback)
    {
        throw new BadMethodCallException('Pushing processors is not supported by the ProductErrorHandler.');
    }

    public function popProcessor()
    {
        throw new BadMethodCallException('Popping processors is not supported by the ProductErrorHandler.');
    }

    public function setFormatter(FormatterInterface $formatter)
    {
        throw new BadMethodCallException('Formatting is not supported by the ProductErrorHandler.');
    }

    public function getFormatter()
    {
        throw new BadMethodCallException('Formatting is not supported by the ProductErrorHandler.');
    }

    public function close(): void
    {
        // Nothing to close.
    }

    public function getExportErrors(): ExportErrors
    {
        return $this->exportErrors;
    }

    protected function handleGeneralError(array $record): void
    {
        if (empty($record['context'])) {
            $this->exportErrors->addGeneralError($record['message']);
        }
    }

    /**
     * @param array $record
     */
    protected function handleProductError(array $record): void
    {
        if (isset($record['context']['exception'])) {
            /** @var ProductInvalidException $exception */
            $exception = $record['context']['exception'];
            if (!$exception instanceof ProductInvalidException) {
                $this->handleGeneralException($exception);

                return;
            }

            $product = $exception->getProduct();
            $productError = new ProductError($product->id, [$record['message']]);

            $this->exportErrors->addProductError($productError);
        }

        if (isset($record['context']['product'])) {
            $product = $record['context']['product'];
            $productError = new ProductError($product->id, [$record['message']]);

            $this->exportErrors->addProductError($productError);
        }
    }

    protected function handleGeneralException(Throwable $e): void
    {
        $this->exportErrors->addGeneralError($e->getMessage());
    }
}
