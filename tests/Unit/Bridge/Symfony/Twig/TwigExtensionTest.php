<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Tests\Unit\Bridge\Symfony\Twig;

use MakinaCorpus\Files\FileManager;
use MakinaCorpus\Files\Bridge\Symfony\Twig\FileManagerExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * File manager tests
 */
final class TwigExtensionTest extends TestCase
{
    private function createTwigExtension(): FileManagerExtension
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('http://perdu.com/'));

        return new FileManagerExtension(
            new FileManager([
                FileManager::SCHEME_PRIVATE => '/some/path/../private//',
                FileManager::SCHEME_PUBLIC => '/var/www/html/',
                FileManager::SCHEME_UPLOAD => '/tmp/upload',
                FileManager::SCHEME_TEMPORARY => '/tmp',
            ], '/var/www/'),
            $requestStack
        );
    }

    public function testGetFileInternalUri(): void
    {
        $ext = $this->createTwigExtension();

        self::assertSame('public://pouet.png', $ext->getFileInternalUri('/var/www/html/pouet.png'));
        self::assertSame('public://pouet.png', $ext->getFileInternalUri('public://pouet.png'));
    }

    public function testGetFileAbsolutePath(): void
    {
        $ext = $this->createTwigExtension();

        self::assertSame('/var/www/html/pouet.png', $ext->getFileAbsolutePath('/var/www/html/pouet.png'));
        self::assertSame('/var/www/html/pouet.png', $ext->getFileAbsolutePath('public://pouet.png'));
    }

    public function testGetFileUrl(): void
    {
        $ext = $this->createTwigExtension();

        self::assertSame('/html/pouet.png', $ext->getFileUrl('/var/www/html/pouet.png'));
        self::assertSame('/html/pouet.png', $ext->getFileUrl('public://pouet.png'));
    }

    public function testGetFileUrlAbsolute(): void
    {
        $ext = $this->createTwigExtension();

        self::assertSame('http://perdu.com/html/pouet.png', $ext->getFileUrl('/var/www/html/pouet.png', true));
        self::assertSame('http://perdu.com/html/pouet.png', $ext->getFileUrl('public://pouet.png', true));
    }

    public function testGetFileUrlOutsideOfWebrootReturnsError(): void
    {
        $ext = $this->createTwigExtension();

        self::assertSame(FileManagerExtension::ERROR_PATH, $ext->getFileUrl('private://pouet.png'));
        self::assertSame(FileManagerExtension::ERROR_PATH, $ext->getFileUrl('/tmp/some/file.png'));
    }
}
