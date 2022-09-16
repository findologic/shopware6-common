<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Attribute;
use FINDOLOGIC\Export\Data\Item;
use FINDOLOGIC\Export\Data\Ordernumber;
use FINDOLOGIC\Shopware6Common\Export\Services\AbstractDynamicProductGroupService;

class ExportItemAdapter
{
    private AbstractDynamicProductGroupService $dynamicProductGroupService;

    public function __construct(AbstractDynamicProductGroupService $dynamicProductGroupService)
    {
        $this->dynamicProductGroupService = $dynamicProductGroupService;
    }

    public function adaptProduct(Item $item, $product): ?Item
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
        $item->addOrdernumber(new Ordernumber($product->productNumber));

        return $item;
    }
}
