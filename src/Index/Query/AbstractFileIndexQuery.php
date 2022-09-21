<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index\Query;

use MakinaCorpus\Files\Index\Query\Error\ImpossibleQueryError;
use MakinaCorpus\Files\Index\Query\Error\InvalidArgumentError;

/**
 * Abstract class for file index query. All you need is to implement the
 * missing execute() method that should read protected properties of this
 * class for building its query.
 *
 * If the storage backend does not support some of the criterions used
 * then just override the associated method(s) and raise some exceptions
 * in there. Or ignore it silently if you want to, but at least document
 * it seriously.
 */
abstract class AbstractFileIndexQuery implements FileIndexQuery
{
    protected int $limit = 100;
    protected int $page = 1;
    protected bool $deleted = false;
    protected ?int $minSize = null;
    protected ?int $maxSize = null;
    protected ?\DateTimeInterface $createdBefore = null;
    protected ?\DateTimeInterface $createdAfter = null;
    protected ?string $filenameContains = null;
    protected array $mimeTypes = [];
    protected array $mimeTypesMatches = [];
    protected array $attributes = [];

    /**
     * {@inheritdoc}
     */
    public function limit(int $limit): self
    {
        $this->validatePositiveInteger($limit);

        $this->limit = $limit;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function page(int $page): self
    {
        $this->validatePositiveInteger($page, 1);

        $this->page = $page;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function deleted(): self
    {
        $this->deleted = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function mimetype(string $mimetype): self
    {
        $this->mimeTypes[] = $mimetype;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function mimetypeContains(string $match): self
    {
        $this->mimeTypesMatches[] = $match;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function filenameContains(string $value): self
    {
        $this->filenameContains = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function attribute(string $name, ?string $value = null)
    {
        if (null === $value) {
            $this->attributes[$name] = [];

            return $this;
        }

        if (isset($this->attributes[$name])) {
            if (empty($this->attributes[$name])) {
                // Empty means match all, leave match all.
                return $this;
            }
        }

        $this->attributes[$name][] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function minSize(int $size): self
    {
        $this->validatePositiveInteger($size);

        if (null !== $this->maxSize && $this->maxSize < $size) {
            throw new ImpossibleQueryError(\sprintf(
                "Query max size is set to '%d' which is less than '%d', this will yield no result.",
                $this->maxSize,
                $size
            ));
        }

        $this->minSize = $size;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function maxSize(int $size): self
    {
        $this->validatePositiveInteger($size);

        if (null !== $this->minSize && $this->minSize > $size) {
            throw new ImpossibleQueryError(\sprintf(
                "Query min size is set to '%d' which is greater than '%d' this will yield no result.",
                $this->minSize,
                $size
            ));
        }

        $this->maxSize = $size;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createdAfter(\DateTimeInterface $date): self
    {
        if (null !== $this->createdBefore && $this->createdBefore < $date) {
            throw new ImpossibleQueryError(\sprintf(
                "Query max date is set to '%s' which is less than '%s' this will yield no result.",
                $this->createdBefore->format(\DateTime::ISO8601),
                $date->format(\DateTime::ISO8601)
            ));
        }

        $this->createdAfter = $date;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createdBefore(\DateTimeInterface $date): self
    {
        if (null !== $this->createdAfter && $this->createdAfter > $date) {
            throw new ImpossibleQueryError(\sprintf(
                "Query min date is set to '%s' which is greater than '%s' this will yield no result.",
                $this->createdAfter->format(\DateTime::ISO8601),
                $date->format(\DateTime::ISO8601)
            ));
        }

        $this->createdBefore = $date;

        return $this;
    }

    protected function validatePositiveInteger(int $value, int $minValue = 0): void
    {
        if ($value < $minValue) {
            throw new InvalidArgumentError(\sprintf("Value '%d' is not a positive integer.", $value));
        }
    }
}
