<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Events;

use FINDOLOGIC\Export\Data\Item;
use Symfony\Contracts\EventDispatcher\Event;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class AfterVariantAdaptEvent extends Event
{
    public const NAME = 'fin_search.export.after_variant_adapt';

    public function __construct(
        private readonly ProductEntity $product,
        private readonly Item $item,
    ) {
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
