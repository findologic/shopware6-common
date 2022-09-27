<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Validation;

use Symfony\Component\Validator\Constraints as Assert;

class PageExportConfiguration extends ExportConfigurationBase
{
    public const DEFAULT_PAGE_PARAM = 1;

    /**
     * @Assert\NotBlank
     * @Assert\Type(
     *     type="integer",
     *     message="The value {{ value }} is not a valid {{ type }}."
     * )
     * @Assert\GreaterThanOrEqual(1)
     */
    protected int $page;

    public function __construct(string $shopkey, int $page, int $count, ?string $productId = null)
    {
        parent::__construct($shopkey, $count, $productId);

        $this->page = $page;
    }

    public function getPage(): int
    {
        return $this->page;
    }
}
