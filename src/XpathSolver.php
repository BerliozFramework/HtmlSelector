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

namespace Berlioz\HtmlSelector;

use Berlioz\HtmlSelector\CssSelector\CssSelector;
use Berlioz\HtmlSelector\CssSelector\CssSelectorParser;
use Berlioz\HtmlSelector\CssSelector\CssSelectorSet;
use Berlioz\HtmlSelector\Exception\SelectorException;
use Berlioz\HtmlSelector\PseudoClass\PseudoClassSet;

/**
 * Class XpathSolver.
 */
class XpathSolver
{
    public const CONTEXT_ROOT = '//';
    public const CONTEXT_ALL = './/';
    public const CONTEXT_CHILD = './';
    public const CONTEXT_SELF = 'self::';
    public const CONTEXT_PARENTS = 'ancestor::';
    public const CONTEXT_NEXT = 'following-sibling::*[1]/self::';
    public const CONTEXT_NEXT_ALL = 'following-sibling::';
    public const CONTEXT_PREV = 'preceding-sibling::*[last()]/self::';
    public const CONTEXT_PREV_ALL = 'preceding-sibling::';

    protected CssSelectorParser $parser;

    public function __construct(protected PseudoClassSet $pseudoClasses)
    {
        $this->parser = new CssSelectorParser();
    }

    /**
     * Handle.
     *
     * @param string $selector
     * @param string|null $context
     *
     * @return string
     * @throws SelectorException
     */
    public function solve(string $selector, ?string $context = self::CONTEXT_ALL): string
    {
        $selector = $this->parser->parse($selector);

        return $this->solveMultiple($selector, $context);
    }

    /**
     * Solve multiple.
     *
     * @param CssSelectorSet $selectors
     * @param string|null $context
     *
     * @return string
     * @throws SelectorException
     */
    protected function solveMultiple(CssSelectorSet $selectors, ?string $context = self::CONTEXT_ALL): string
    {
        $xpaths = array_map(fn(CssSelector $selector) => $this->solveUnique($selector, $context), $selectors->all());
        $xpaths = array_filter($xpaths);

        return implode(' | ', $xpaths);
    }

    /**
     * Solve a selector.
     *
     * @param CssSelector $selector
     * @param string|null $context
     *
     * @return string
     * @throws SelectorException
     */
    protected function solveUnique(CssSelector $selector, ?string $context = self::CONTEXT_ALL): string
    {
        // Type
        $xpath = ($context ?? '') . ($selector->getType() ?: '*');

        // ID
        if (null !== $selector->getId()) {
            $xpath .= '[@id="' . addslashes($selector->getId()) . '"]';
        }

        // Classes
        foreach ($selector->getClasses() as $class) {
            $xpath .= '[contains(concat(" ", @class, " "), " ' . addslashes($class) . ' ")]';
        }

        // Attributes
        foreach ($selector->getAttributes() as $attribute) {
            $xpath .= match ($attribute['comparison']) {
                '=' => sprintf('[@%s="%s"]', $attribute['name'], addslashes($attribute['value'])),
                '^=' => sprintf('[starts-with(@%s, "%s")]', $attribute['name'], addslashes($attribute['value'])),
                '$=' => sprintf(
                    '["%2$s" = substring(@%1$s, string-length(@%1$s) - string-length("%2$s") + 1)]',
                    $attribute['name'],
                    addslashes($attribute['value'])
                ),
                '*=' => sprintf('[contains(@%s, "%s")]', $attribute['name'], addslashes($attribute['value'])),
                '!=' => sprintf('[@%s!="%s"]', $attribute['name'], addslashes($attribute['value'])),
                '~=' => sprintf(
                    '[contains(concat(" ", @%s, " "), " %s ")]',
                    $attribute['name'],
                    addslashes($attribute['value'])
                ),
                '|=' => sprintf(
                    '[@%1$s = "%2$s" or starts-with(@%1$s, "%2$s")]',
                    $attribute['name'],
                    addslashes($attribute['value'])
                ),
                default => sprintf('[@%s]', $attribute['name']),
            };
        }

        // Pseudo classes
        $xpath = $this->pseudoClasses->buildXpath($xpath, $selector);

        // Next?
        if (null !== ($next = $selector->getNext())) {
            $xpath .= match ($next->getPredecessor()) {
                '>' => '/',
                '+' => '/following-sibling::*[1]/self::',
                '~' => '/following-sibling::',
                default => '//',
            };
            $xpath = $this->solveUnique($next->getSelector(), $xpath);
        }

        return $xpath;
    }
}