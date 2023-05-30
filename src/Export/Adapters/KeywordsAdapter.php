<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Adapters;

use FINDOLOGIC\Export\Data\Keyword;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;

class KeywordsAdapter
{
    protected ExportContext $exportContext;

    public function __construct(ExportContext $exportContext)
    {
        $this->exportContext = $exportContext;
    }

    /**
     * @return Keyword[]
     */
    public function adapt(ProductEntity $product): array
    {
        if (!$keywords = $product->searchKeywords) {
            return [];
        }

        return $this->getKeywords($keywords->getElements(), $this->getBlacklistedKeywords($product));
    }

    /**
     * @return Keyword[]
     */
    protected function getKeywords(
        ?array $keywordsCollection,
        array $blackListedKeywords
    ): array {
        $keywords = [];

        if (!$keywordsCollection || count($keywordsCollection) <= 0) {
            return [];
        }

        foreach ($keywordsCollection as $keyword) {
            $keywordValue = $keyword->keyword;
            if (Utils::isEmpty($keywordValue)) {
                continue;
            }

            if ($isBlackListedKeyword = in_array($keywordValue, $blackListedKeywords)) {
                continue;
            }

            if ($this->exportContext->getLanguageId() != $keyword->languageId) {
                continue;
            }

            $keywords[] = new Keyword($keywordValue);
        }

        return $keywords;
    }

    protected function getBlacklistedKeywords(ProductEntity $product): array
    {
        $blackListedKeywords = [
            $product->productNumber,
        ];

        if ($manufacturer = $product->manufacturer) {
            $blackListedKeywords[] = $manufacturer->getTranslation('name');
        }

        return $blackListedKeywords;
    }
}
