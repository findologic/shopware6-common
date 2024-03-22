<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Shopware6Common\Export\Adapters\VariantConfigurationAdapter;
use FINDOLOGIC\Shopware6Common\Export\Config\PluginConfig;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\TestCase;
use Vin\ShopwareSdk\Data\Uuid\Uuid;

class VariantConfigurationAdapterTest extends TestCase
{
    use ProductHelper;
    use AdapterHelper;

    public VariantConfigurationAdapter $variantConfigurationAdapter;
    public PluginConfig $pluginConfig;
    public function setUp(): void
    {
        $this->pluginConfig = $this->getPluginConfig();
        $this->variantConfigurationAdapter = $this->getVariantConfigurationAdapter($this->pluginConfig);
    }

    public function testGetVariantAttributes(): void
    {
        $id = Uuid::randomHex();
        $product = $this->createTestProduct([
            'id' => $id,
        ]);
        $attributes = $this->variantConfigurationAdapter->adapt($product);
        $this->assertCount(2, $attributes);
        $this->assertSame('red', $attributes[0]->getValues()[0]);
        $this->assertSame('blue', $attributes[1]->getValues()[0]);
    }
}