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

namespace Berlioz\HtmlSelector\PseudoClass;

use Berlioz\HtmlSelector\CssSelector\CssSelector;
use Berlioz\HtmlSelector\Exception\SelectorException;

/**
 * Class PseudoClassSet.
 */
class PseudoClassSet
{
    private array $pseudoClasses = [];

    public function __construct(array $pseudoClasses = [])
    {
    }

    /**
     * Add pseudo class.
     *
     * @param PseudoClassInterface ...$pseudoClass
     */
    public function add(PseudoClassInterface ...$pseudoClass)
    {
        array_push($this->pseudoClasses, ...$pseudoClass);
    }

    /**
     * Get pseudo class.
     *
     * @param string $name
     *
     * @return PseudoClassInterface|null
     */
    public function get(string $name): ?PseudoClassInterface
    {
        /** @var PseudoClassInterface $pseudoClass */
        foreach ($this->pseudoClasses as $pseudoClass) {
            if ($pseudoClass->getName() === $name) {
                return $pseudoClass;
            }
        }

        return null;
    }

    /**
     * Build xpath.
     *
     * @param string $xpath
     * @param CssSelector $selector
     *
     * @return string
     * @throws SelectorException
     */
    public function buildXpath(string $xpath, CssSelector $selector): string
    {
        foreach ($selector->getPseudoClasses() as $name => $arguments) {
            if (null === ($pseudoClass = $this->get($name))) {
                throw SelectorException::unknownPseudoClass($name, $selector);
            }

            $xpath = $pseudoClass->buildXpath($xpath, $arguments, $selector);
        }

        return $xpath;
    }
}