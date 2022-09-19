<?php

namespace FINDOLOGIC\Shopware6Common\Export\Utils;

use Vin\ShopwareSdk\Data\Defaults;
use Vin\ShopwareSdk\Data\Entity\SalesChannelDomain\SalesChannelDomainCollection;
use Vin\ShopwareSdk\Data\Entity\SalesChannelDomain\SalesChannelDomainEntity;

class Utils
{
    public static function cleanString(?string $string): ?string
    {
        if (!$string) {
            return null;
        }
        $string = str_replace('\\', '', addslashes(strip_tags($string)));
        $string = str_replace(["\n", "\r", "\t"], ' ', $string);
        // Remove unprintable characters since they would cause an invalid XML.
        $string = self::removeControlCharacters($string);

        return trim($string);
    }

    public static function removeControlCharacters(?string $string): string
    {
        if ($string === null) {
            return '';
        }

        $result = preg_replace('/[\x{0000}-\x{001F}]|[\x{007F}]|[\x{0080}-\x{009F}]/u', '', $string);

        return trim($result) ?? trim($string);
    }

    public static function removeSpecialChars(?string $string): string
    {
        if ($string === null) {
            return '';
        }

        return preg_replace('/[^äöüA-Za-z0-9:_-]/u', '', $string);
    }

    public static function multiByteRawUrlEncode(string $string): string
    {
        $encoded = '';
        $length = mb_strlen($string);
        for ($i = 0; $i < $length; ++$i) {
            $encoded .= '%' . wordwrap(bin2hex(mb_substr($string, $i, 1)), 2, '%', true);
        }

        return $encoded;
    }

    public static function buildUrl(array $parsedUrl): string
    {
        return sprintf(
            '%s%s%s%s%s%s%s%s%s%s',
            isset($parsedUrl['scheme']) ? "{$parsedUrl['scheme']}:" : '',
            (isset($parsedUrl['user']) || isset($parsedUrl['host'])) ? '//' : '',
            isset($parsedUrl['user']) ? "{$parsedUrl['user']}" : '',
            isset($parsedUrl['pass']) ? ":{$parsedUrl['pass']}" : '',
            isset($parsedUrl['user']) ? '@' : '',
            isset($parsedUrl['host']) ? "{$parsedUrl['host']}" : '',
            isset($parsedUrl['port']) ? ":{$parsedUrl['port']}" : '',
            isset($parsedUrl['path']) ? "{$parsedUrl['path']}" : '',
            isset($parsedUrl['query']) ? "?{$parsedUrl['query']}" : '',
            isset($parsedUrl['fragment']) ? "#{$parsedUrl['fragment']}" : ''
        );
    }

    public static function isEmpty($value): bool
    {
        if (is_numeric($value) || is_object($value) || is_bool($value)) {
            return false;
        }

        if (is_array($value) && empty(array_filter($value))) {
            return true;
        }

        if (is_string($value) && empty(trim($value))) {
            return true;
        }

        if (empty($value)) {
            return true;
        }

        return false;
    }

    public static function buildCategoryPath(array $categoryBreadCrumb, array $rootCategoryBreadcrumbs): string
    {
        $breadcrumb = static::getCategoryBreadcrumb($categoryBreadCrumb, $rootCategoryBreadcrumbs);

        // Build category path and trim all entries.
        return implode('_', array_map('trim', $breadcrumb));
    }

    /**
     * Builds the category path by removing the path of the parent (root) category of the sales channel.
     * Since Findologic does not care about any root categories, we need to get the difference between the
     * normal category path and the root category.
     *
     * @return string[]
     */
    private static function getCategoryBreadcrumb(array $categoryBreadcrumb, array $rootCategoryBreadcrumbs): array
    {
        $path = array_splice($categoryBreadcrumb, count($rootCategoryBreadcrumbs));

        return array_values($path);
    }

    /**
     * Takes a given domain collection and only returns domains which are not associated to a headless sales
     * channel, as these do not have real URLs, but only contain placeholder information.
     */
    public static function filterSalesChannelDomainsWithoutHeadlessDomain(
        SalesChannelDomainCollection $original
    ): SalesChannelDomainCollection {
        /** @var SalesChannelDomainCollection $new */
        $new = $original->filter(function (SalesChannelDomainEntity $domainEntity) {
            return !str_starts_with($domainEntity->url, 'default.headless');
        });
        return $new;
    }

    /**
     * Flattens a given array. This method is similar to the JavaScript method "Array.prototype.flat()".
     * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/flat
     *
     * @param array $array
     *
     * @return array
     */
    public static function flat(array $array): array
    {
        $flattened = [];
        array_walk_recursive($array, static function ($a) use (&$flattened) {
            $flattened[] = $a;
        });

        return $flattened;
    }

    public static function flattenWithUnique(array $array): array
    {
        return array_unique(static::flat($array), SORT_REGULAR);
    }

    /**
     * @return ?array<string, mixed>
     */
    public static function getCurrencyPrice(array $prices, string $currencyId): ?array
    {
        $filteredProductPrice = array_filter($prices, static function ($price) use ($currencyId) {
            return $price['currencyId'] === $currencyId;
        });

        return count($filteredProductPrice) ? $filteredProductPrice[0] : null;
    }
}
