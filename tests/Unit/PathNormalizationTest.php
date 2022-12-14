<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Tests\Unit;

use MakinaCorpus\Files\FileManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests path normalization
 */
final class PathNormalizationTest extends TestCase
{
    public static function getSchemeMap(): iterable
    {
        return [
            ['_pouet://a/b', '_pouet'],
            ['file://a/b', 'file'],
            ['file:/a/b', null],
            ['/pouet//tada:/truc', null],
            ['file:://a/b', 'file:'], // @todo this is an edge erroeneous case
            ['pouet_:///a/b', 'pouet_'],
        ];
    }

    /** @dataProvider getSchemeMap */
    public function testGetScheme($path, $expected): void
    {
        self::assertSame($expected, FileManager::getScheme($path));
    }

    public static function getStripSchemeMap(): iterable
    {
        return [
            ['_pouet://a/b', 'a/b'],
            ['file://a/b', 'a/b'],
            ['file:/a/b', 'file:/a/b'],
            ['/pouet//tada:/truc', '/pouet//tada:/truc'],
            ['file:://a/b', 'a/b'], // @todo this is an edge erroeneous case
            ['pouet_:///a/b', '/a/b'],
        ];
    }

    /** @dataProvider getStripSchemeMap */
    public function testStripScheme($path, $expected): void
    {
        self::assertSame($expected, FileManager::stripScheme($path));
    }

    public static function getPathNormalizationMap(): iterable
    {
        return [
            // Tests with '..'
            ['a/b/..', 'a'],
            ['https://a/b/../', 'https://a'],
            ['/a/b/c/d/../e/f', '/a/b/c/e/f'],
            ['a/b/c/../../e/f', 'a/e/f'],
            ['ftp://a/../b/../c/../e/f', 'ftp://e/f'],
            ['a../b/c../d..e/', 'a../b/c../d..e'],
            ['../c/d', '../c/d'],
            // Windows various
            // ['file://C:\\Windows\\system32', 'file://C:/Windows/system32'],
            // ['C:\\Windows\\system32', 'C:/Windows/system32'],
            // ['Windows\\drivers/system32', 'C:/Windows/system32'],
            // With multiple '/'
            ['/a/b/////c/d/../e/f', '/a/b/c/e/f'],
            ['file:////a/b/c//../..//e/f', 'file:///a/e/f'],
            ['////a/../b/../c//../e/f', '/e/f'],
            ['a../b//c../d..e/', 'a../b/c../d..e'],
            ['../c////d', '../c/d'],
            // With dots
            ['a/b/./././..', 'a'],
            ['a/.b/./../', 'a'],
            ['/a/b/.c/d/../e/f', '/a/b/.c/e/f'],
            ['.a/./b/c/.././../e./f', '.a/e./f'],
            // Special cases
            ['/', '/'],
            ['.', '.'],
            ['..', '..'],
            ['./', '.'],
            ['../', '..'],
        ];
    }

    /** @dataProvider getPathNormalizationMap */
    public function testNormalizePath($path, $expected): void
    {
        self::assertSame($expected, FileManager::normalizePath($path));
    }

    public static function getStripLocalSchemeMap(): iterable
    {
        return [
            // Matching file://
            ['file:///pouet', '/pouet'],
            ['file://C:\pouet', 'C:\pouet'],
            ['file://pouet', 'pouet'],
            // Not matching file://
            ['file:/pouet', 'file:/pouet'],
            ['nofile:///pouet', 'nofile:///pouet'],
            ['nofile://pouet', 'nofile://pouet'],
        ];
    }

    /** @dataProvider getStripLocalSchemeMap */
    public function testStripLocalScheme($path, $expected): void
    {
        self::assertSame($expected, FileManager::stripLocalScheme($path));
    }
}
