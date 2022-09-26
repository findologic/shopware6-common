<?php

namespace FINDOLOGIC\Shopware6Common\Export\Handlers;

abstract class AbstractHeaderHandler
{
    public const HEADER_SHOPWARE = 'x-findologic-platform';
    public const HEADER_PLUGIN = 'x-findologic-plugin';
    public const HEADER_EXTENSION = 'x-findologic-extension-plugin';
    public const HEADER_CONTENT_TYPE = 'content-type';

    public const CONTENT_TYPE_XML = 'text/xml';
    public const CONTENT_TYPE_JSON = 'application/json';

    protected const SHOPWARE_VERSION = 'Shopware/%s';
    protected const PLUGIN_VERSION = 'Plugin-Shopware-6/%s';
    protected const EXTENSION_PLUGIN_VERSION = 'Plugin-Shopware-6-Extension/%s';
    protected const DEFAULT_VERSION_TEXT = 'none';

    protected string $shopwareVersion;

    protected string $pluginVersion;

    protected string $extensionPluginVersion;

    public function __construct(string $shopwareVersion) {
        $this->shopwareVersion = sprintf(self::SHOPWARE_VERSION, $shopwareVersion);

        $this->pluginVersion = $this->fetchPluginVersion();
        $this->extensionPluginVersion = $this->fetchExtensionPluginVersion();
    }

    /**
     * @param array<string, string> $overrides
     * @return array<string, string>
     */
    public function getHeaders(array $overrides = []): array
    {
        $headers = [];
        $headers[self::HEADER_CONTENT_TYPE] = self::CONTENT_TYPE_XML;
        $headers[self::HEADER_SHOPWARE] = $this->shopwareVersion;
        $headers[self::HEADER_PLUGIN] = $this->pluginVersion;
        $headers[self::HEADER_EXTENSION] = $this->extensionPluginVersion;

        return array_merge($headers, $overrides);
    }

    abstract protected function fetchPluginVersion(): string;

    abstract protected function fetchExtensionPluginVersion(): string;
}
