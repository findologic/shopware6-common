<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Types;

use FINDOLOGIC\Export\Data\Item;
use FINDOLOGIC\Export\Exporter;
use FINDOLOGIC\Export\XML\XMLExporter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\ExportItemAdapter;
use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use FINDOLOGIC\Shopware6Common\Export\Search\AbstractProductSearcher;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractDynamicProductGroupService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryCollection;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryEntity;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class XmlExport extends AbstractExport
{
    private const MAXIMUM_PROPERTIES_COUNT = 500;

    protected AbstractDynamicProductGroupService $dynamicProductGroupService;

    protected AbstractProductSearcher $productSearcher;

    protected PluginConfig $pluginConfig;

    protected ExportItemAdapter $exportItemAdapter;

    protected ?LoggerInterface $logger;

    protected XMLExporter $xmlFileConverter;

    public function __construct(
        AbstractDynamicProductGroupService $dynamicProductGroupService,
        AbstractProductSearcher $productSearcher,
        PluginConfig $pluginConfig,
        ExportItemAdapter $exportItemAdapter,
        ?LoggerInterface $logger = null
    ) {
        $this->dynamicProductGroupService = $dynamicProductGroupService;
        $this->productSearcher = $productSearcher;
        $this->pluginConfig = $pluginConfig;
        $this->exportItemAdapter = $exportItemAdapter;
        $this->logger = $logger;

        /** @var XMLExporter $exporter */
        $exporter = Exporter::create(Exporter::TYPE_XML);
        $this->xmlFileConverter = $exporter;
    }

    /** @inheritDoc */
    public function buildResponse(array $items, int $start, int $total, array $headers = []): Response
    {
        $rawXml = $this->xmlFileConverter->serializeItems(
            $items,
            $start,
            count($items),
            $total
        );

        $response = new Response($rawXml);
        $response->headers->add($headers);

        return $response;
    }

    /** @inheritDoc */
    public function buildItems(array $products): array
    {
        $items = [];
        foreach ($products as $productEntity) {
            $item = $this->exportSingleItem($productEntity);
            if (!$item) {
                continue;
            }
            // TODO: Event dispatching
//            $this->eventDispatcher->dispatch(new AfterItemBuildEvent($item), AfterItemBuildEvent::NAME);

            $items[] = $item;
        }

        return $items;
    }

    private function exportSingleItem(ProductEntity $product): ?Item
    {
        $category = $this->getConfiguredCrossSellingCategory(
            $product->id,
            $product->categories,
        );
        if ($category && $this->logger) {
            $this->logger->warning(
                sprintf(
                    'Product with id %s (%s) was not exported because it is assigned to cross selling category %s (%s)',
                    $product->id,
                    $product->getTranslation('name'),
                    $category->id,
                    implode(' < ', $category->breadcrumb)
                ),
                ['product' => $product]
            );

            return null;
        }

        $initialItem = $this->xmlFileConverter->createItem($product->id);
        $item = $this->exportItemAdapter->adaptProduct($initialItem, $product);

//        $pageSize = $this->calculatePageSize($productEntity);
//        $iterator = $this->productSearcher->buildVariantIterator($productEntity, $pageSize);
//
//        while (($variantsResult = $iterator->fetch()) !== null) {
//            /** @var ProductCollection $variants */
//            $variants = $variantsResult->getEntities();
//            foreach ($variants->getElements() as $variant) {
//                if ($item) {
//                    $adaptedItem = $this->exportItemAdapter->adaptVariant($item, $variant);
//                } elseif ($adaptedItem = $this->exportItemAdapter->adapt($initialItem, $variant)) {
//                    $adaptedItem->setId($variant->getId());
//                }
//
//                if ($adaptedItem) {
//                    $item = $adaptedItem;
//                }
//            }
//        }

        return $item;
    }

    private function calculatePageSize(ProductEntity $product): int
    {
        $maxPropertiesCount = $this->productSearcher->findMaxPropertiesCount(
            $product->id,
            $product->parentId,
            $product->propertyIds
        );
        if ($maxPropertiesCount >= self::MAXIMUM_PROPERTIES_COUNT) {
            return 1;
        }

        return intval(self::MAXIMUM_PROPERTIES_COUNT / max(1, $maxPropertiesCount));
    }

    private function getConfiguredCrossSellingCategory(string $productId, CategoryCollection $productCategories): ?CategoryEntity
    {
        $crossSellingCategories = $this->pluginConfig->getCrossSellingCategories();
        if (count($crossSellingCategories)) {
            $categories = array_merge(
                $productCategories->getElements(),
                $this->dynamicProductGroupService->getCategories($productId)->getElements()
            );

            foreach ($categories as $categoryId => $category) {
                if (in_array($categoryId, $crossSellingCategories)) {
                    return $category;
                }
            }
        }

        return null;
    }
}
