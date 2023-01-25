<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Services;

use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Vin\ShopwareSdk\Data\Entity\EntityCollection;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;
use Vin\ShopwareSdk\Data\Entity\SalesChannelDomain\SalesChannelDomainCollection;
use Vin\ShopwareSdk\Data\Entity\SeoUrl\SeoUrlCollection;
use Vin\ShopwareSdk\Data\Entity\SeoUrl\SeoUrlEntity;

class ProductUrlService extends UrlBuilderService
{
    /**
     * Builds the URL of the given product for the currently used language. Automatically fallbacks to
     * generating a URL via the router in case the product does not have a SEO URL configured.
     * E.g.
     * * http://localhost:8000/Lightweight-Paper-Prior-IT/7562a1140f7f4abd8c6a4a4b6d050b77
     * * https://your-shop.com/detail/032c79962b3f4fb4bd1e9117005b42c1
     * * https://your-shop.com/de/Cooles-Produkt/c0421a8d8af840ecad60971ec5280476.
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
     * Gets the SEO path of the given product for the currently used language. Prefixed slashes are removed.
     * E.g.
     * * Lightweight-Paper-Prior-IT/7562a1140f7f4abd8c6a4a4b6d050b77
     * * Sony-Alpha-7-III-Sigma-AF-24-70mm-1-2-8-DG-DN-ART/145055000510.
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
                sprintf('https://dummy.com%s/"', $seoUrl->seoPathInfo),
                FILTER_VALIDATE_URL
            );
        });

        return $seoUrlCollection;
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

    /**
     * Filters the given collection to only return entities for the current language.
     */
    protected function getTranslatedEntities(?EntityCollection $collection): ?EntityCollection
    {
        if (!$collection) {
            return null;
        }

        $translatedEntities = $collection->filterByProperty(
            'languageId',
            $this->exportContext->getLanguageId(),
        );

        if ($translatedEntities->count() === 0) {
            return null;
        }

        return $translatedEntities;
    }

    /**
     * Gets the domain of the sales channel for the currently used language. Suffixed slashes are removed.
     * E.g.
     * * http://localhost:8000
     * * https://your-domain.com
     * * https://your-domain.com/de.
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

    protected function getFallbackUrl(ProductEntity $product): string
    {
        try {
            return $this->router->generate(
                'frontend.detail.page',
                ['productId' => $product->id],
                RouterInterface::ABSOLUTE_URL,
            );
        } catch (RouteNotFoundException $e) {
            return sprintf('%s/detail/%s', $this->getSalesChannelDomain(), $product->id);
        }
    }

    protected function buildSeoUrl(string $domain, string $seoPath): string
    {
        return sprintf('%s/%s', $domain, $seoPath);
    }
}
