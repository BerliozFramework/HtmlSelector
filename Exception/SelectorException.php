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

namespace Berlioz\HtmlSelector\Exception;

use Berlioz\HtmlSelector\CssSelector\CssSelector;

class SelectorException extends HtmlSelectorException
{
    /**
     * Unknown pseudo class.
     *
     * @param string $name
     * @param CssSelector $selector
     *
     * @return static
     */
    public static function unknownPseudoClass(string $name, CssSelector $selector): static
    {
        return new static(sprintf('Invalid "%s" in selector "%s"', $name, $selector));
    }
}