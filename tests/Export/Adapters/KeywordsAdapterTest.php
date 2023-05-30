<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Export\Data\Keyword;
use FINDOLOGIC\Shopware6Common\Export\Adapters\KeywordsAdapter;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\TestCase;
use Vin\ShopwareSdk\Data\Entity\ProductSearchKeyword\ProductSearchKeywordCollection;
use Vin\ShopwareSdk\Data\Entity\ProductSearchKeyword\ProductSearchKeywordEntity;
use Vin\ShopwareSdk\Data\Uuid\Uuid;

class KeywordsAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;

    public function testKeywordsContainsTheKeywordsOfTheProductForSelectedLanguage(): void
    {
        $expectedKeywords = [new Keyword('keyword1'), new Keyword('keyword2')];
        $keywordsEntities = [
            $this->getKeywordEntity('keyword1', '2fbb5fe2e29a4d70aa5854ce7ce3e20b'),
            $this->getKeywordEntity('keyword2', '2fbb5fe2e29a4d70aa5854ce7ce3e20b'),
            $this->getKeywordEntity('keyword3', '3fbb5fe2e29a12345asddfa22334fgfs'),
        ];

        $productSearchKeywordCollection = new ProductSearchKeywordCollection($keywordsEntities);

        $product = $this->createTestProduct();
        $product->searchKeywords = $productSearchKeywordCollection;

        $keywordsAdapter = new KeywordsAdapter($this->getExportContext());

        $keywords = $keywordsAdapter->adapt($product);

        $this->assertCount(2, $keywords);
        $this->assertEquals($expectedKeywords, $keywords);
    }

    private function getKeywordEntity(string $keyword, string $languageId): ProductSearchKeywordEntity
    {
        $productSearchKeywordEntity = new ProductSearchKeywordEntity();
        $productSearchKeywordEntity->id = Uuid::randomHex();
        $productSearchKeywordEntity->keyword = $keyword;
        $productSearchKeywordEntity->languageId = $languageId;

        return $productSearchKeywordEntity;
    }
}
