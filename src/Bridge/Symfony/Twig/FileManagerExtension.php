<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Bridge\Symfony\Twig;

use MakinaCorpus\Files\FileManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

final class FileManagerExtension extends AbstractExtension
{
    const ERROR_PATH = '#error';

    private FileManager $fileManager;
    private RequestStack $requestStack;

    public function __construct(FileManager $fileManager, RequestStack $requestStack)
    {
        $this->fileManager = $fileManager;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('file_absolute_path', [$this, 'getFileAbsolutePath']),
            new TwigFunction('file_internal_uri', [$this, 'getFileInternalUri']),
            new TwigFunction('file_url', [$this, 'getFileUrl']),
        ];
    }

    /**
     * From a filename or URI return the absolute URL on server.
     */
    public function getFileAbsolutePath(string $filenameOrUri): string
    {
        return $this->fileManager->getAbsolutePath($filenameOrUri);
    }

    /**
     * From a filename or URI return the internal URI.
     */
    public function getFileInternalUri(string $filenameOrUri): string
    {
        return $this->fileManager->getURI($filenameOrUri);
    }

    /**
     * From a filename or URI return the webroot relative URI.
     */
    public function getFileUrl(string $filenameOrUri, bool $absolute = false): string
    {
        $relativePath = $this->fileManager->getFileUrl($filenameOrUri);

        if (!$relativePath) {
            return self::ERROR_PATH;
        }

        if ($absolute) {
            if (\method_exists($this->requestStack, 'getMainRequest')) {
                // Symfony >= 6.0
                $request = $this->requestStack->getMainRequest();
            } else {
                // Symfony < 6.0
                $request = $this->requestStack->getMasterRequest();
            }

            if ($request) {
                if ($basePath = $request->getBasePath()) {
                    return $request->getSchemeAndHttpHost().'/'.$basePath.'/'.$relativePath;
                }
                return $request->getSchemeAndHttpHost().'/'.$relativePath;
            }
        }

        return '/'.$relativePath;
    }
}
