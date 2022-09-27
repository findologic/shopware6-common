<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Types;

use FINDOLOGIC\Export\Data\Item;
use FINDOLOGIC\Shopware6Common\Export\Adapters\ExportItemAdapter;
use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use FINDOLOGIC\Shopware6Common\Export\Logger\Handler\ProductErrorHandler;
use FINDOLOGIC\Shopware6Common\Export\Search\AbstractProductSearcher;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractDynamicProductGroupService;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

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

    public static function getInstance(
        int $type,
        AbstractDynamicProductGroupService $dynamicProductGroupService,
        AbstractProductSearcher $productSearcher,
        PluginConfig $pluginConfig,
        ExportItemAdapter $exportItemAdapter,
        LoggerInterface $logger,
        ?EventDispatcherInterface $eventDispatcher = null
    ): self {
        switch ($type) {
            case self::TYPE_XML:
                return new XmlExport(
                    $dynamicProductGroupService,
                    $productSearcher,
                    $pluginConfig,
                    $exportItemAdapter,
                    $logger,
                    $eventDispatcher,
                );
            case self::TYPE_PRODUCT_ID:
                return new ProductIdExport(
                    $dynamicProductGroupService,
                    $productSearcher,
                    $pluginConfig,
                    $exportItemAdapter,
                    $logger,
                    $eventDispatcher,
                );
            default:
                throw new InvalidArgumentException(sprintf('Unknown export type %d.', $type));
        }
    }

    public static function buildErrorResponse(ProductErrorHandler $errorHandler): JsonResponse
    {
        return new JsonResponse(
            $errorHandler->getExportErrors()->buildErrorResponse(),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    /**
     * @param Item[] $items
     */
    abstract public function buildResponse(array $items, int $start, int $total, array $headers = []): Response;

    /**
     * Converts given product entities to Findologic XML items. In case items can not be exported, they won't
     * be returned. Details about why specific products can not be exported, can be found in the logs.
     *
     * @param ProductEntity[] $products
     *
     * @return Item[]
     */
    abstract public function buildItems(array $products): array;
}
