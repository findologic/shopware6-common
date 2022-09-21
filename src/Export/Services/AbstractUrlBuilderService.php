<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Services;

use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Symfony\Component\Routing\RouterInterface;
use Vin\ShopwareSdk\Data\Collection;
use Vin\ShopwareSdk\Data\Entity\Category\CategoryEntity;
use Vin\ShopwareSdk\Data\Entity\EntityCollection;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

use Vin\ShopwareSdk\Data\Entity\SalesChannelDomain\SalesChannelDomainCollection;
use Vin\ShopwareSdk\Data\Entity\SeoUrl\SeoUrlCollection;
use Vin\ShopwareSdk\Data\Entity\SeoUrl\SeoUrlEntity;
use const PHP_URL_PATH;

abstract class AbstractUrlBuilderService
{
    protected RouterInterface $router;

    protected ExportContext $exportContext;

    public function __construct(
        RouterInterface $router,
        ExportContext $exportContext
    ) {
        $this->router = $router;
        $this->exportContext = $exportContext;
    }

    /**
     * Builds the URL of the given product for the currently used language. Automatically fallbacks to
     * generating a URL via the router in case the product does not have a SEO URL configured.
     * E.g.
     * * http://localhost:8000/Lightweight-Paper-Prior-IT/7562a1140f7f4abd8c6a4a4b6d050b77
     * * https://your-shop.com/detail/032c79962b3f4fb4bd1e9117005b42c1
     * * https://your-shop.com/de/Cooles-Produkt/c0421a8d8af840ecad60971ec5280476
     */
    public function buildProductUrl(ProductEntity $product): string
    {
        $seoPath = $this->getProductSeoPath($product);
        if (!$seoPath) {
            return $this->getFallbackUrl($product);
        }

        $domain = $this->getSalesChannelDomain();
        if (!$domain) {
            return $this->getFallbackUrl($product);
        }

        return $this->buildSeoUrl($domain, $seoPath);
    }

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
     * Gets the domain of the sales channel for the currently used language. Suffixed slashes are removed.
     * E.g.
     * * http://localhost:8000
     * * https://your-domain.com
     * * https://your-domain.com/de
     */
    protected function getSalesChannelDomain(): ?string
    {
        $allDomains = $this->exportContext->getSalesChannel()->domains;
        $allDomains = Utils::filterSalesChannelDomainsWithoutHeadlessDomain($allDomains);
        /** @var SalesChannelDomainCollection $domains */
        $domains = $this->getTranslatedEntities($allDomains);

        if (!$domains || !$domains->first()) {
            return null;
        }

        return rtrim($domains->first()->url, '/');
    }

    /**
     * Gets the SEO path of the given product for the currently used language. Prefixed slashes are removed.
     * E.g.
     * * Lightweight-Paper-Prior-IT/7562a1140f7f4abd8c6a4a4b6d050b77
     * * Sony-Alpha-7-III-Sigma-AF-24-70mm-1-2-8-DG-DN-ART/145055000510
     */
    protected function getProductSeoPath(ProductEntity $product): ?string
    {
        if (!$product->seoUrls) {
            return null;
        }

        $allSeoUrls = $this->removeInvalidUrls($product->seoUrls);
        if (!$allSeoUrls->count()) {
            return null;
        }

        $applicableSeoUrls = $this->getApplicableSeoUrls($allSeoUrls);

        /** @var SeoUrlCollection $seoUrls */
        $seoUrls = $this->getTranslatedEntities($applicableSeoUrls);
        if (!$seoUrls || !$seoUrls->first()) {
            return null;
        }

        /** @var SeoUrlEntity $canonicalSeoUrl */
        $canonicalSeoUrl = $seoUrls->filter(function (SeoUrlEntity $entity) {
            return $entity->isCanonical;
        })->first();
        $seoUrl = $canonicalSeoUrl ?? $seoUrls->first();

        return ltrim($seoUrl->seoPathInfo, '/');
    }

    /**
     * Filters the given collection to only return entities with valid url.
     */
    protected function removeInvalidUrls(SeoUrlCollection $seoUrls): SeoUrlCollection
    {
        /** @var SeoUrlCollection $seoUrlCollection */
        $seoUrlCollection = $seoUrls->filter(function (SeoUrlEntity $seoUrl) {
            return filter_var(
                sprintf('https://dummy.com%s"', $seoUrl->seoPathInfo),
                FILTER_VALIDATE_URL
            );
        });

        return $seoUrlCollection;
    }

    /**
     * Filters the given collection to only return entities for the current language.
     */
    protected function getTranslatedEntities(?EntityCollection $collection): ?Collection
    {
        if (!$collection) {
            return null;
        }

        $translatedEntities = $collection->filterByProperty(
            'languageId',
            $this->exportContext->getLanguageId()
        );

        if ($translatedEntities->count() === 0) {
            return null;
        }

        return $translatedEntities;
    }

    /**
     * Filters out non-applicable SEO URLs based on the current context.
     */
    protected function getApplicableSeoUrls(SeoUrlCollection $collection): SeoUrlCollection
    {
        $salesChannelId = $this->exportContext->getSalesChannelId();

        /** @var SeoUrlCollection $seoUrlCollection */
        $seoUrlCollection = $collection->filter(static function (SeoUrlEntity $seoUrl) use ($salesChannelId) {
            return $seoUrl->salesChannelId === $salesChannelId && !$seoUrl->isDeleted;
        });
        return $seoUrlCollection;
    }

    protected function getFallbackUrl(ProductEntity $product): string
    {
        return $this->router->generate(
            'frontend.detail.page',
            ['productId' => $product->id],
            RouterInterface::ABSOLUTE_URL
        );
    }

    protected function buildSeoUrl(string $domain, string $seoPath): string
    {
        return sprintf('%s/%s', $domain, $seoPath);
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

    protected function buildCategoryUrls(CategoryEntity $category): array
    {
        $categoryUrls = $this->buildCategorySeoUrl($category);
        $categoryUrls[] = sprintf(
            '/%s',
            ltrim(
                $this->router->generate(
                    'frontend.navigation.page',
                    ['navigationId' => $category->id],
                    RouterInterface::ABSOLUTE_PATH
                ),
                '/'
            )
        );

        return $categoryUrls;
    }

    abstract protected function fetchParentsFromCategoryPath(string $categoryPath): ?array;
}
