<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Adapters;

use FINDOLOGIC\Export\Data\Image;
use FINDOLOGIC\Export\Enums\ImageType;
use FINDOLOGIC\Shopware6Common\Export\Adapters\ImagesAdapter;
use FINDOLOGIC\Shopware6Common\Tests\Traits\AdapterHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ImageHelper;
use FINDOLOGIC\Shopware6Common\Tests\Traits\ProductHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vin\ShopwareSdk\Data\Entity\Media\MediaCollection;
use Vin\ShopwareSdk\Data\Entity\ProductMedia\ProductMediaEntity;

class ImagesAdapterTest extends TestCase
{
    use AdapterHelper;
    use ProductHelper;
    use ImageHelper;

    public ImagesAdapter $imagesAdapter;

    public function setUp(): void
    {
        $this->imagesAdapter = $this->getImagesAdapter();
    }

    public function testImagesContainsTheImagesOfTheProduct(): void
    {
        $product = $this->createTestProduct();
        $expectedImages = $this->getImages($product);

        $images = $this->imagesAdapter->adapt($product);

        $this->assertEquals($expectedImages, $images);
    }

    #[DataProvider('thumbnailProvider')]
    public function testCorrectThumbnailImageIsAdapted(array $thumbnails, array $expectedImages): void
    {
        $productData = [
            'cover' => ['media' => ['thumbnails' => $thumbnails]],
            'media' => [0 => ['media' => ['thumbnails' => $thumbnails]]],
        ];
        $productEntity = $this->createTestProduct($productData, true);

        $images = $this->imagesAdapter->adapt($productEntity);

        $actualImages = $this->urlDecodeImages($images);
        $this->assertCount(count($expectedImages), $actualImages);

        foreach ($expectedImages as $key => $expectedImage) {
            $this->assertStringContainsString($expectedImage['url'], $actualImages[$key]->getUrl());
            $this->assertSame($expectedImage['type'], $actualImages[$key]->getType());
        }
    }

    #[DataProvider('widthSizesProvider')]
    public function testImageThumbnailsAreFilteredAndSortedByWidth(array $widthSizes, array $expected): void
    {
        $thumbnails = $this->generateThumbnailData($widthSizes);
        $productData = [
            'cover' => ['media' => ['thumbnails' => $thumbnails]],
            'media' => [0 => ['media' => ['thumbnails' => $thumbnails]]],
        ];
        $productEntity = $this->createTestProduct($productData);
        $mediaCollection = $productEntity->media;
        $media = new MediaCollection(
            $mediaCollection->fmap(function (ProductMediaEntity $productMedia) {
                return $productMedia->media;
            }),
        );
        $thumbnailCollection = $media->first()->thumbnails;

        $width = [];
        $filteredThumbnails = $this->sortAndFilterThumbnailsByWidth($thumbnailCollection);
        foreach ($filteredThumbnails as $filteredThumbnail) {
            $width[] = $filteredThumbnail->width;
        }

        $this->assertSame($expected, $width);
    }

    /**
     * URL decodes images. This avoids having to debug the difference between URL encoded characters.
     *
     * @param Image[] $images
     *
     * @return Image[]
     */
    private function urlDecodeImages(array $images): array
    {
        return array_map(function (Image $image) {
            return new Image(urldecode($image->getUrl()), $image->getType(), $image->getUsergroup());
        }, $images);
    }

    private function generateThumbnailData(array $sizes): array
    {
        $thumbnails = [];
        foreach ($sizes as $width) {
            $thumbnails[] = [
                'width' => $width,
                'height' => 100,
                'highDpi' => false,
                'url' => 'https://via.placeholder.com/100',
            ];
        }

        return $thumbnails;
    }

    public static function thumbnailProvider(): array
    {
        return [
            '3 thumbnails 400x400, 600x600 and 1000x100, the image of width 600 is taken' => [
                'thumbnails' => [
                    [
                        'width' => 400,
                        'height' => 400,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/400x400',
                    ],
                    [
                        'width' => 600,
                        'height' => 600,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/600x600',
                    ],
                    [
                        'width' => 1000,
                        'height' => 100,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/1000x100',
                    ],
                ],
                'expectedImages' => [
                    [
                        'url' => '600x600',
                        'type' => ImageType::DEFAULT,
                    ],
                    [
                        'url' => '600x600',
                        'type' => ImageType::THUMBNAIL,
                    ],
                ],
            ],
            '2 thumbnails 800x800 and 2000x200, the image of width 800 is taken' => [
                'thumbnails' => [
                    [
                        'width' => 800,
                        'height' => 800,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/800x800',
                    ],
                    [
                        'width' => 2000,
                        'height' => 200,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/2000x200',
                    ],
                ],
                'expectedImages' => [
                    [
                        'url' => '800x800',
                        'type' => ImageType::DEFAULT,
                    ],
                    [
                        'url' => '800x800',
                        'type' => ImageType::THUMBNAIL,
                    ],
                ],
            ],
            '3 thumbnails 100x100, 200x200 and 400x400, the image directly assigned to the product is taken' => [
                'thumbnails' => [
                    [
                        'width' => 100,
                        'height' => 100,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/100x100',
                    ],
                    [
                        'width' => 200,
                        'height' => 200,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/200x200',
                    ],
                    [
                        'width' => 400,
                        'height' => 400,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/400x400',
                    ],
                ],
                'expectedImages' => [
                    [
                        'url' => 'findologic.png',
                        'type' => ImageType::DEFAULT,
                    ],
                ],
            ],
            '0 thumbnails, the automatically generated thumbnail is taken' => [
                'thumbnails' => [],
                'expectedImages' => [
                    [
                        'url' => '600x600',
                        'type' => ImageType::DEFAULT,
                    ],
                    [
                        'url' => '600x600',
                        'type' => ImageType::THUMBNAIL,
                    ],
                ],
            ],
            'Same thumbnail exists in various sizes will only export one size' => [
                'thumbnails' => [
                    [
                        'width' => 800,
                        'height' => 800,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/800x800',
                    ],
                    [
                        'width' => 1000,
                        'height' => 1000,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/1000x1000',
                    ],
                    [
                        'width' => 1200,
                        'height' => 1200,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/1200x1200',
                    ],
                    [
                        'width' => 1400,
                        'height' => 1400,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/1400x1400',
                    ],
                    [
                        'width' => 1600,
                        'height' => 1600,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/1600x1600',
                    ],
                    [
                        'width' => 1800,
                        'height' => 1800,
                        'highDpi' => false,
                        'url' => 'https://via.placeholder.com/1800x1800',
                    ],
                ],
                'expectedImages' => [
                    [
                        'url' => '800x800',
                        'type' => ImageType::DEFAULT,
                    ],
                    [
                        'url' => '800x800',
                        'type' => ImageType::THUMBNAIL,
                    ],
                ],
            ],
        ];
    }

    public static function widthSizesProvider(): array
    {
        return [
            'Max 600 width is provided' => [
                'widthSizes' => [100, 200, 300, 400, 500, 600],
                'expected' => [600],
            ],
            'Min 600 width is provided' => [
                'widthSizes' => [600, 800, 200, 500],
                'expected' => [600, 800],
            ],
            'Random width are provided' => [
                'widthSizes' => [800, 100, 650, 120, 2000, 1000],
                'expected' => [650, 800, 1000, 2000],
            ],
            'Less than 600 width is provided' => [
                'widthSizes' => [100, 200, 300, 500],
                'expected' => [],
            ],
        ];
    }
}
