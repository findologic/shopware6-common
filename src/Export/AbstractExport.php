<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export;

use FINDOLOGIC\Export\Data\Item;
use FINDOLOGIC\FinSearch\Logger\Handler\ProductErrorHandler;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractDynamicProductGroupService;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractExport
{
    /**
     * Creates a Findologic-consumable XML file, containing all product data as XML representation.
     */
    public const TYPE_XML = 0;

    /**
     * May be used for debugging purposes. In case any of the products can not be exported due to any reasons,
     * the reason will be shown in JSON format. When all products are valid, the default XML export will be used
     * to generate a Findologic-consumable XML file.
     */
    public const TYPE_PRODUCT_ID = 1;

    public function buildErrorResponse(ProductErrorHandler $errorHandler, array $headers): JsonResponse
    {
        // TODO: Add HeaderHandler
        $headers['content-type'] = 'application/json';

        return new JsonResponse(
            $errorHandler->getExportErrors()->buildErrorResponse(),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $headers
        );
    }

    abstract public function buildItems(array $products): array;

    /**
     * @param Item[] $items
     */
    abstract public function buildResponse(array $items, int $start, int $total, array $headers = []): Response;
}
