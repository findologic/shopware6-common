<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Item;
use FINDOLOGIC\Shopware6Common\Export\Events\AfterItemAdaptEvent;
use FINDOLOGIC\Shopware6Common\Export\Events\AfterVariantAdaptEvent;
use FINDOLOGIC\Shopware6Common\Export\Events\BeforeItemAdaptEvent;
use FINDOLOGIC\Shopware6Common\Export\Events\BeforeVariantAdaptEvent;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoCategoriesException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoNameException;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoPricesException;
use FINDOLOGIC\Shopware6Common\Export\Logger\ExportExceptionLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class ExportItemAdapter
{
    private AdapterFactory $adapterFactory;

    private LoggerInterface $logger;

    private ?EventDispatcherInterface $eventDispatcher;

    public function __construct(
        AdapterFactory $adapterFactory,
        LoggerInterface $logger,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->adapterFactory = $adapterFactory;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function adapt(Item $item, ProductEntity $product): ?Item
    {
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(new BeforeItemAdaptEvent($product, $item), BeforeItemAdaptEvent::NAME);
        }

        try {
            $item = $this->adaptProduct($item, $product);
        } catch (Throwable $exception) {
            $exceptionLogger = new ExportExceptionLogger($this->logger);
            $exceptionLogger->log($product, $exception);

            return null;
        }

        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(new AfterItemAdaptEvent($product, $item), AfterItemAdaptEvent::NAME);
        }

        return $item;
    }

    /**
     * @throws ProductHasNoPricesException
     * @throws ProductHasNoNameException
     * @throws ProductHasNoCategoriesException
     */
    public function adaptProduct(Item $item, ProductEntity $product): ?Item
    {
        $hasCategories = false;
        foreach ($this->adapterFactory->getAttributeAdapter()->adapt($product) as $attribute) {
            if ($attribute->getKey() === 'cat') {
                $hasCategories = true;
            }

            $item->addMergedAttribute($attribute);
        }

        if (!$hasCategories) {
            throw new ProductHasNoCategoriesException($product);
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

        foreach ($this->adapterFactory->getOptionsAdapter()->adapt($product) as $attribute) {
            $item->addMergedAttribute($attribute);
        }

        return $item;
    }

    public function adaptVariant(Item $item, ProductEntity $product): ?Item
    {
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(
                new BeforeVariantAdaptEvent($product, $item),
                BeforeVariantAdaptEvent::NAME,
            );
        }

        try {
            foreach ($this->adapterFactory->getOrderNumbersAdapter()->adapt($product) as $orderNumber) {
                    $item->addOrdernumber($orderNumber);
            }

            foreach ($this->adapterFactory->getAttributeAdapter()->adapt($product) as $attribute) {
                $item->addMergedAttribute($attribute);
            }

            foreach ($this->adapterFactory->getShopwarePropertiesAdapter()->adapt($product) as $property) {
                $item->addProperty($property);
            }

            foreach ($this->adapterFactory->getVariantConfigurationAdapter()->adapt($product) as $attribute) {
                $item->addMergedAttribute($attribute);
            }
        } catch (Throwable $exception) {
            $exceptionLogger = new ExportExceptionLogger($this->logger);
            $exceptionLogger->log($product, $exception);

            return null;
        }

        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(new AfterVariantAdaptEvent($product, $item), AfterVariantAdaptEvent::NAME);
        }

        return $item;
    }
}
