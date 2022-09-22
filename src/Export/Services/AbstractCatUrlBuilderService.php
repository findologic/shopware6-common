<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Services;

use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Symfony\Component\Routing\RouterInterface;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryCollection;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryEntity;
use Vin\ShopwareSdk\Data\Entity\SalesChannelDomain\SalesChannelDomainCollection;
use Vin\ShopwareSdk\Data\Entity\SeoUrl\SeoUrlCollection;
use Vin\ShopwareSdk\Data\Entity\SeoUrl\SeoUrlEntity;
use const PHP_URL_PATH;

abstract class AbstractCatUrlBuilderService extends UrlBuilderService
{
    /**
     * Builds `cat_url`s for Direct Integrations. Based on the given category, all
     * paths excluding the root category are generated.
     * E.g. Category Structure "Something > Root Category > Men > Shirts > T-Shirts" exports
     * * /Men/Shirts/T-Shirts/
     * * /Men/Shirts/
     * * /Men/
     * * /navigation/4e43b925d5ec43339d2b3414a91151ab
     * In case there is a language prefix assigned to the Sales Channel, this would also be included.
     * E.g.
     * * /de/Men/Shirts/T-Shirts/
     * * /de/navigation/4e43b925d5ec43339d2b3414a91151ab
     *
     * @return string[]
     */
    public function getCategoryUrls(CategoryEntity $category): array
    {
        $categories = $this->getParentCategories($category);
        $categoryUrls = [];

        $categoryUrls[] = $this->buildCategoryUrls($category);
        foreach ($categories as $categoryEntity) {
            $categoryUrls[] = $this->buildCategoryUrls($categoryEntity);
        }

        return $categoryUrls;
    }

    /**
     * Returns all parent categories of the given category.
     * The main navigation category (aka. root category) won't be added to the array.
     *
     * @return CategoryEntity[]
     */
    public function getParentCategories(CategoryEntity $category): array
    {
        $parentCategories = $this->fetchParentsFromCategoryPath($category->path);
        $categories = [];

        /** @var CategoryEntity $categoryInPath */
        foreach ($parentCategories as $categoryInPath) {
            if ($categoryInPath->id === $this->exportContext->getNavigationCategoryId()) {
                continue;
            }

            $categories[] = $categoryInPath;
        }

        return $categories;
    }

    protected function fetchCategorySeoUrls(CategoryEntity $categoryEntity): SeoUrlCollection
    {
        $seoUrls = new SeoUrlCollection();
        if ($categoryEntity->seoUrls && $categoryEntity->seoUrls->count() > 0) {
            $salesChannelId = $this->exportContext->getSalesChannelId();
            foreach ($categoryEntity->seoUrls->getElements() as $seoUrlEntity) {
                $seoUrlSalesChannelId = $seoUrlEntity->salesChannelId;
                if ($seoUrlSalesChannelId === $salesChannelId || $seoUrlSalesChannelId === null) {
                    $seoUrls->add($seoUrlEntity);
                }
            }
        }

        return $seoUrls;
    }

    /**
     * Returns all SEO paths for the given category.
     *
     * @return string[]
     */
    protected function buildCategorySeoUrl(CategoryEntity $categoryEntity): array
    {
        $salesChannelId = $this->exportContext->getSalesChannelId();
        $allSeoUrls = $this->fetchCategorySeoUrls($categoryEntity);

        /** @var SeoUrlCollection $salesChannelSeoUrls */
        $salesChannelSeoUrls = $allSeoUrls->filter(static function (SeoUrlEntity $seoUrl) use ($salesChannelId) {
            return $seoUrl->salesChannelId === $salesChannelId;
        });

        if ($salesChannelSeoUrls->count() === 0) {
            return [];
        }

        $seoUrls = [];
        foreach ($salesChannelSeoUrls as $seoUrl) {
            $pathInfo = $seoUrl->seoPathInfo;
            if (Utils::isEmpty($pathInfo)) {
                continue;
            }

            $seoUrls[] = $this->getCatUrlPrefix() . sprintf('/%s', ltrim($pathInfo, '/'));
        }

        return $seoUrls;
    }

    protected function getCatUrlPrefix(): string
    {
        $url = $this->getTranslatedDomainBaseUrl();
        if (!$url) {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return '';
        }

        return rtrim($path, '/');
    }

    protected function getTranslatedDomainBaseUrl(): ?string
    {
        $salesChannel = $this->exportContext->getSalesChannel();
        $domainCollection = Utils::filterSalesChannelDomainsWithoutHeadlessDomain($salesChannel->domains);

        /** @var SalesChannelDomainCollection $domainEntities */
        $domainEntities = $this->getTranslatedEntities($domainCollection);

        return $domainEntities && $domainEntities->first() ? rtrim($domainEntities->first()->url, '/') : null;
    }

    /**
     * @return string[]
     */
    abstract protected function buildCategoryUrls(CategoryEntity $category): array;

    abstract protected function fetchParentsFromCategoryPath(string $categoryPath): CategoryCollection;
}
