<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Item;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class ExportItemAdapter
{
    private AdapterFactory $adapterFactory;

    public function __construct(AdapterFactory $adapterFactory) {
        $this->adapterFactory = $adapterFactory;
    }

    public function adaptProduct(Item $item, ProductEntity $product): ?Item
    {
        foreach ($this->adapterFactory->getAttributeAdapter()->adapt($product) as $attribute) {
            $item->addMergedAttribute($attribute);
        }

        if ($bonus = $this->adapterFactory->getBonusAdapter()->adapt($product)) {
            $item->setBonus($bonus);
        }

        if ($dateAdded = $this->adapterFactory->getDateAddedAdapter()->adapt($product)) {
            $item->setDateAdded($dateAdded);
        }

        if ($description = $this->adapterFactory->getDescriptionAdapter()->adapt($product)) {
            $item->setDescription($description);
        }

        foreach ($this->adapterFactory->getImagesAdapter()->adapt($product) as $image) {
            $item->addImage($image);
        }

        foreach ($this->adapterFactory->getKeywordsAdapter()->adapt($product) as $keyword) {
            $item->addKeyword($keyword);
        }

        if ($name = $this->adapterFactory->getNameAdapter()->adapt($product)) {
            $item->setName($name);
        }

        foreach ($this->adapterFactory->getOrderNumbersAdapter()->adapt($product) as $orderNumber) {
            $item->addOrdernumber($orderNumber);
        }

        $item->setAllPrices($this->adapterFactory->getPriceAdapter()->adapt($product));

        foreach ($this->adapterFactory->getDefaultPropertiesAdapter()->adapt($product) as $property) {
            $item->addProperty($property);
        }

        foreach ($this->adapterFactory->getShopwarePropertiesAdapter()->adapt($product) as $property) {
            $item->addProperty($property);
        }

        if ($salesFrequency = $this->adapterFactory->getSalesFrequencyAdapter()->adapt($product)) {
            $item->setSalesFrequency($salesFrequency);
        }

        if ($sort = $this->adapterFactory->getSortAdapter()->adapt($product)) {
            $item->setSort($sort);
        }

        if ($summary = $this->adapterFactory->getSummaryAdapter()->adapt($product)) {
            $item->setSummary($summary);
        }

        if ($url = $this->adapterFactory->getUrlAdapter()->adapt($product)) {
            $item->setUrl($url);
        }

        foreach ($this->adapterFactory->getUserGroupsAdapter()->adapt($product) as $userGroup) {
            $item->addUsergroup($userGroup);
        }

        return $item;
    }

    public function adaptVariant(Item $item, ProductEntity $product): ?Item
    {
        foreach ($this->adapterFactory->getOrderNumbersAdapter()->adapt($product) as $orderNumber) {
            $item->addOrdernumber($orderNumber);
        }

        foreach ($this->adapterFactory->getAttributeAdapter()->adapt($product) as $attribute) {
            $item->addMergedAttribute($attribute);
        }

        foreach ($this->adapterFactory->getShopwarePropertiesAdapter()->adapt($product) as $property) {
            $item->addProperty($property);
        }
    }
}
