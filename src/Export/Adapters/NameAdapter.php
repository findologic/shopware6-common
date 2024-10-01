<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Name;
use FINDOLOGIC\Shopware6Common\Export\Exceptions\Product\ProductHasNoNameException;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class NameAdapter implements AdapterInterface
{
    /**
     * @throws ProductHasNoNameException
     */
    public function adapt(ProductEntity $product): ?Name
    {
        $value = new Name();
        $value->setValue($this->getCleanedName($product));

        return $value;
    }

    /**
     * @throws ProductHasNoNameException
     */
    protected function getCleanedName(ProductEntity $product): string
    {
        $name = $product->getTranslation('name');

        if (Utils::isEmpty($name)) {
            throw new ProductHasNoNameException($product);
        }

        return Utils::removeControlCharacters($name);
    }
}
