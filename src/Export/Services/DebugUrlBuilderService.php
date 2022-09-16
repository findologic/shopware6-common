<?php

namespace FINDOLOGIC\Shopware6Common\Export\Services;

use FINDOLOGIC\Shopware6Common\Export\ExportContext;

class DebugUrlBuilderService
{
    private const PATH_STRUCTURE = '%s/%s%s?shopkey=%s&productId=%s';
    private const DEBUG_PATH = '/debug';

    private ExportContext $exportContext;

    private string $shopkey;

    private string $basePath;

    public function __construct(ExportContext $exportContext, string $shopkey, string $basePath)
    {
        $this->exportContext = $exportContext;
        $this->shopkey = $shopkey;
        $this->basePath = $basePath;
    }

    public function buildExportUrl(string $productId): string
    {
        return $this->buildUrlByPath('', $productId);
    }

    public function buildDebugUrl(string $productId): string
    {
        return $this->buildUrlByPath(self::DEBUG_PATH, $productId);
    }

    private function buildUrlByPath(string $path, string $productId): string
    {
        return sprintf(
            self::PATH_STRUCTURE,
            $this->exportContext->getShopDomain(),
            $this->basePath,
            $path,
            $this->shopkey,
            $productId
        );
    }
}
