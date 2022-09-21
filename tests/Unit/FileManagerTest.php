<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Tests\Unit;

use MakinaCorpus\Files\FileManager;
use PHPUnit\Framework\TestCase;

/**
 * File manager tests
 */
final class FileManagerTest extends TestCase
{
    private function createFileManager(): FileManager
    {
        return new FileManager([
            FileManager::SCHEME_PRIVATE => '/some/path/../private//',
            FileManager::SCHEME_PUBLIC => '/var/www/html/',
            FileManager::SCHEME_UPLOAD => '/tmp/upload',
            FileManager::SCHEME_TEMPORARY => '/tmp',
        ]);
    }

    public function testGetWorkingDirectory(): void
    {
        $manager = $this->createFileManager();

        self::assertSame('/some/private', $manager->getWorkingDirectory(FileManager::SCHEME_PRIVATE));
    }

    public function testGetAbsolutePathWithSchemeOnly(): void
    {
        $manager = $this->createFileManager();

        self::assertSame('/some/private', $manager->getAbsolutePath('private://'));
    }

    public function testGetWorkingDirectoryFailsWithUnknownScheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createFileManager()->getWorkingDirectory('unknownone');
    }

    public function testIsPathWithin(): void
    {
        $manager = $this->createFileManager();

        self::assertFalse($manager->isPathWithin('/foo/../bar/../baz/test', '/foo/bar/../baz'));
        self::assertTrue($manager->isPathWithin('/foo/baz/test', '/foo/bar/../baz'));
        self::assertTrue($manager->isPathWithin('/var/www/html/bar/pouet', 'public://bar'));
        self::assertTrue($manager->isPathWithin('public://bar/pouet', '/var/www/html/bar'));
        self::assertTrue($manager->isPathWithin('public://bar/pouet', 'public://bar'));
    }

    public function testGetRelativePathFrom(): void
    {
        $manager = $this->createFileManager();

        self::assertNull($manager->getRelativePathFrom('/foo/../bar/../baz/test', '/foo/bar/../baz'));
        self::assertSame('test', $manager->getRelativePathFrom('/foo/baz/test', '/foo/bar/../baz'));
        self::assertSame('baz/pouet', $manager->getRelativePathFrom('/var/www/html/bar/baz/pouet', 'public://bar'));
        self::assertSame('baz/pouet', $manager->getRelativePathFrom('public://bar/baz/pouet', '/var/www/html/bar'));
        self::assertSame('baz/pouet', $manager->getRelativePathFrom('public://bar/baz/pouet', 'public://bar'));
    }

    public function testCreateNestedSchemeDeambiguation(): void
    {
        $manager = $this->createFileManager();

        $file = $manager->create('/tmp/upload/file.png');

        self::assertSame(FileManager::SCHEME_UPLOAD, $file->getScheme());

        $file = $manager->create('/tmp/pouf/file.png');

        self::assertSame(FileManager::SCHEME_TEMPORARY, $file->getScheme());
    }

    public function testCreateWithAbsolutePathWithUnknownScheme(): void
    {
        $manager = $this->createFileManager();

        $file = $manager->create('/some/path/file.png');

        self::assertSame(FileManager::SCHEME_LOCAL, $file->getScheme());
        self::assertSame('some/path/file.png', $file->getRelativePath());
        self::assertSame('/', $file->getWorkingDirectory());
    }

    public function testCreateWithAbsolutePathInKnownScheme(): void
    {
        $manager = $this->createFileManager();

        $file = $manager->create('/some/private/oups/../file/is/here.png');

        self::assertSame(FileManager::SCHEME_PRIVATE, $file->getScheme());
        self::assertSame('/some/private/file/is/here.png', $file->getAbsolutePath());
        self::assertSame('file/is/here.png', $file->getRelativePath());
        self::assertSame('/some/private', $file->getWorkingDirectory());
        self::assertSame('private://file/is/here.png', (string) $file);
    }

    public function testCreateWithAbsolutePathWithLocalSchemeInKnownScheme(): void
    {
        $manager = $this->createFileManager();

        $file = $manager->create('file:///some/private/file/is/here.png');

        self::assertSame(FileManager::SCHEME_PRIVATE, $file->getScheme());
        self::assertSame('/some/private/file/is/here.png', $file->getAbsolutePath());
        self::assertSame('file/is/here.png', $file->getRelativePath());
        self::assertSame('/some/private', $file->getWorkingDirectory());
        self::assertSame('private://file/is/here.png', (string) $file);
    }

    public function testCreateWithUnknownScheme(): void
    {
        $manager = $this->createFileManager();

        $file = $manager->create('ftp://192.168.1.32:2121/some/path/file.png');

        self::assertSame('ftp', $file->getScheme());
        self::assertSame('/192.168.1.32:2121/some/path/file.png', $file->getAbsolutePath());
        self::assertSame('192.168.1.32:2121/some/path/file.png', $file->getRelativePath());
        self::assertSame('/', $file->getWorkingDirectory());
        self::assertSame('ftp://192.168.1.32:2121/some/path/file.png', (string) $file);
    }

    public function testCreateWithScheme(): void
    {
        $manager = $this->createFileManager();

        $file = $manager->create('public://article/212/thumbnail.jpg');

        self::assertSame(FileManager::SCHEME_PUBLIC, $file->getScheme());
        self::assertSame('/var/www/html/article/212/thumbnail.jpg', $file->getAbsolutePath());
        self::assertSame('article/212/thumbnail.jpg', $file->getRelativePath());
        self::assertSame('/var/www/html', $file->getWorkingDirectory());
        self::assertSame('public://article/212/thumbnail.jpg', (string) $file);
    }
}
