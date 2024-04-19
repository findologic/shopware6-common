<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Validation;

use FINDOLOGIC\Shopware6Common\Export\Validation\ExportConfigurationBase;
use FINDOLOGIC\Shopware6Common\Export\Validation\OffsetDebugConfiguration;
use FINDOLOGIC\Shopware6Common\Export\Validation\OffsetExportConfiguration;
use FINDOLOGIC\Shopware6Common\Export\Validation\PageDebugConfiguration;
use FINDOLOGIC\Shopware6Common\Export\Validation\PageExportConfiguration;
use FINDOLOGIC\Shopware6Common\Tests\CommonConstants;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;

class ExportConfigurationTest extends TestCase
{
    public function testGetInstanceReturnsConfigWithGivenStartArguments(): void
    {
        $expectedShopkey = CommonConstants::VALID_SHOPKEY;
        $expectedStart = 0;
        $expectedCount = 100;

        $request = $this->createFindologicRequest([
            'shopkey' => $expectedShopkey,
            'start' => $expectedStart,
            'count' => $expectedCount,
        ]);

        $config = ExportConfigurationBase::getInstance($request);

        $this->assertSame($expectedShopkey, $config->getShopkey());
        $this->assertSame($expectedStart, $config->getStart());
        $this->assertSame($expectedCount, $config->getCount());
    }

    public function testGetInstanceReturnsConfigWithGivenOffsetArguments(): void
    {
        $expectedShopkey = CommonConstants::VALID_SHOPKEY;
        $expectedPage = 1;
        $expectedCount = 100;

        $request = $this->createExportRequest([
            'shopkey' => $expectedShopkey,
            'page' => $expectedPage,
            'count' => $expectedCount,
        ]);

        $config = ExportConfigurationBase::getInstance($request);

        $this->assertSame($expectedShopkey, $config->getShopkey());
        $this->assertSame($expectedPage, $config->getPage());
        $this->assertSame($expectedCount, $config->getCount());
    }

    public function testStartDefaultsAreSet(): void
    {
        $expectedDefaultStart = 0;
        $expectedDefaultCount = 20;

        $request = $this->createFindologicRequest([
            'shopkey' => CommonConstants::VALID_SHOPKEY,
        ]);

        $config = ExportConfigurationBase::getInstance($request);

        $this->assertSame($expectedDefaultStart, $config->getStart());
        $this->assertSame($expectedDefaultCount, $config->getCount());
    }

    public function testPageDefaultsAreSet(): void
    {
        $expectedDefaultStart = 1;
        $expectedDefaultCount = 20;

        $request = $this->createExportRequest([
            'shopkey' => CommonConstants::VALID_SHOPKEY,
        ]);

        $config = ExportConfigurationBase::getInstance($request);

        $this->assertSame($expectedDefaultStart, $config->getPage());
        $this->assertSame($expectedDefaultCount, $config->getCount());
    }

    public function testProductIdIsSetWhenGiven(): void
    {
        $expectedProductId = '03cca9ceac4047e4b331b6827e245594';

        $requestFindologic = $this->createFindologicRequest([
            'shopkey' => CommonConstants::VALID_SHOPKEY,
            'productId' => $expectedProductId,
        ]);
        $requestExport = $this->createExportRequest([
            'shopkey' => CommonConstants::VALID_SHOPKEY,
            'productId' => $expectedProductId,
        ]);

        $configFindologic = ExportConfigurationBase::getInstance($requestFindologic);
        $configExport = ExportConfigurationBase::getInstance($requestExport);

        $this->assertSame($expectedProductId, $configFindologic->getProductId());
        $this->assertSame($expectedProductId, $configExport->getProductId());
    }

