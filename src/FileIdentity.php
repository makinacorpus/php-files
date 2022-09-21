<?php

declare(strict_types=1);

namespace MakinaCorpus\Files;

interface FileIdentity
{
    /**
     * Get file base name (e.g. for "tmp://bar/baz.txt" returns "baz.txt").
     */
    public function getBasename(): string;

    /**
     * Get URI scheme relative path (e.g. For "tmp://bar.baz" returns "bar.baz").
     */
    public function getRelativePath(): string;

    /**
     * Get scheme working directory (e.g. For "tmp://bar.baz" returns "/var/tmp/").
     *
     * Depending upon the URI, this may return nonsense.
     */
    public function getWorkingDirectory(): string;

    /**
     * Get URI scheme (e.g. For "tmp://bar.baz" returns "tmp").
     */
    public function getScheme(): string;

    /**
     * Get URI absolute path (e.g. For "tmp://bar.baz" returns "/var/tmp/bar.baz").
     */
    public function getAbsolutePath(): string;

    /**
     * URI representation with scheme.
     */
    public function toString(): string;

    /**
     * URI representation with scheme.
     */
    public function __toString(): string;
}
