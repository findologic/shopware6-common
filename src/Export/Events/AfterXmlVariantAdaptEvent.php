<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Events;

use FINDOLOGIC\Export\Data\Variant;
use Symfony\Contracts\EventDispatcher\Event;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class AfterXmlVariantAdaptEvent extends Event
{
    public const NAME = 'fin_search.export.after_xml_variant_adapt';

    public function __construct(
        private readonly ProductEntity $product,
        private readonly Variant $variant,
    ) {
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
