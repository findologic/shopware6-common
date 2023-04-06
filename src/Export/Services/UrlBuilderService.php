<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Services;

use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use Symfony\Component\Routing\RouterInterface;
use Vin\ShopwareSdk\Data\Entity\EntityCollection;

class UrlBuilderService
{
    public function __construct(
        protected readonly ExportContext $exportContext,
        protected readonly ?RouterInterface $router = null,
    ) {
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
}
