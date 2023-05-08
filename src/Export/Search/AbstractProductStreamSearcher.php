<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Search;

abstract class AbstractProductStreamSearcher
{
    abstract public function isProductInDynamicProductGroup(string $productId, string $streamId): bool;
}
