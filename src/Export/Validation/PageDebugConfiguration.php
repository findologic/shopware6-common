<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Validation;

use Symfony\Component\Validator\Constraints as Assert;

class PageDebugConfiguration extends PageExportConfiguration
{
    #[Assert\NotBlank]
    protected ?string $productId;

    public function __construct(string $shopkey, ?string $productId = null)
    {
        parent::__construct($shopkey, parent::DEFAULT_PAGE_PARAM, 1, $productId);
    }
}
