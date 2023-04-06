<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Services;

use FINDOLOGIC\Shopware6Common\Export\ExportContext;

class DebugUrlBuilderService
{
    private const PATH_STRUCTURE = '%s/%s%s?shopkey=%s&productId=%s';
    private const DEBUG_PATH = '/debug';

    public function __construct(
        private readonly ExportContext $exportContext,
        private readonly string $shopkey,
        private readonly string $basePath,
    ) {
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
            $this->getShopDomain(),
            $this->basePath,
            $path,
            $this->shopkey,
            $productId,
        );
    }

    private function getShopDomain(): string
    {
        if ($domains = $this->exportContext->getSalesChannel()->domains) {
            return $domains->first()->url;
        }

        return '';
    }
}