    public static function invalidConfigurationProvider(): array
    {
        return [
            'No parameters given' => [
                'queryParams' => [],
            ],
            'Shopkey does not match the schema' => [
                'queryParams' => [
                    'shopkey' => 'hehe i am a bad shopkey',
                ],
            ],
            'Shopkey matches the schema but count is negative' => [
                'queryParams' => [
                    'shopkey' => CommonConstants::VALID_SHOPKEY,
                    'count' => -1,
                ],
            ],
            'Shopkey matches the schema but count is zero' => [
                'queryParams' => [
                    'shopkey' => CommonConstants::VALID_SHOPKEY,
                    'count' => 0,
                ],
            ],
            'Shopkey matches the schema but start is negative' => [
                'queryParams' => [
                    'shopkey' => CommonConstants::VALID_SHOPKEY,
                    'start' => -1,
                ],
                'exportPath' => 'findologic',
            ],
            'Shopkey matches the schema but page is zero' => [
                'queryParams' => [
                    'shopkey' => CommonConstants::VALID_SHOPKEY,
                    'page' => 0,
                ],
                'exportPath' => 'export',
            ],
            'Findologic: All params are invalid' => [
                'queryParams' => [
                    'shopkey' => 'i am invalid',
                    'count' => -55,
                    'start' => -134,
                ],
                'exportPath' => 'findologic',
            ],
            'Export: All params are invalid' => [
                'queryParams' => [
                    'shopkey' => 'i am invalid',
                    'count' => -55,
                    'page' => -134,
                ],
                'exportPath' => 'export',
            ],
        ];
    }

    #[DataProvider('invalidConfigurationProvider')]
    public function testInvalidConfigurationIsDetected(
        array $queryParams,
        ?string $exportPath = null
    ): void {
        $configs = [];
        if (!$exportPath || $exportPath === 'findologic') {
            $requestFindologic = $this->createFindologicRequest($queryParams);
            $configs[] = ExportConfigurationBase::getInstance($requestFindologic);
        }
        if (!$exportPath || $exportPath === 'export') {
            $requestExport = $this->createExportRequest($queryParams);
            $configs[] = ExportConfigurationBase::getInstance($requestExport);
        }

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $validationsList = new ConstraintViolationList();
        foreach ($configs as $config) {
            $validationsList->addAll(
                $validator->validate($config),
            );
        }

        $this->assertGreaterThan(0, $validationsList->count());
    }

    public static function pathProvider(): array
    {
        return [
            'Findologic: Export path' => [
                'path' => 'findologic',
                'expectedClass' => OffsetExportConfiguration::class,
            ],
            'Findologic: Dynamic product groups path' => [
                'path' => 'findologic/dynamic-product-groups',
                'expectedClass' => OffsetExportConfiguration::class,
            ],
            'Findologic: Export debug path' => [
                'path' => 'findologic/debug',
                'expectedClass' => OffsetDebugConfiguration::class,
            ],
            'Export: Export path' => [
                'path' => 'export',
                'expectedClass' => PageExportConfiguration::class,
            ],
            'Export: Dynamic product groups path' => [
                'path' => 'export/dynamic-product-groups',
                'expectedClass' => PageExportConfiguration::class,
            ],
            'Export: Export debug path' => [
                'path' => 'export/debug',
                'expectedClass' => PageDebugConfiguration::class,
            ],
        ];
    }

    #[DataProvider('pathProvider')]
    public function testGetInstanceReturnsCorrectConfiguration(string $path, $expectedClass): void
    {
        $request = $this->createFindologicRequest([], $path);

        $config = ExportConfigurationBase::getInstance($request);

        $this->assertEquals($expectedClass, get_class($config));
    }

    private function createFindologicRequest(?array $query = [], ?string $path = 'findologic'): Request
    {
        return new Request($query, [], [], [], [], ['REQUEST_URI' => 'https://example.com/' . $path]);
    }

    private function createExportRequest(?array $query = [], ?string $path = 'export'): Request
    {
        return new Request($query, [], [], [], [], ['REQUEST_URI' => 'https://example.com/' . $path]);
    }
}
