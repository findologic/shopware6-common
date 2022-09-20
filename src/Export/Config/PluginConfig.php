<?php

namespace FINDOLOGIC\Shopware6Common\Export\Config;

use Vin\ShopwareSdk\Data\Struct;

class PluginConfig extends Struct
{
    protected ?string $shopkey;

    protected bool $active;

    protected bool $exportZeroPricedProducts = false;

    protected string $advancedPricing = AdvancedPricing::OFF;

    protected string $mainVariant = MainVariant::SHOPWARE_DEFAULT;

    /** @var string[] */
    protected array $crossSellingCategories = [];

    protected string $integrationType = IntegrationType::DI;

    public static function createFromArray(array $values): self
    {
        $config = new self();

        foreach ($values as $key => $value) {
            if (property_exists($config, $key)) {
                $config->$key = $value;
            }
        }

        return $config;
    }

    public function getShopkey(): ?string
    {
        return $this->shopkey;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function exportZeroPricedProducts(): bool
    {
        return $this->exportZeroPricedProducts;
    }

    public function getAdvancedPricing(): string
    {
        return $this->advancedPricing;
    }

    public function getMainVariant(): string
    {
        return $this->mainVariant;
    }

    public function getCrossSellingCategories(): array
    {
        return $this->crossSellingCategories;
    }

    public function isIntegrationTypeApi(): bool
    {
        return $this->integrationType === IntegrationType::API;
    }
}
