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

namespace Berlioz\HtmlSelector\CssSelector;

use Countable;

/**
 * Class CssSelectorSet.
 */
class CssSelectorSet implements Countable
{
    protected array $selectors = [];

    public function __construct(CssSelector ...$selector)
    {
        array_push($this->selectors, ...$selector);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->selectors);
    }

    /**
     * __toString() PHP magic method.
     *
     * @return string
     */
    public function __toString(): string
    {
        return implode(', ', array_map(fn(CssSelector $selector) => (string)$selector, $this->selectors));
    }

    /**
     * Get selectors.
     *
     * @return CssSelector[]
     */
    public function all(): array
    {
        return $this->selectors;
    }
}