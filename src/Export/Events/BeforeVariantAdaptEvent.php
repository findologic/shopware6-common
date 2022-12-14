<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Events;

use FINDOLOGIC\Export\Data\Item;
use Symfony\Contracts\EventDispatcher\Event;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class BeforeVariantAdaptEvent extends Event
{
    public const NAME = 'fin_search.export.before_variant_adapt';

    protected ProductEntity $product;

    protected Item $item;

    public function __construct(ProductEntity $product, Item $item)
    {
        $this->product = $product;
        $this->item = $item;
    }

    public function getProduct(): ProductEntity
    {
        return $this->product;
    }

    public function getItem(): Item
    {
        return $this->item;
    }
}
