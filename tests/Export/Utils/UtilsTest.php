<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Tests\Export\Utils;

use FINDOLOGIC\Shopware6Common\Export\Utils\Utils;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    public function controlCharacterProvider(): array
    {
        return [
            'Strings with only letters and numbers' => [
                'Findologic123',
                'Findologic123',
                'Expected string to return unchanged',
            ],
            'Strings with whitespace' => [
                ' Findologic123 ',
                'Findologic123',
                'Expected string to be trimmed',
            ],
            'String with control characters' => [
                "Findologic\n1\t2\r3",
                'Findologic123',
                'Expected control characters to be stripped way',
            ],
            'String with another set of control characters' => [
                "Findologic\xC2\x9F\xC2\x80 Rocks",
                'Findologic Rocks',
                'Expected control characters to be stripped way',
            ],
            'String with special characters' => [
                'Findologic&123',
                'Findologic&123',
                'Expected special characters to be returned as they are',
            ],
            'String with umlauts' => [
                'Findolögic123',
                'Findolögic123',
                'Expected umlauts to be left unaltered.',
            ],
        ];
    }

    /**
     * @dataProvider controlCharacterProvider
     */
    public function testControlCharacterMethod(string $text, string $expected, string $errorMessage): void
    {
        $result = Utils::removeControlCharacters($text);
        $this->assertEquals($expected, $result, $errorMessage);
    }

    public static function cleanStringProvider(): array
    {
        return [
            'String with HTML tags' => [
                '<span>Findologic Rocks</span>',
                'Findologic Rocks',
                'Expected HTML tags to be stripped away',
            ],
            'String with single quotes' => [
                "Findologic's team rocks",
                'Findologic\'s team rocks',
                'Expected single quotes to be escaped with back slash',
            ],
            'String with double quotes' => [
                'Findologic "Rocks!"',
                'Findologic "Rocks!"',
                'Expected double quotes to be escaped with back slash',
            ],
            'String with back slashes' => [
                "Findologic\ Rocks!\\",
                'Findologic Rocks!',
                'Expected back slashes to be stripped away',
            ],
            'String with preceding space' => [
                ' Findologic Rocks ',
                'Findologic Rocks',
                'Expected preceding and succeeding spaces to be stripped away',
            ],
            'Strings with only letters and numbers' => [
                'Findologic123',
                'Findologic123',
                'Expected string to return unchanged',
            ],
            'String with control characters' => [
                "Findologic\n1\t2\r3",
                'Findologic 1 2 3',
                'Expected control characters to be stripped way',
            ],
            'String with another set of control characters' => [
                "Findologic\xC2\x9F\xC2\x80 Rocks",
                'Findologic Rocks',
                'Expected control characters to be stripped way',
            ],
            'String with special characters' => [
                'Findologic&123!',
                'Findologic&123!',
                'Expected special characters to be returned as they are',
            ],
            'String with umlauts' => [
                'Findolögic123',
                'Findolögic123',
                'Expected umlauts to be left unaltered.',
            ],
        ];
    }

    /**
     * @dataProvider cleanStringProvider
     */
    public function testCleanStringMethod(string $text, string $expected, string $errorMessage): void
    {
        $result = Utils::cleanString($text);
        $this->assertEquals($expected, $result, $errorMessage);
    }

    public function categoryProvider(): array
    {
        return [
            'main category' => [
                'breadCrumbs' => ['Main'],
                'expectedCategoryPath' => '',
            ],
            'one subcategory' => [
                'breadCrumbs' => ['Main', 'Sub'],
                'expectedCategoryPath' => 'Sub',
            ],
            'two subcategories' => [
                'breadCrumbs' => ['Main', 'Sub', 'SubOfSub'],
                'expectedCategoryPath' => 'Sub_SubOfSub',
            ],
            'three subcategories' => [
                'breadCrumbs' => ['Main', 'Sub', 'SubOfSub', 'very deep'],
                'expectedCategoryPath' => 'Sub_SubOfSub_very deep',
            ],
            'three subcategories with redundant spaces' => [
                'breadCrumbs' => [' Main', ' Sub ', 'SubOfSub  ', '   very deep'],
                'expectedCategoryPath' => 'Sub_SubOfSub_very deep',
            ],
        ];
    }

    /**
     * @dataProvider categoryProvider
     */
    public function testCategoryPathIsProperlyBuilt(array $breadCrumbs, string $expectedCategoryPath): void
    {
        $categoryPath = Utils::buildCategoryPath($breadCrumbs, ['Main']);

        $this->assertSame($expectedCategoryPath, $categoryPath);
    }

    public function testCategoryPathIsProperlyBuiltWhenMainCategoryIsInADeeperPath(): void
    {
        $expectedCategoryPath = 'Cookies_Soft Cookies';
        $breadCrumbs = ['Main', 'Food', 'Cookies', 'Soft Cookies'];

        $categoryPath = Utils::buildCategoryPath($breadCrumbs, ['Main', 'Food']);
        $this->assertSame($expectedCategoryPath, $categoryPath);
    }
}
