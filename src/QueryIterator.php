<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2017 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\HtmlSelector;

/**
 * Class QueryIterator.
 *
 * @package Berlioz\HtmlSelector
 */
class QueryIterator implements \SeekableIterator, \Countable
{
    /** @var int Position */
    private $position;
    /** @var \Berlioz\HtmlSelector\Query Query */
    private $query;

    /**
     * QueryIterator constructor.
     *
     * @param \Berlioz\HtmlSelector\Query $query
     */
    public function __construct(Query $query)
    {
        $this->position = 0;
        $this->query = $query;
    }

    /**
     * Return the current element.
     *
     * @return Query Can return any type.
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     * @link http://php.net/manual/en/iterator.current.php
     */
    public function current()
    {
        return new Query($this->query->get($this->position));
    }

    /**
     * Move forward to next element.
     *
     * @return void
     * @link http://php.net/manual/en/iterator.next.php
     */
    public function next()
    {
        $this->seek($this->position + 1);
    }

    /**
     * Return the key of the current element.
     *
     * @return mixed Scalar on success, or null on failure.
     * @link http://php.net/manual/en/iterator.key.php
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     * @link http://php.net/manual/en/iterator.valid.php
     */
    public function valid()
    {
        return $this->query->isset($this->position);
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @return void Any returned value is ignored.
     * @link http://php.net/manual/en/iterator.rewind.php
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Count elements of an object.
     *
     * @return int
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @link http://php.net/manual/en/countable.count.php
     */
    public function count()
    {
        return count($this->query->get());
    }

    /**
     * Seeks to a position.
     *
     * @param int $position The position to seek to.
     *
     * @return void
     * @link http://php.net/manual/en/seekableiterator.seek.php
     */
    public function seek($position)
    {
        if (!$this->query->isset($this->position)) {
            throw new \OutOfBoundsException(sprintf('Invalid seek position (%d)', $position));
        }

        $this->position = $position;
    }
}