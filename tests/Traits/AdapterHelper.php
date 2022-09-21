<?php

namespace FINDOLOGIC\Shopware6Common\Tests\Traits;

use FINDOLOGIC\Shopware6Common\Export\Adapters\AttributeAdapter;
use FINDOLOGIC\Shopware6Common\Export\Adapters\BonusAdapter;
use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;

trait AdapterHelper
{
    use ServicesHelper;

    public function getAttributeAdapter(?PluginConfig $config = null): AttributeAdapter
    {
        return new AttributeAdapter(
            $this->getDynamicProductGroupService(),
            $this->getUrlBuilderService(),
            $this->getExportContext(),
            $config ?? $this->getPluginConfig()
        );
    }

    public function getBonusAdapter(): BonusAdapter
    {
        return new BonusAdapter();
    }
}
