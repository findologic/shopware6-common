<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export;

use FINDOLOGIC\Export\Data\Item;
use FINDOLOGIC\Export\Exporter;
use FINDOLOGIC\Export\XML\XMLExporter;
use FINDOLOGIC\Export\XML\XMLItem;
use FINDOLOGIC\FinSearchApp\Export\Search\ProductSearcher;
use FINDOLOGIC\Shopware6Common\Export\Adapters\ExportItemAdapter;
use FINDOLOGIC\Shopware6Common\Export\Search\AbstractProductSearcher;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractDynamicProductGroupService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;
use Vin\ShopwareSdk\Exception\ShopwareSearchResponseException;

abstract class AbstractXmlExport extends AbstractExport
{
    private const MAXIMUM_PROPERTIES_COUNT = 500;

    protected AbstractDynamicProductGroupService $dynamicProductGroupService;

    protected ContainerInterface $container;

    protected ?LoggerInterface $logger;

    protected XMLExporter $xmlFileConverter;

    protected ExportItemAdapter $exportItemAdapter;

    protected ProductSearcher $productSearcher;

    public function __construct(
        AbstractDynamicProductGroupService $dynamicProductGroupService,
        AbstractProductSearcher $productSearcher,
        ContainerInterface $container,
        ?LoggerInterface $logger = null
    ) {
        $this->dynamicProductGroupService = $dynamicProductGroupService;
        $this->productSearcher = $productSearcher;
        $this->container = $container;
        $this->logger = $logger;

        $this->exportItemAdapter = new ExportItemAdapter($dynamicProductGroupService);

        /** @var XMLExporter $exporter */
        $exporter = Exporter::create(Exporter::TYPE_XML);
        $this->xmlFileConverter = $exporter;
    }

    /**
     * @param Item[] $items
     */
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

    /**
     * Converts given product entities to Findologic XML items. In case items can not be exported, they won't
     * be returned. Details about why specific products can not be exported, can be found in the logs.
     *
     * @param array $products
     *
     * @return XMLItem[]
     */
    public function buildItems(array $products): array
    {
        $items = [];
        foreach ($products as $productEntity) {
            $item = $this->exportSingleItem($productEntity);
            if (!$item) {
                continue;
            }

//            $this->eventDispatcher->dispatch(new AfterItemBuildEvent($item), AfterItemBuildEvent::NAME);

            $items[] = $item;
        }

        return $items;
    }

    private function exportSingleItem(ProductEntity $product): ?Item
    {
        $category = $this->getConfiguredCrossSellingCategory(
            $this->getIdOfProductEntity($product),
            $this->getCategoriesOfProductEntity($product),
        );
        if ($category) {
            $this->logger?->warning(
                sprintf(
                    'Product with id %s (%s) was not exported because it is assigned to cross selling category %s (%s)',
                    $this->getIdOfProductEntity($product),
                    $this->getNameOfProductEntity($product),
                    $this->getIdOfCategoryEntity($category),
                    $this->getBreadcrumbsStringOfCategoryEntity($category)
                ),
                ['product' => $product]
            );

            return null;
        }

        $initialItem = $this->xmlFileConverter->createItem($this->getIdOfProductEntity($product));
        $item = $this->exportItemAdapter->adapt($initialItem, $product, $this->logger);

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

    /**
     * @throws ShopwareSearchResponseException
     */
    private function calculatePageSize($product): int
    {
        $maxPropertiesCount = $this->productSearcher->findMaxPropertiesCount(
            $this->getIdOfProductEntity($product),
            $this->getParentIdOfProductEntity($product),
            $this->getPropertyIdsOfProductEntity($product)
        );
        if ($maxPropertiesCount >= self::MAXIMUM_PROPERTIES_COUNT) {
            return 1;
        }

        return intval(self::MAXIMUM_PROPERTIES_COUNT / max(1, $maxPropertiesCount));
    }

    private function getConfiguredCrossSellingCategory(string $productId, array $productCategories)
    {
        // TODO: Get cross selling categories from configuration
        $crossSellingCategories = [];
        if (count($crossSellingCategories)) {
            $categories = array_merge(
                $productCategories,
                $this->dynamicProductGroupService->getCategories($productId)
            );

            foreach ($categories as $categoryId => $category) {
                if (in_array($categoryId, $crossSellingCategories)) {
                    return $category;
                }
            }
        }

        return null;
    }

    abstract protected function getIdOfProductEntity($product): string;

    abstract protected function getParentIdOfProductEntity($product): string;

    abstract protected function getPropertyIdsOfProductEntity($product): ?array;

    abstract protected function getNameOfProductEntity($product): string;

    abstract protected function getCategoriesOfProductEntity($product): array;

    abstract protected function getIdOfCategoryEntity($category): string;

    abstract protected function getBreadcrumbsStringOfCategoryEntity($category): string;
}
