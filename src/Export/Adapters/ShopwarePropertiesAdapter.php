<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Property;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;
use Vin\ShopwareSdk\Data\Entity\PropertyGroupOption\PropertyGroupOptionEntity;

class ShopwarePropertiesAdapter
{
    protected ExportContext $exportContext;

    public function __construct(ExportContext $exportContext) {
        $this->exportContext = $exportContext;
    }

    public function adapt(ProductEntity $product): array
    {
        $properties = [];

        /** @var PropertyGroupOptionEntity $propertyGroupOptionEntity */
        foreach ($product->properties as $propertyGroupOptionEntity) {
            $group = $propertyGroupOptionEntity->group;
            if ($group && !$group->filterable) {
                // Non-filterable properties should be available in the properties field.
                $properties = array_merge(
                    $properties,
                    $this->getAttributePropertyAsProperty($propertyGroupOptionEntity)
                );
            }
        }

        return $properties;
    }

    protected function getAttributePropertyAsProperty(PropertyGroupOptionEntity $propertyGroupOptionEntity): array
    {
        $properties = [];

        $group = $propertyGroupOptionEntity->group;
        if ($group && $propertyGroupOptionEntity->getTranslation('name') && $group->getTranslation('name')) {
            $groupName = $this->getAttributeKey($group->getTranslation('name'));
            $propertyGroupOptionName = $propertyGroupOptionEntity->getTranslation('name');
            if (!Utils::isEmpty($groupName) && !Utils::isEmpty($propertyGroupOptionName)) {
                $propertyGroupProperty = new Property($groupName);
                $propertyGroupProperty->addValue(Utils::removeControlCharacters($propertyGroupOptionName));

                $properties[] = $propertyGroupProperty;
            }
        }

        return $properties;
    }

    protected function getAttributeKey(?string $key): ?string
    {
        if ($this->exportContext->isIntegrationTypeApi()) {
            return Utils::removeSpecialChars($key);
        }

        return $key;
    }
}
