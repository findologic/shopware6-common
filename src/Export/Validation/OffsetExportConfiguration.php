<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Validation;

use Symfony\Component\Validator\Constraints as Assert;

class OffsetExportConfiguration extends ExportConfigurationBase
{
    public const DEFAULT_START_PARAM = 0;

    #[Assert\NotBlank]
    #[Assert\Type(
        type: 'integer',
        message: 'The value {{ value }} is not a valid {{ type }}.',
    )]
    #[Assert\GreaterThanOrEqual(0)]
    protected int $start;

    public function __construct(string $shopkey, int $start, int $count, ?string $productId = null)
    {
        parent::__construct($shopkey, $count, $productId);

        $this->start = $start;
    }

    public function getStart(): int
    {
        return $this->start;
    }
}
