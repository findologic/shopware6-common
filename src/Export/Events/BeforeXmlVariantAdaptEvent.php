<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Events;

use FINDOLOGIC\Export\Data\Item;
use FINDOLOGIC\Export\Data\Variant;
use Symfony\Contracts\EventDispatcher\Event;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class BeforeXmlVariantAdaptEvent extends Event
{
    public const NAME = 'fin_search.export.before_xml_variant_adapt';

    protected ProductEntity $product;

    protected Variant $variant;

    public function __construct(ProductEntity $product, Variant $item)
    {
        $this->product = $product;
        $this->variant = $item;
    }

    public function getProduct(): ProductEntity
    {
        return $this->product;
    }

    public function getVariant(): Variant
    {
        return $this->variant;
    }
}
