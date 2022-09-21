<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Tests\Unit;

use MakinaCorpus\Files\FileManager;
use PHPUnit\Framework\TestCase;
use MakinaCorpus\Files\DefaultFile;

/**
 * Test default file behaviour.
 */
final class DefaultFileTest extends TestCase
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

    public function testExists(): void
    {
        $fileManager = $this->createFileManager();

        $existingFile = $fileManager->create(__FILE__);

        self::assertTrue($existingFile->exists());

        $nonExistingFile = $fileManager->create(__FILE__ . '.foo');

        self::assertFalse($nonExistingFile->exists());
    }

    public function testMimeTypeGuessing(): void
    {
        $fileManager = $this->createFileManager();
        $existingFile = $fileManager->create(__FILE__);

        self::assertSame('text/x-php', $existingFile->getMimeType());
    }

    public function testMimeTypeGuessingWhenNotExistsFallback(): void
    {
        $fileManager = $this->createFileManager();
        $nonExistingFile = $fileManager->create(__FILE__ . '.foo');

        self::assertSame('application/octet-stream', $nonExistingFile->getMimeType());
    }

    public function testMimeTypeCaching(): void
    {
        $existingFile = new DefaultFile('file', \ltrim(__FILE__), '/', 'foo/bar');

        self::assertSame('foo/bar', $existingFile->getMimeType());
    }

    public function testSha1Compute(): void
    {
        $fileManager = $this->createFileManager();
        $existingFile = $fileManager->create(\dirname(__DIR__) . '/Resources/cat1200_1.jpg');

        self::assertSame('e04154a7af70967c5b4ca72c913c4a94a234c621', $existingFile->getSha1sum());
    }

    public function testSha1Caching(): void
    {
        $existingFile = new DefaultFile('file', \ltrim(\dirname(__DIR__) . '/Resources/cat1200_1.jpg'), null, null, 'boo');

        self::assertSame('boo', $existingFile->getSha1sum());
    }

    public function testFilesizeCompute(): void
    {
        $fileManager = $this->createFileManager();
        $existingFile = $fileManager->create(\dirname(__DIR__) . '/Resources/cat1200_1.jpg');

        self::assertSame(100463, $existingFile->getFilesize());
    }

    public function testFilesizeComputeWhenNotExistsFallback(): void
    {
        $fileManager = $this->createFileManager();
        $nonExistingFile = $fileManager->create(\dirname(__DIR__) . '/Resources/cat1200_1_fooooooo.jpg');

        self::assertSame(0, $nonExistingFile->getFilesize());
    }

    public function testFilesizeCaching(): void
    {
        $existingFile = new DefaultFile('file', \ltrim(\dirname(__DIR__) . '/Resources/cat1200_1.jpg'), null, null, null, 12);

        self::assertSame(12, $existingFile->getFilesize());
    }
}
