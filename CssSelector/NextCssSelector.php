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

class NextCssSelector
{
    public function __construct(
        protected CssSelector $selector,
        protected ?string $predecessor
    ) {
    }

    /**
     * Get selector.
     *
     * @return CssSelector
     */
    public function getSelector(): CssSelector
    {
        return $this->selector;
    }

    /**
     * Get predecessor.
     *
     * @return string|null
     */
    public function getPredecessor(): ?string
    {
        return $this->predecessor;
    }
}