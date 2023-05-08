<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Events;

use FINDOLOGIC\Export\Data\Item;
use Symfony\Contracts\EventDispatcher\Event;

class AfterItemBuildEvent extends Event
{
    public const NAME = 'fin_search.export.after_item_builb';

    public function __construct(
        private readonly Item $item,
    ) {
    }

    public function getItem(): Item
    {
        return $this->item;
    }
}
