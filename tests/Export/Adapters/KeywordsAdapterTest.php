<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Export\Data\Keyword;
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

    public function testKeywordsContainsTheKeywordsOfTheProduct(): void
    {
        $expectedKeywords = [new Keyword('keyword1'), new Keyword('keyword2')];
        $keywordsEntities = [$this->getKeywordEntity('keyword1'), $this->getKeywordEntity('keyword2')];
        $productSearchKeywordCollection = new ProductSearchKeywordCollection($keywordsEntities);

        $product = $this->createTestProduct();
        $product->searchKeywords = $productSearchKeywordCollection;

        $keywords = $this->getKeywordsAdapter()->adapt($product);

        $this->assertCount(2, $keywords);
        $this->assertEquals($expectedKeywords, $keywords);
    }

    private function getKeywordEntity(string $keyword): ProductSearchKeywordEntity
    {
        $productSearchKeywordEntity = new ProductSearchKeywordEntity();
        $productSearchKeywordEntity->id = Uuid::randomHex();
        $productSearchKeywordEntity->keyword = $keyword;

        return $productSearchKeywordEntity;
    }
}
