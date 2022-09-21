<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Index\Query;

/**
 * This query is an interface, because storage capabilities may vary.
 *
 * Beware that you might catch exception on some method calls.
 *
 * @todo Missing sorts.
 */
interface FileIndexQuery
{
    /**
     * Set limit, set 0 for no limit, default is 100.
     *
     * If no limit is provide, page will be ignored.
     */
    public function limit(int $limit): self;

    /**
     * Set page number, starts with 1.
     */
    public function page(int $page): self;

    /**
     * Find only deleted files.
     *
     * Deleted files are not supposed to come out in results, by calling this
     * you reverse the query, and only deleted files will show up.
     *
     * Deleted files are supposed to be cleaned up by background processes, so
     * you might see some, if you're lucky.
     */
    public function deleted(): self;

    /**
     * Add a mimetype, you may call this method more than once will create an
     * OR condition.
     *
     * This will OR with self::mimetypeContains().
     */
    public function mimetype(string $mimetype): self;

    /**
     * Search on loosee mimetype (for example "image" or "json").
     *
     * This will OR with self::mimetype().
     */
    public function mimetypeContains(string $match): self;

    /**
     * Search for filename containing given value. You can call this method
     * only once. Filename includes the whole file path and basename.
     */
    public function filenameContains(string $match): self;

    /**
     * Search based upon attributes. If value is null, any value will match.
     *
     * You may call this function as many time as you wish, same attribute
     * name used more than once will create an OR condition.
     *
     * Different attributes names will do an AND conditon altogether.
     */
    public function attribute(string $name, ?string $value = null);

    /**
     * Minimum size in bytes.
     */
    public function minSize(int $size): self;

    /**
     * Maximum size in bytes.
     */
    public function maxSize(int $size): self;

    /**
     * File was created before.
     */
    public function createdAfter(\DateTimeInterface $date): self;

    /**
     * File was created after.
     */
    public function createdBefore(\DateTimeInterface $date): self;

    /**
     * Execute query and fetch result.
     */
    public function execute(): FileIndexQueryResult;
}
