<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Services;

use FINDOLOGIC\Export\Data\Image;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Vin\ShopwareSdk\Data\Entity\Entity;
use Vin\ShopwareSdk\Data\Entity\Media\MediaEntity;
use Vin\ShopwareSdk\Data\Entity\MediaThumbnail\MediaThumbnailCollection;
use Vin\ShopwareSdk\Data\Entity\MediaThumbnail\MediaThumbnailEntity;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;
use Vin\ShopwareSdk\Data\Entity\ProductMedia\ProductMediaCollection;
use Vin\ShopwareSdk\Data\Entity\ProductMedia\ProductMediaEntity;

class ProductImageService
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @return Image[]
     */
    public function getProductImages(ProductEntity $product): array
    {
        $productHasImages = $this->productHasImages($product);

        if (!$productHasImages) {
            return $this->getFallbackImages();
        }

        $images = $this->getSortedProductImages($product);

        return $this->buildImageUrls($images);
    }

    public function productHasImages(ProductEntity $product): bool
    {
        return $product->media && $product->media->count() > 0;
    }

    protected function buildFallbackImage(RequestContext $requestContext): string
    {
        $schemaAuthority = $requestContext->getScheme() . '://' . $requestContext->getHost();
        if ($requestContext->getHttpPort() !== 80) {
            $schemaAuthority .= ':' . $requestContext->getHttpPort();
        } elseif ($requestContext->getHttpsPort() !== 443) {
            $schemaAuthority .= ':' . $requestContext->getHttpsPort();
        }

        return sprintf(
            '%s/%s',
            $schemaAuthority,
            'bundles/storefront/assets/icon/default/placeholder.svg',
        );
    }

    protected function getSortedProductImages(ProductEntity $product): ProductMediaCollection
    {
        /** @var ProductMediaCollection $images */
        $images = $product->media;
        $coverImageId = $product->coverId;
        $filteredCoverImage = $images->filterByProperty('id', $coverImageId);

        if (!$filteredCoverImage->count() || $images->count() === 1) {
            return $images;
        }

        $images->remove($coverImageId);
        $images->insert(0, $filteredCoverImage->first());

        return $images;
    }

    /**
     * @param MediaThumbnailEntity|MediaEntity $mediaEntity
     */
    protected function buildImage(Entity $mediaEntity, string $type = Image::TYPE_DEFAULT): Image
    {
        $encodedUrl = $this->getEncodedUrl($mediaEntity->url);

        return new Image($encodedUrl, $type);
    }

    /**
     * Takes invalid URLs that contain special characters such as umlauts, or special UTF-8 characters and
     * encodes them.
     */
    protected function getEncodedUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        $urlPath = explode('/', $parsedUrl['path']);
        $encodedPath = array_map('\FINDOLOGIC\Shopware6Common\Export\Utils\Utils::multiByteRawUrlEncode', $urlPath);
        $parsedUrl['path'] = implode('/', $encodedPath);

        return Utils::buildUrl($parsedUrl);
    }

    protected function sortAndFilterThumbnailsByWidth(MediaThumbnailCollection $thumbnails): MediaThumbnailCollection
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

    /**
     * Go through all given thumbnails and only add one thumbnail image. This avoids exporting thumbnails in
     * all various sizes.
     */
    protected function addThumbnailImages(array &$images, MediaThumbnailCollection $thumbnails): void
    {
        $imageIds = [];
        /** @var MediaThumbnailEntity $thumbnailEntity */
        foreach ($thumbnails as $thumbnailEntity) {
            if (in_array($thumbnailEntity->mediaId, $imageIds)) {
                continue;
            }

            $images[] = $this->buildImage($thumbnailEntity, Image::TYPE_THUMBNAIL);
            $imageIds[] = $thumbnailEntity->mediaId;
        }
    }

    protected function buildImageUrls(ProductMediaCollection $collection): array
    {
        $images = [];
        /** @var ProductMediaEntity $productMedia */
        foreach ($collection as $productMedia) {
            $media = $productMedia->media;

            if (!$this->hasMediaUrl($media)) {
                continue;
            }

            if (!$this->hasThumbnails($media)) {
                $images[] = $this->buildImage($media);

                continue;
            }

            $filteredThumbnails = $this->sortAndFilterThumbnailsByWidth($media->thumbnails);
            // Use the thumbnail as the main image if available, otherwise fallback to the directly assigned image.
            $image = $filteredThumbnails->first() ?? $media;
            if ($image) {
                $images[] = $this->buildImage($image);
            }

            $this->addThumbnailImages($images, $filteredThumbnails);
        }

        return $images;
    }

    /**
     * @return Image[]
     */
    protected function getFallbackImages(): array
    {
        $fallbackImage = $this->buildFallbackImage($this->router->getContext());

        return [
            new Image($fallbackImage),
            new Image($fallbackImage, Image::TYPE_THUMBNAIL),
        ];
    }

    protected function hasMediaUrl(MediaEntity $media): bool
    {
        return $media && $media->url;
    }

    protected function hasThumbnails(MediaEntity $media): bool
    {
        return $media->thumbnails && $media->thumbnails->count() > 0;
    }
}
