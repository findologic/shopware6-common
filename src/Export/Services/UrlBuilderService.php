<?php

namespace FINDOLOGIC\Shopware6Common\Export\Services;

use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use Symfony\Component\Routing\RouterInterface;
use Vin\ShopwareSdk\Data\Entity\EntityCollection;

class UrlBuilderService
{
    protected ExportContext $exportContext;

    protected ?RouterInterface $router;

    public function __construct(
        ExportContext $exportContext,
        ?RouterInterface $router = null
    ) {
        $this->router = $router;
        $this->exportContext = $exportContext;
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
            $this->exportContext->getLanguageId()
        );

        if ($translatedEntities->count() === 0) {
            return null;
        }

        return $translatedEntities;
    }
}
