<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Services;

use FINDOLOGIC\Export\XML\XMLItem;
use FINDOLOGIC\Shopware6Common\Export\Errors\ExportErrors;
use FINDOLOGIC\Shopware6Common\Export\ExportContext;
use FINDOLOGIC\Shopware6Common\Export\Search\AbstractProductCriteriaBuilder;
use FINDOLOGIC\Shopware6Common\Export\Search\ProductDebugSearcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Vin\ShopwareSdk\Data\Entity\Product\ProductEntity;

class ProductDebugService
{
    protected const NO_PRODUCT_EXPORTED = 'No product is exported';

    protected string $productId;

    protected ExportErrors $exportErrors;

    protected ?XMLItem $xmlItem;

    protected ?ProductEntity $requestedProduct;

    protected ?ProductEntity $exportedMainProduct;

    protected DebugUrlBuilderService $debugUrlBuilderService;

    public function __construct(
        protected readonly ExportContext $exportContext,
        protected readonly ProductDebugSearcherInterface $productDebugSearcher,
        protected readonly AbstractProductCriteriaBuilder $productCriteriaBuilder,
        protected readonly string $basePath,
    ) {
    }

    public function getDebugInformation(
        string $productId,
        string $shopkey,
        ?XMLItem $xmlItem,
        ?ProductEntity $exportedMainProduct,
        ExportErrors $exportErrors
    ): JsonResponse {
        $this->initialize($productId, $shopkey, $xmlItem, $exportedMainProduct, $exportErrors);

        if (!$this->requestedProduct) {
            $this->exportErrors->addGeneralError(
                sprintf('Product or variant with ID %s does not exist.', $this->productId),
            );

            return new JsonResponse(
                $this->exportErrors->buildErrorResponse(),
                422,
            );
        }

        if (!$isExported = $this->isExported() && !$exportErrors->hasErrors()) {
            $this->checkExportCriteria();
        }

        return new JsonResponse([
            'export' => [
                'productId' => $this->requestedProduct->id,
                'exportedMainProductId' => $this->exportedMainProduct?->id ?? self::NO_PRODUCT_EXPORTED,
                'isExported' => $isExported,
                'reasons' => $this->parseExportErrors(),
            ],
            'debugLinks' => [
                'exportUrl' => $this->exportedMainProduct
                    ? $this->debugUrlBuilderService->buildExportUrl($this->exportedMainProduct->id)
                    : self::NO_PRODUCT_EXPORTED,
                'debugUrl' => $this->exportedMainProduct
                    ? $this->debugUrlBuilderService->buildDebugUrl($this->exportedMainProduct->id)
                    : self::NO_PRODUCT_EXPORTED,
            ],
            'data' => [
                'isExportedMainVariant' => $this->exportedMainProduct?->id === $this->requestedProduct->id,
                'product' => $this->requestedProduct,
                'siblings' => $this->requestedProduct->parentId
                    ? $this->productDebugSearcher->getSiblings($this->requestedProduct->parentId, 100)
                    : [],
                'associations' => $this->productDebugSearcher
                    ->buildCriteria()
                    ->getAssociations(),
            ],
        ]);
    }

    protected function initialize(
        string $productId,
        string $shopkey,
        ?XMLItem $xmlItem,
        ?ProductEntity $exportedMainProduct,
        ExportErrors $exportErrors
    ): void {
        $this->productId = $productId;
        $this->exportErrors = $exportErrors;
        $this->xmlItem = $xmlItem;
        $this->exportedMainProduct = $exportedMainProduct;

        $this->debugUrlBuilderService = new DebugUrlBuilderService(
            $this->exportContext,
            $shopkey,
            $this->basePath,
        );
        $this->requestedProduct = $this->productDebugSearcher->getProductById($productId);
    }

    private function isExported(): bool
    {
        return $this->isVisible() && $this->isExportedVariant();
    }

    private function isVisible(): bool
    {
        if (!$isVisible = isset($this->xmlItem) && $this->requestedProduct->active) {
            $this->exportErrors->addGeneralError('Product could not be found or is not available for search.');
        }

        return $isVisible;
    }

    private function isExportedVariant(): bool
    {
        if (!$isExportedVariant = $this->exportedMainProduct->id === $this->productId) {
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
                ->withIds([$this->requestedProduct->id])
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
}
