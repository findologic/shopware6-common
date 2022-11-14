<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Validation;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class ExportConfigurationBase
{
    public const DEFAULT_COUNT_PARAM = 20;

    /**
     * @Assert\NotBlank
     * @Assert\Regex(
     *     pattern="/^[A-F0-9]{32}$/",
     *     message="Invalid key provided."
     * )
     */
    protected string $shopkey;

    /**
     * @Assert\NotBlank
     * @Assert\Type(
     *     type="integer",
     *     message="The value {{ value }} is not a valid {{ type }}."
     * )
     * @Assert\GreaterThan(0)
     */
    protected int $count;

    /**
     * @Assert\Type("string")
     * @Assert\Uuid(
     *     strict=false
     * )
     */
    protected ?string $productId;

    public function __construct(string $shopkey, int $count, ?string $productId = null)
    {
        $this->shopkey = $shopkey;
        $this->count = $count;
        $this->productId = $productId;
    }

    public static function getInstance(Request $request): self
    {
        switch ($request->getPathInfo()) {
            case '/findologic':
            case '/findologic/dynamic-product-groups':
                return new OffsetExportConfiguration(
                    $request->query->get('shopkey', ''),
                    $request->query->getInt('start', OffsetExportConfiguration::DEFAULT_START_PARAM),
                    $request->query->getInt('count', self::DEFAULT_COUNT_PARAM),
                    $request->query->get('productId'),
                );
            case '/findologic/debug':
                return new OffsetDebugConfiguration(
                    $request->query->get('shopkey', ''),
                    $request->query->get('productId', ''),
                );
            case '/export':
            case '/export/dynamic-product-groups':
                return new PageExportConfiguration(
                    $request->query->get('shopkey', ''),
                    $request->query->getInt('page', PageExportConfiguration::DEFAULT_PAGE_PARAM),
                    $request->query->getInt('count', self::DEFAULT_COUNT_PARAM),
                    $request->query->get('productId'),
                );
            case '/export/debug':
                return new PageDebugConfiguration(
                    $request->query->get('shopkey', ''),
                    $request->query->get('productId', ''),
                );
            default:
                throw new InvalidArgumentException(
                    sprintf('Unknown export configuration type for path %d.', $request->getPathInfo()),
                );
        }
    }

    public function getShopkey(): string
    {
        return $this->shopkey;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }
}
