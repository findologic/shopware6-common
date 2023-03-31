<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Config;

use FINDOLOGIC\Shopware6Common\Export\Config\AdvancedPricing;
use FINDOLOGIC\Shopware6Common\Export\Config\IntegrationType;
use FINDOLOGIC\Shopware6Common\Export\Config\MainVariant;
use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use PHPUnit\Framework\TestCase;

class PluginConfigTest extends TestCase
{
    public function testCreateFromArray(): void
    {
        $pluginConfig = PluginConfig::createFromArray([
            'shopkey' => 'ABAB',
            'active' => false,
            'exportZeroPricedProducts' => true,
            'advancedPricing' => AdvancedPricing::CHEAPEST,
            'mainVariant' => MainVariant::CHEAPEST,
            'crossSellingCategories' => ['this-is-an-uuid'],
            'integrationType' => IntegrationType::API,
            'useXmlVariants' => true,
        ]);

        $this->assertEquals('ABAB', $pluginConfig->getShopkey());
        $this->assertFalse($pluginConfig->isActive());
        $this->assertTrue($pluginConfig->exportZeroPricedProducts());
        $this->assertEquals(AdvancedPricing::CHEAPEST, $pluginConfig->getAdvancedPricing());
        $this->assertEquals(MainVariant::CHEAPEST, $pluginConfig->getMainVariant());
        $this->assertEquals(['this-is-an-uuid'], $pluginConfig->getCrossSellingCategories());
        $this->assertTrue($pluginConfig->isIntegrationTypeApi());
        $this->assertFalse($pluginConfig->isIntegrationTypesDirectIntegration());
        $this->assertTrue($pluginConfig->useXmlVariants());
    }
}
