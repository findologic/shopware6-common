<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Config;

use FINDOLOGIC\Shopware6Common\Export\Enums\AdvancedPricing;
use FINDOLOGIC\Shopware6Common\Export\Enums\IntegrationType;
use FINDOLOGIC\Shopware6Common\Export\Enums\MainVariant;
use Vin\ShopwareSdk\Data\Struct;

class PluginConfig extends Struct
{
    protected ?string $shopkey;

    protected bool $active;

    protected bool $exportZeroPricedProducts = false;

    protected AdvancedPricing $advancedPricing = AdvancedPricing::OFF;

    protected MainVariant $mainVariant = MainVariant::SHOPWARE_DEFAULT;

    /** @var string[] */
    protected array $crossSellingCategories = [];

    protected IntegrationType $integrationType = IntegrationType::DI;

    protected bool $useXmlVariants = false;

    public static function createFromArray(array $values): self
    {
        $config = new self();

        foreach ($values as $key => $value) {
            if (property_exists($config, $key) && !is_null($value)) {
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

    public function getAdvancedPricing(): AdvancedPricing
    {
        return $this->advancedPricing;
    }

    public function getMainVariant(): MainVariant
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

    public function isIntegrationTypesDirectIntegration(): bool
    {
        return $this->integrationType === IntegrationType::DI;
    }

    public function useXmlVariants(): bool
    {
        return $this->useXmlVariants;
    }
}
