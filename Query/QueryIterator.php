<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2021 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\HtmlSelector\Query;

use Berlioz\HtmlSelector\Exception\QueryException;
use Berlioz\HtmlSelector\HtmlSelector;
use Countable;
use OutOfBoundsException;
use SeekableIterator;

/**
 * Class QueryIterator.
 */
class QueryIterator implements SeekableIterator, Countable
{
    private int $position = 0;

    /**
     * QueryIterator constructor.
     *
     * @param Query $query
     * @param HtmlSelector $htmlSelector
     */
    public function __construct(
        protected Query $query,
        protected HtmlSelector $htmlSelector
    ) {
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function current(): Query
    {
        return new Query(
            [$this->query->get($this->position)],
            $this->query->getSelector(),
            $this->htmlSelector
        );
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        $this->seek($this->position + 1);
    }

    /**
     * @inheritDoc
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return $this->query->isset($this->position);
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->query);
    }

    /**
     * @inheritDoc
     */
    public function seek(int $offset): void
    {
        if (!$this->query->isset($this->position)) {
            throw new OutOfBoundsException(sprintf('Invalid seek position (%d)', $offset));
        }

        $this->position = $offset;
    }
}