<?php

namespace FINDOLOGIC\Shopware6Common\Export\Services;

use FINDOLOGIC\Export\XML\XMLItem;
use FINDOLOGIC\Shopware6Common\Export\Errors\ExportErrors;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use FINDOLOGIC\Shopware6Common\Export\Search\AbstractProductCriteriaBuilder;
use FINDOLOGIC\Shopware6Common\Export\Search\ProductDebugSearcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class AbstractProductDebugService
{
    protected const NO_PRODUCT_EXPORTED = 'No product is exported';

    protected ExportContext $exportContext;

    protected ProductDebugSearcherInterface $productDebugSearcher;

    protected AbstractProductCriteriaBuilder $productCriteriaBuilder;

    protected string $productId;

    protected ExportErrors $exportErrors;

    protected ?XMLItem $xmlItem;

    protected DebugUrlBuilderService $debugUrlBuilderService;

    public function __construct(
        ExportContext $exportContext,
        ProductDebugSearcherInterface $productDebugSearcher,
        AbstractProductCriteriaBuilder $productCriteriaBuilder
    ) {
        $this->exportContext = $exportContext;
        $this->productDebugSearcher = $productDebugSearcher;
        $this->productCriteriaBuilder = $productCriteriaBuilder;
    }

    public function getDebugInformation(
        string $productId,
        string $shopkey,
        ?XMLItem $xmlItem,
        $exportedMainProduct,
        ExportErrors $exportErrors
    ): JsonResponse {
        $this->initialize($productId, $shopkey, $xmlItem, $exportedMainProduct, $exportErrors);

        if (!$this->getRequestedProduct()) {
            $this->exportErrors->addGeneralError(
                sprintf('Product or variant with ID %s does not exist.', $this->productId)
            );

            return new JsonResponse(
                $this->exportErrors->buildErrorResponse(),
                422
            );
        }

        $isExported = $this->isExported() && !$exportErrors->hasErrors();

        if (!$isExported) {
            $this->checkExportCriteria();
        }

        return new JsonResponse([
            'export' => [
                'productId' => $this->getRequestedProductId(),
                'exportedMainProductId' => $this->getExportedMainProductProduct()
                    ? $this->getExportedMainProductProductId()
                    : self::NO_PRODUCT_EXPORTED,
                'isExported' => $isExported,
                'reasons' => $this->parseExportErrors()
            ],
            'debugLinks' => [
                'exportUrl' => $this->getExportedMainProductProduct()
                    ? $this->debugUrlBuilderService->buildExportUrl($this->getExportedMainProductProductId())
                    : self::NO_PRODUCT_EXPORTED,
                'debugUrl' => $this->getExportedMainProductProduct()
                    ? $this->debugUrlBuilderService->buildDebugUrl($this->getExportedMainProductProductId())
                    : self::NO_PRODUCT_EXPORTED,
            ],
            'data' => [
                'isExportedMainVariant' => $this->getExportedMainProductProduct() &&
                    $this->getExportedMainProductProductId() === $this->getRequestedProductId(),
                'product' => $this->getRequestedProduct(),
                'siblings' => $this->getRequestedProductParentId()
                    ? $this->productDebugSearcher->getSiblings($this->getRequestedProductParentId(), 100)
                    : [],
                'associations' => $this->productDebugSearcher
                    ->buildCriteria()
                    ->getAssociations(),
            ]
        ]);
    }

    protected function initialize(
        string $productId,
        string $shopkey,
        ?XMLItem $xmlItem,
        $exportedMainProduct,
        ExportErrors $exportErrors
    ): void {
        $this->productId = $productId;
        $this->exportErrors = $exportErrors;
        $this->xmlItem = $xmlItem;
    }

    private function isExported(): bool
    {
        return $this->isVisible() && $this->isExportedVariant();
    }

    private function isVisible(): bool
    {
        $isVisible = isset($this->xmlItem) && $this->isRequestedProductActive();
        if (!$isVisible) {
            $this->exportErrors->addGeneralError('Product could not be found or is not available for search.');
        }

        return $isVisible;
    }

    private function isExportedVariant(): bool
    {
        if (!$isExportedVariant = $this->getExportedMainProductProductId() === $this->productId) {
            $this->exportErrors->addGeneralError('Product is not the exported variant.');
        }

        return $isExportedVariant;
    }

    private function checkExportCriteria(): void
    {
        $criteriaMethods = [
            'withDisplayGroupFilter' => 'No display group set',
            'withOutOfStockFilter' => 'Closeout product is out of stock',
            'withVisibilityFilter' => 'Product is not visible for search',
            'withPriceZeroFilter' => 'Product has a price of 0',
        ];

        foreach ($criteriaMethods as $method => $errorMessage) {
            $criteria = $this->productCriteriaBuilder
                ->withIds([$this->getRequestedProductId()])
                ->$method()
                ->build();

            if (!$this->productDebugSearcher->searchProduct($criteria)) {
                $this->exportErrors->addGeneralError($errorMessage);
            }
        }
    }

    /**
     * @return string[]
     */
    private function parseExportErrors(): array
    {
        $errors = [];

        if ($this->exportErrors->hasErrors()) {
            $errors = array_merge($errors, $this->exportErrors->getGeneralErrors());

            if ($productErrors = $this->exportErrors->getProductError($this->productId)) {
                $errors = array_merge($errors, $productErrors->getErrors());
            }
        }

        return $errors;
    }

    protected abstract function getRequestedProduct();

    protected abstract function getRequestedProductId(): string;

    protected abstract function getRequestedProductParentId(): ?string;

    protected abstract function isRequestedProductActive(): bool;

    protected abstract function getExportedMainProductProduct();

    protected abstract function getExportedMainProductProductId(): string;
}
