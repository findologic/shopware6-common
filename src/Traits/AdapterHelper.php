<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Traits;

use FINDOLOGIC\Export\Data\Attribute;
use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Vin\ShopwareSdk\Data\Entity\PropertyGroupOption\PropertyGroupOptionCollection;
use Vin\ShopwareSdk\Data\Entity\PropertyGroupOption\PropertyGroupOptionEntity;

trait AdapterHelper
{
    protected PropertyGroupOptionEntity $propertyGroupOptionEntity;
    protected PluginConfig $pluginConfig;

    /**
     * @return Attribute[]
     */
    protected function getPropertyGroupOptionAttributes(
        PropertyGroupOptionCollection $collection,
        PluginConfig $pluginConfig
    ): array {
        $this->pluginConfig = $pluginConfig;
        $attributes = [];

        foreach ($collection as $propertyGroupOptionEntity) {
            if ($propertyGroupOptionEntity->group && !$propertyGroupOptionEntity->group->filterable) {
                continue;
            }
            $this->propertyGroupOptionEntity = $propertyGroupOptionEntity;
            $attributes = array_merge($attributes, $this->getAttributePropertyAsAttribute());
        }

        return $attributes;
    }

    /**
     * @return Attribute[]
     */
    protected function getAttributePropertyAsAttribute(): array
    {
        $attributes = [];

        $group = $this->propertyGroupOptionEntity->group;
        if ($group && $this->propertyGroupOptionEntity->getTranslation('name') && $group->getTranslation('name')) {
            $groupName = static::getAttributeKey($group->getTranslation('name'), $this->pluginConfig);
            $propertyGroupOptionName = $this->propertyGroupOptionEntity->getTranslation('name');
            if (!Utils::isEmpty($groupName) && !Utils::isEmpty($propertyGroupOptionName)) {
                $propertyGroupAttrib = new Attribute($groupName);
                $propertyGroupAttrib->addValue(Utils::removeControlCharacters($propertyGroupOptionName));

                $attributes[] = $propertyGroupAttrib;
            }
        }

        return $attributes;
    }

    /**
     * For API Integrations, we have to remove special characters from the attribute key as a requirement for
     * sending data via API.
     */
    protected static function getAttributeKey(?string $key, PluginConfig $pluginConfig): ?string
    {
        if ($pluginConfig->isIntegrationTypeApi()) {
            return Utils::removeSpecialChars($key);
        }

        return $key;
    }
}
