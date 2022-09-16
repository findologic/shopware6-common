<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Attribute;
use FINDOLOGIC\Export\Data\Item;
use FINDOLOGIC\Export\Data\Ordernumber;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractDynamicProductGroupService;
use Throwable;

class ExportItemAdapter
{
    private AbstractDynamicProductGroupService $dynamicProductGroupService;

    public function __construct(AbstractDynamicProductGroupService $dynamicProductGroupService)
    {
        $this->dynamicProductGroupService = $dynamicProductGroupService;
    }

    public function adapt(Item $item, $product): ?Item
    {
        // TODO: Add possibility to use event dispatching
//        $this->eventDispatcher->dispatch(new BeforeItemAdaptEvent($product, $item), BeforeItemAdaptEvent::NAME);

//        try {
            $item = $this->adaptProduct($item, $product);
//        } catch (Throwable $exception) {
            // TODO: Add possibility to use logging
//            $exceptionLogger = new ExportExceptionLogger($logger ?: $this->logger);
//            $exceptionLogger->log($product, $exception);

//            return null;
//        }

//        $this->eventDispatcher->dispatch(new AfterItemAdaptEvent($product, $item), AfterItemAdaptEvent::NAME);

        return $item;
    }

    protected function adaptProduct(Item $item, $product): Item
    {
        $item->addName($product->getTranslation('name'));
        $item->addOrdernumber(new Ordernumber($product->productNumber));
        $item->addUrl('http://example.org/test.html');
        $item->addPrice(0.00);

        $categoryAttribute = new Attribute('cat');
        foreach ($this->dynamicProductGroupService->getCategories($product->id) as $category) {
            $categoryAttribute->addValue($category->getTranslation('name'));
        }
        if (count($categoryAttribute->getValues())) {
            $item->addAttribute($categoryAttribute);
        }

        return $item;
    }

    public function adaptVariant(Item $item, $product): ?Item
    {
//        $this->eventDispatcher->dispatch(new BeforeVariantAdaptEvent($product, $item), BeforeVariantAdaptEvent::NAME);

        try {
            $item->addOrdernumber(new Ordernumber($product->productNumber));
        } catch (Throwable $exception) {
//            $exceptionLogger = new ExportExceptionLogger($this->logger);
//            $exceptionLogger->log($product, $exception);
            return null;
        }

//        $this->eventDispatcher->dispatch(new AfterVariantAdaptEvent($product, $item), AfterVariantAdaptEvent::NAME);

        return $item;
    }
}
