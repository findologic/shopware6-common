<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Services;

use FINDOLOGIC\Shopware6Common\Export\Services\ProductUrlService;
use FINDOLOGIC\Shopware6Common\Tests\CommonConstants;
use FINDOLOGIC\Shopware6Common\Tests\TestHelper\Helper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ServicesHelper;
use PHPUnit\Framework\TestCase;
use Vin\ShopwareSdk\Data\Entity\SeoUrl\SeoUrlCollection;
use Vin\ShopwareSdk\Data\Entity\SeoUrl\SeoUrlEntity;
use Vin\ShopwareSdk\Data\Uuid\Uuid;

class ProductUrlServiceTest extends TestCase
{
    use ProductHelper;
    use ServicesHelper;

    public ProductUrlService $productUrlService;

    public function setUp(): void
    {
        parent::setUp();

        $this->productUrlService = $this->getProductUrlService();
    }

    public function removeInvalidUrlsProvider(): array
    {
        return [
            'SeoPatInfo support slash, minus and big letters but not spaces' => [
                'seoUrlArray' => [
                    ['seoPathInfo' => '/correctSeoUrlOne'],
                    ['seoPathInfo' => '/correct-seo-url-two'],
                    ['seoPathInfo' => '/correctSeoUrl/three'],
                    ['seoPathInfo' => '/correct-seo-url/four'],
                    ['seoPathInfo' => 'correctSeoUrlFive'],
                    ['seoPathInfo' => 'correct-seo-url-six'],
                    ['seoPathInfo' => 'correctSeoUrl/seven'],
                    ['seoPathInfo' => 'correct-seo-url/eight'],
                    ['seoPathInfo' => 'failed seo url with spaces one'],
                    ['seoPathInfo' => '/failed seo url with spaces two'],
                ],
                'expectedUrlCount' => 8,
            ],
        ];
    }

    /**
     * @dataProvider removeInvalidUrlsProvider
     */
    public function testRemoveInvalidUrls(array $seoUrlArray, int $expectedUrlCount): void
    {
        $seoUrlCollection = new SeoUrlCollection();
        foreach ($seoUrlArray as $seoUrl) {
            $seoUrlEntity = new SeoUrlEntity();
            $seoUrlEntity->id = Uuid::randomHex();
            $seoUrlEntity->seoPathInfo = $seoUrl['seoPathInfo'];

            $seoUrlCollection->add($seoUrlEntity);
        }

        $this->assertSame(
            $expectedUrlCount,
            Helper::callMethod(
                $this->productUrlService,
                'removeInvalidUrls',
                [$seoUrlCollection],
            )->count(),
        );
    }

    public function productSeoPathProvider(): array
    {
        return [
            'Has valid url, canonical and not deleted' => [
                'seoUrlArray' => [
                    ['seoPathInfo' => 'invalid url one', 'isCanonical' => false, 'isDeleted' => false],
                    ['seoPathInfo' => '/validUrlOne', 'isCanonical' => true, 'isDeleted' => false],
                ],
                'expectedSeoUrl' => 'validUrlOne',
            ],
            'Has valid url not canonical and not deleted' => [
                'seoUrlArray' => [
                    ['seoPathInfo' => 'invalid url two', 'isCanonical' => false, 'isDeleted' => false],
                    ['seoPathInfo' => '/validUrlTwo', 'isCanonical' => false, 'isDeleted' => false],
                ],
                'expectedSeoUrl' => 'validUrlTwo',
            ],
            'Has valid and canonical url, but deleted' => [
                'seoUrlArray' => [
                    ['seoPathInfo' => 'invalid url five', 'isCanonical' => false, 'isDeleted' => false],
                    ['seoPathInfo' => '/validUrlThree', 'isCanonical' => true, 'isDeleted' => true],
                ],
                'expectedSeoUrl' => null,
            ],
            'Has valid and not canonical url and deleted' => [
                'seoUrlArray' => [
                    ['seoPathInfo' => 'invalid url five', 'isCanonical' => false, 'isDeleted' => false],
                    ['seoPathInfo' => '/validUrlFour', 'isCanonical' => false, 'isDeleted' => true],
                ],
                'expectedSeoUrl' => null,
            ],
            'No valid url, all not canonical' => [
                'seoUrlArray' => [
                    ['seoPathInfo' => 'invalid url three', 'isCanonical' => false, 'isDeleted' => false],
                    ['seoPathInfo' => 'invalid url four', 'isCanonical' => false, 'isDeleted' => false],
                ],
                'expectedSeoUrl' => null,
            ],
        ];
    }

    /**
     * @dataProvider productSeoPathProvider
     */
    public function testGetProductSeoPath(array $seoUrlArray, ?string $expectedSeoUrl): void
    {
        $seoUrlCollection = new SeoUrlCollection();

        foreach ($seoUrlArray as $seoPath) {
            $seoUrlEntity = new SeoUrlEntity();
            $seoUrlEntity->id = Uuid::randomHex();
            $seoUrlEntity->salesChannelId = CommonConstants::SALES_CHANNEL_ID;
            $seoUrlEntity->languageId = CommonConstants::LANGUAGE_ID;
            $seoUrlEntity->seoPathInfo = $seoPath['seoPathInfo'];
            $seoUrlEntity->isCanonical = $seoPath['isCanonical'];
            $seoUrlEntity->isDeleted = $seoPath['isDeleted'];

            $seoUrlCollection->add($seoUrlEntity);
        }

        $product = $this->createTestProduct();
        $product->seoUrls = $seoUrlCollection;

        $seoUrl = Helper::callMethod(
            $this->productUrlService,
            'getProductSeoPath',
            [$product],
        );

        $this->assertSame($expectedSeoUrl, $seoUrl);
    }
}
