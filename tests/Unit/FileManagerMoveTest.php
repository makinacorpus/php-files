<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Tests\Unit;

use MakinaCorpus\Files\FileManager;
use MakinaCorpus\Files\Error\FileAlreadyExistsError;
use MakinaCorpus\Files\Error\FileDoesNotExistError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * File manager tests for the rename and alternatives functions.
 */
final class FileManagerMoveTest extends TestCase
{
    private function createFileManager(): FileManager
    {
        $manager = new FileManager([
            'test' => \dirname(__DIR__) . '/Resources',
            FileManager::SCHEME_PRIVATE => '/tmp/private',
            FileManager::SCHEME_PUBLIC => '/tmp/public',
            FileManager::SCHEME_UPLOAD => '/tmp/upload',
            FileManager::SCHEME_TEMPORARY => '/tmp',
        ]);

        // @todo using setup() would be better here
        // Prepare test case.
        // Avoid conflicts with previous tests.
        $filesystem = new Filesystem();
        $filesystem->remove($manager->getAbsolutePath('temporary://destination'));
        $filesystem->remove($manager->getAbsolutePath('temporary://source'));

        // Create test files.
        $manager->mkdir('temporary://destination');
        $manager->mkdir('temporary://destination/alt');
        $manager->mkdir('temporary://source');
        $manager->copy('test://cat1200.jpg', 'temporary://destination/file.jpg');
        $manager->copy('test://cat800.jpg', 'temporary://source/file.jpg');

        return $manager;
    }

    public function testIfRenameWithin(): void
    {
        $manager = $this->createFileManager();

        // File moves
        self::assertSame(
            'temporary://destination/alt/file.jpg',
            $manager->renameIfNotWithin('temporary://source/file.jpg', 'temporary://destination/alt')
        );

        // File does not move
        self::assertSame(
            'temporary://destination/alt/file.jpg',
            $manager->renameIfNotWithin('temporary://destination/alt/file.jpg', 'temporary://destination')
        );
    }

    public function testIfRenameWithinDoesNotMove(): void
    {
        $manager = $this->createFileManager();

        self::assertSame(
            'temporary://destination/alt/file.jpg',
            $manager->renameIfNotWithin('temporary://source/file.jpg', 'temporary://destination/alt')
        );
    }

    public function testDeduplicateNameWithExt(): void
    {
        $manager = $this->createFileManager();

        self::assertSame('test://cat1200_2.jpg', $manager->deduplicate('test://cat1200.jpg'));
    }

    public function testDeduplicateNameWithoutExt(): void
    {
        $manager = $this->createFileManager();

        self::assertSame('test://cat1200_1', $manager->deduplicate('test://cat1200'));
    }

    public function testRenameWithInvalidDirectoryStrategy(): void
    {
        $manager = $this->createFileManager();

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageMatches('/directory strategy/');
        $manager->rename('temporary://source/file.jpg', 'temporary://destination', 0, 'foo');
    }

    public function testRenameWithDateDirectoryStrategy(): void
    {
        $manager = $this->createFileManager();

        $date = new \DateTime();
        $result = $manager->rename('temporary://source/file.jpg', 'temporary://destination', 0, 'date');
        self::assertSame(
            \sprintf(
                'temporary://destination/%s/%s/%s/file.jpg',
                $date->format('Y'), $date->format('m'), $date->format('d')
            ),
            $result
        );
    }

    public function testRenameWithDatetimeDirectoryStrategy(): void
    {
        $manager = $this->createFileManager();

        $date = new \DateTime();
        $result = $manager->rename('temporary://source/file.jpg', 'temporary://destination', 0, 'datetime');

        self::assertSame(
            \sprintf(
                'temporary://destination/%s/%s/%s/%s/%s/file.jpg',
                $date->format('Y'), $date->format('m'), $date->format('d'),
                $date->format('h'), $date->format('i')
            ),
            $result
        );
    }

    public function testRenameWithNonExistingFileRaiseError(): void
    {
        $manager = $this->createFileManager();

        self::expectException(FileDoesNotExistError::class);
        self::expectExceptionMessageMatches('/does not exist/');

        $manager->rename('temporary://source/non-existing-file.jpg', 'temporary://destination');
    }

    public function testRenameWithOverwriteStrategy(): void
    {
        $manager = $this->createFileManager();

        self::assertFalse($manager->isDuplicateOf('temporary://source/file.jpg', 'temporary://destination/file.jpg'));

        $manager->copy('temporary://source/file.jpg', 'temporary://source/file-reference.jpg');

        $result = $manager->rename(
            'temporary://source/file.jpg', 'temporary://destination',
            FileManager::MOVE_CONFLICT_OVERWRITE
        );

        self::assertSame('temporary://destination/file.jpg', $result);
        self::assertTrue($manager->isDuplicateOf('temporary://source/file-reference.jpg', 'temporary://destination/file.jpg'));
    }

    public function testRenameWithRenameStrategy(): void
    {
        $manager = $this->createFileManager();

        $result = $manager->rename(
            'temporary://source/file.jpg', 'temporary://destination',
            FileManager::MOVE_CONFLICT_RENAME
        );

        self::assertSame('temporary://destination/file_1.jpg', $result);
    }

    public function testRenameWithErrorStrategy(): void
    {
        $manager = $this->createFileManager();

        self::expectException(FileAlreadyExistsError::class);
        self::expectExceptionMessageMatches('/file exists/');

        $manager->rename('temporary://source/file.jpg', 'temporary://destination');
    }
}
