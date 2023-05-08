<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Traits;

use FINDOLOGIC\Export\Data\Image;
use FINDOLOGIC\Export\Enums\ImageType;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Vin\ShopwareSdk\Data\Entity\Media\MediaCollection;
use Vin\ShopwareSdk\Data\Entity\MediaThumbnail\MediaThumbnailCollection;
use Vin\ShopwareSdk\Data\Entity\MediaThumbnail\MediaThumbnailEntity;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;
use Vin\ShopwareSdk\Data\Entity\ProductMedia\ProductMediaEntity;

trait ImageHelper
{
    /**
     * @return Image[]
     */
    public function getImages(ProductEntity $productEntity): array
    {
        $images = [];
        if (!$productEntity->media || !$productEntity->media->count()) {
            $fallbackImage = sprintf(
                '%s/%s',
                getenv('APP_URL') ? getenv('APP_URL') : 'http://localhost',
                'bundles/storefront/assets/icon/default/placeholder.svg',
            );

            $images[] = new Image($fallbackImage);
            $images[] = new Image($fallbackImage, ImageType::THUMBNAIL);

            return $images;
        }

        $mediaCollection = $productEntity->media;
        $media = new MediaCollection(
            $mediaCollection->fmap(function (ProductMediaEntity $productMedia) {
                return $productMedia->media;
            }),
        );
        $thumbnails = $media->first()->thumbnails;

        $filteredThumbnails = $this->sortAndFilterThumbnailsByWidth($thumbnails);
        $firstThumbnail = $filteredThumbnails->first();

        $image = $firstThumbnail ?? $media->first();
        $url = $this->getEncodedUrl($image->url);
        $images[] = new Image($url);

        $imageIds = [];
        foreach ($thumbnails as $thumbnail) {
            if (in_array($thumbnail->mediaId, $imageIds)) {
                continue;
            }

            $url = $this->getEncodedUrl($thumbnail->url);
            $images[] = new Image($url, ImageType::THUMBNAIL);
            $imageIds[] = $thumbnail->mediaId;
        }

        return $images;
    }

    private function sortAndFilterThumbnailsByWidth(MediaThumbnailCollection $thumbnails): MediaThumbnailCollection
    {
        /** @var MediaThumbnailCollection $filteredThumbnails */
        $filteredThumbnails = $thumbnails->filter(static function (MediaThumbnailEntity $thumbnail) {
            return $thumbnail->width >= 600;
        });

        $filteredThumbnails->sort(function (MediaThumbnailEntity $a, MediaThumbnailEntity $b) {
            return $a->width <=> $b->width;
        });

        return $filteredThumbnails;
    }

    protected function getEncodedUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        $urlPath = explode('/', $parsedUrl['path']);
        $encodedPath = array_map('\FINDOLOGIC\Shopware6Common\Export\Utils\Utils::multiByteRawUrlEncode', $urlPath);
        $parsedUrl['path'] = implode('/', $encodedPath);

        return Utils::buildUrl($parsedUrl);
    }
}
