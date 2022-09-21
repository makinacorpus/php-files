<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Tests\Functional\Bridge\Goat\Index;

use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTestTrait;
use Goat\Runner\Testing\TestDriverFactory;
use MakinaCorpus\Files\FileManager;
use MakinaCorpus\Files\Bridge\Goat\Index\GoatFileIndex;
use MakinaCorpus\Files\Index\FileIndex;
use PHPUnit\Framework\TestCase;

final class GoatFileIndexTest extends TestCase
{
    use DatabaseAwareQueryTestTrait;

    private function createFileManager(): FileManager
    {
        return new FileManager([
            'mock' => \dirname(__DIR__, 4) . '/Resources',
            FileManager::SCHEME_TEMPORARY => '/tmp',
        ]);
    }

    private function createTestSchema(Runner $runner)
    {
        $runner->execute(
            <<<SQL
            DROP TABLE IF EXISTS "file_attribute" CASCADE;
            SQL
        );

        $runner->execute(
            <<<SQL
            DROP TABLE IF EXISTS "file" CASCADE;
            SQL
        );

        $runner->execute(
            <<<SQL
            CREATE TABLE "file" (
                "id" uuid NOT NULL,
                "indexed_at" timestamp NOT NULL DEFAULT current_timestamp,
                "created_at" timestamp NOT NULL DEFAULT current_timestamp,
                "modified_at" timestamp NOT NULL DEFAULT current_timestamp,
                "expires_at" date DEFAULT NULL,
                "deleted_at" timestamp DEFAULT NULL,
                "is_deleted" bool DEFAULT false,
                "is_valid" bool NOT NULL DEFAULT true,
                "is_anonymized" bool NOT NULL DEFAULT false,
                "type" varchar(64) NOT NULL,
                "name" varchar(255) NOT NULL,
                "filename" varchar(1024) NOT NULL,
                "filesize" int NOT NULL,
                "mimetype" varchar(64) NOT NULL,
                "sha1sum" varchar(64) DEFAULT NULL,
                PRIMARY KEY ("id")
            );
            SQL
        );

        $runner->execute(
            <<<SQL
            CREATE TABLE "file_attribute" (
                "file_id" uuid NOT NULL,
                "name" varchar(255) NOT NULL,
                "value" text NOT NULL,
                PRIMARY KEY ("file_id", "name"),
                FOREIGN KEY ("file_id")
                    REFERENCES "file" ("id")
                    ON DELETE CASCADE
            );
            SQL
        );
    }

    private function createFileIndex(FileManager $fileManager, Runner $runner): FileIndex
    {
        $this->createTestSchema($runner);

        return new GoatFileIndex($fileManager, $runner);
    }

    /** @dataProvider runnerDataProvider */
    public function testIndex(TestDriverFactory $factory): void
    {
        $fileManager = $this->createFileManager();
        $fileIndex = $this->createFileIndex($fileManager, $factory->getRunner());

        $file = $fileManager->create('mock://cat1200.jpg');

        $indexed = $fileIndex->index($file, ['foo' => 'bar', 'baz' => 'beh', 'void' => null]);

        self::assertNotNull($indexed->getId());
        self::assertNotNull($indexed->getDisplayLabel());
        self::assertNotNull($indexed->getIndexedAt());

        self::assertFalse($indexed->isDeleted());
        self::assertNull($indexed->getDeletedAt());

        self::assertNotNull($indexed->getFile()->getSha1sum());
        self::assertSame($file->getSha1sum(), $indexed->getFile()->getSha1sum());
        self::assertSame($file->getMimeType(), $indexed->getFile()->getMimeType());
        self::assertSame($file->toString(), $indexed->getFile()->toString());

        self::assertSame('bar', $indexed->getAttribute('foo'));
        self::assertSame('beh', $indexed->getAttribute('baz'));
        self::assertNull($indexed->getAttribute('void'));
        self::assertIsArray($indexed->getAttributes());
    }

    /** @dataProvider runnerDataProvider */
    public function testGetAttributesLazy(TestDriverFactory $factory): void
    {
        $fileManager = $this->createFileManager();
        $fileIndex = $this->createFileIndex($fileManager, $factory->getRunner());

        $file = $fileManager->create('mock://cat1200.jpg');

        $indexed = $fileIndex->index($file, ['foo' => 'bar', 'fizz' => 'buzz']);

        $reloaded = $fileIndex->find($indexed->getId());

        self::assertSame('bar', $reloaded->getAttribute('foo'));
        self::assertSame('buzz', $reloaded->getAttribute('fizz'));
        self::assertNull($reloaded->getAttribute('void'));

        // Warning: attributes are name-ordered.
        self::assertSame(['fizz' => 'buzz', 'foo' => 'bar'], $reloaded->getAttributes());
    }

    /** @dataProvider runnerDataProvider */
    public function testIndexSameUriTwiceGiveTwoRows(TestDriverFactory $factory): void
    {
        $fileManager = $this->createFileManager();
        $fileIndex = $this->createFileIndex($fileManager, $factory->getRunner());

        $file = $fileManager->create('mock://cat1200.jpg');

        $indexed1 = $fileIndex->index($file, ['foo' => 'bar']);
        $indexed2 = $fileIndex->index($file, ['fizz' => 'buzz']);

        self::assertFalse($indexed1->getId()->equals($indexed2->getId()));
    }

    /** @dataProvider runnerDataProvider */
    public function testDelete(TestDriverFactory $factory): void
    {
        $fileManager = $this->createFileManager();
        $fileIndex = $this->createFileIndex($fileManager, $factory->getRunner());

        $file = $fileManager->create('mock://cat1200.jpg');

        $indexed1 = $fileIndex->index($file);
        $indexed2 = $fileIndex->index($file);

        $fileIndex->delete($indexed1->getId());

        $reloaded1 = $fileIndex->find($indexed1->getId());
        self::assertTrue($reloaded1->isDeleted());
        self::assertNotNull($reloaded1->getDeletedAt());

        $reloaded2 = $fileIndex->find($indexed2->getId());
        self::assertFalse($reloaded2->isDeleted());
        self::assertNull($reloaded2->getDeletedAt());
    }

    /** @dataProvider runnerDataProvider */
    public function testDeleteFromUri(TestDriverFactory $factory): void
    {
        $fileManager = $this->createFileManager();
        $fileIndex = $this->createFileIndex($fileManager, $factory->getRunner());

        $file1 = $fileManager->create('mock://cat1200.jpg');
        $file2 = $fileManager->create('mock://cat800.jpg');

        $indexed1 = $fileIndex->index($file1);
        $indexed2 = $fileIndex->index($file2);
        $indexed3 = $fileIndex->index($file1);

        $fileIndex->deleteFromUri('mock://cat1200.jpg');

        $reloaded1 = $fileIndex->find($indexed1->getId());
        self::assertTrue($reloaded1->isDeleted());
        self::assertNotNull($reloaded1->getDeletedAt());

        $reloaded2 = $fileIndex->find($indexed2->getId());
        self::assertFalse($reloaded2->isDeleted());
        self::assertNull($reloaded2->getDeletedAt());

        $reloaded3 = $fileIndex->find($indexed3->getId());
        self::assertTrue($reloaded3->isDeleted());
        self::assertNotNull($reloaded3->getDeletedAt());
    }
}
