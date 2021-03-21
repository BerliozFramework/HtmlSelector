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

namespace Berlioz\HtmlSelector\Extension;

use Berlioz\HtmlSelector\CssSelector\CssSelector;
use Berlioz\HtmlSelector\Exception\SelectorException;
use Berlioz\HtmlSelector\HtmlSelector;
use Berlioz\HtmlSelector\PseudoClass\Nth;
use Berlioz\HtmlSelector\PseudoClass\PseudoClass;
use Berlioz\HtmlSelector\XpathSolver;

/**
 * Class CssExtension.
 */
class CssExtension implements ExtensionInterface
{
    public function __construct(protected HtmlSelector $htmlSelector)
    {
    }

    /**
     * @inheritDoc
     */
    public function getPseudoClasses(): array
    {
        return [
            new PseudoClass('any', [$this, 'any']),
            new PseudoClass('any-link', [$this, 'anyLink']),
            new PseudoClass('blank', [$this, 'blank']),
            new PseudoClass('checked', [$this, 'checked']),
            new PseudoClass('dir', [$this, 'dir']),
            new PseudoClass('disabled', [$this, 'disabled']),
            new PseudoClass('empty', [$this, 'empty']),
            new PseudoClass('enabled', [$this, 'enabled']),
            new PseudoClass('first', [$this, 'first']),
            new PseudoClass('first-child', [$this, 'firstChild']),
            new PseudoClass('first-of-type', [$this, 'firstOfType'], true),
            new PseudoClass('has', [$this, 'has']),
            new PseudoClass('lang', [$this, 'lang']),
            new PseudoClass('last-child', [$this, 'lastChild']),
            new PseudoClass('last-of-type', [$this, 'lastOfType'], true),
            new PseudoClass('not', [$this, 'not']),
            new Nth('nth-child', $this->htmlSelector),
            new Nth('nth-last-child', $this->htmlSelector),
            new Nth('nth-of-type', $this->htmlSelector),
            new Nth('nth-last-of-type', $this->htmlSelector),
            new PseudoClass('only-child', [$this, 'onlyChild']),
            new PseudoClass('only-of-type', [$this, 'onlyOfType'], true),
            new PseudoClass('optional', [$this, 'optional']),
            new PseudoClass('read-only', [$this, 'readOnly']),
            new PseudoClass('read-write', [$this, 'readWrite']),
            new PseudoClass('required', [$this, 'required']),
            new PseudoClass('root', [$this, 'root']),
        ];
    }

    /**
     * :any(selector)
     *
     * @param string $xpath
     * @param string $arguments
     *
     * @return string
     * @throws SelectorException
     */
    public function any(string $xpath, string $arguments): string
    {
        $subXpath = $this->htmlSelector->solveXpath($arguments ?? '*', XpathSolver::CONTEXT_SELF);

        return sprintf('%s[%s]', $xpath, $subXpath);
    }

    /**
     * :any-link
     *
     * @param string $xpath
     *
     * @return string
     */
    public function anyLink(string $xpath): string
    {
        return $xpath . '[( name() = "a" or name() = "area" or name() = "link" ) and @href]';
    }

    /**
     * :blank
     *
     * @param string $xpath
     *
     * @return string
     */
    public function blank(string $xpath): string
    {
        return $xpath . '[count(child::*) = 0 and not(normalize-space())]';
    }

    /**
     * :checked
     *
     * @param string $xpath
     *
     * @return string
     */
    public function checked(string $xpath): string
    {
        return $xpath . '[( name() = "input" and ( @type = "checkbox" or @type = "radio" ) and @checked ) or ( name() = "option" and @selected )]';
    }

    /**
     * :dir(...)
     *
     * @param string $xpath
     * @param string $arguments
     *
     * @return string
     */
    public function dir(string $xpath, string $arguments): string
    {
        if (!in_array(trim($arguments), ['ltr', 'rtl'])) {
            return $xpath;
        }

        return $xpath . sprintf('[(ancestor-or-self::*[@dir])[last()][@dir = "%s"]]', trim($arguments));
    }

    /**
     * :disabled
     *
     * @param string $xpath
     *
     * @return string
     */
    public function disabled(string $xpath): string
    {
        return $xpath . '[( name() = "button" or name() = "input" or name() = "optgroup" or name() = "option" or name() = "select" or name() = "textarea" or name() = "menuitem" or name() = "fieldset" ) and @disabled]';
    }

    /**
     * :empty
     *
     * @param string $xpath
     *
     * @return string
     */
    public function empty(string $xpath): string
    {
        return $xpath . '[count(child::*) = 0]';
    }

    /**
     * :enabled
     *
     * @param string $xpath
     *
     * @return string
     */
    public function enabled(string $xpath): string
    {
        return $xpath . '[( name() = "button" or name() = "input" or name() = "optgroup" or name() = "option" or name() = "select" or name() = "textarea" ) and not( @disabled )]';
    }

    /**
     * :first
     *
     * @param string $xpath
     *
     * @return string
     */
    public function first(string $xpath): string
    {
        return sprintf('(%s)[1]', $xpath);
    }

    /**
     * :first-child
     *
     * @param string $xpath
     *
     * @return string
     */
    public function firstChild(string $xpath): string
    {
        return $xpath . '[../*[1] = node()]';
    }

    /**
     * :first-of-type(...)
     *
     * @param string $xpath
     * @param CssSelector $selector
     *
     * @return string
     * @throws SelectorException
     */
    public function firstOfType(string $xpath, CssSelector $selector): string
    {
        if (null !== $selector->getType() && '*' !== $selector->getType()) {
            return $xpath . '[last()]';
        }

        throw new SelectorException('"*:first-of-type" isn\'t implemented');
    }

    /**
     * :has(selector)
     *
     * @param string $xpath
     * @param string $arguments
     *
     * @return string
     * @throws SelectorException
     */
    public function has(string $xpath, string $arguments): string
    {
        $subXpath = $this->htmlSelector->solveXpath($arguments ?? '*', XpathSolver::CONTEXT_CHILD);

        return sprintf('%s[%s]', $xpath, $subXpath);
    }

    /**
     * :lang(...)
     *
     * @param string $xpath
     * @param string $arguments
     *
     * @return string
     */
    public function lang(string $xpath, string $arguments): string
    {
        return $xpath . sprintf('[@lang = "%1$s" or starts-with(@lang, "%1$s")]', addslashes($arguments));
    }

    /**
     * :last-child
     *
     * @param string $xpath
     *
     * @return string
     */
    public function lastChild(string $xpath): string
    {
        return $xpath . '[../*[last()] = node()]';
    }

    /**
     * :last-of-type(...)
     *
     * @param string $xpath
     * @param CssSelector $selector
     *
     * @return string
     * @throws SelectorException
     */
    public function lastOfType(string $xpath, CssSelector $selector): string
    {
        if (null !== $selector->getType() && '*' !== $selector->getType()) {
            return $xpath . '[last()]';
        }

        throw new SelectorException('"*:last-of-type" isn\'t implemented');
    }

    /**
     * :only-child
     *
     * @param string $xpath
     *
     * @return string
     */
    public function onlyChild(string $xpath): string
    {
        return $xpath . '[last() = 1]';
    }

    /**
     * :only-of-type
     *
     * @param string $xpath
     * @param CssSelector $selector
     *
     * @return string
     */
    public function onlyOfType(string $xpath, CssSelector $selector): string
    {
        return $xpath . sprintf('[count(../%s)=1]', $selector->getType() ?? '*');
    }

    /**
     * :optional
     *
     * @param string $xpath
     *
     * @return string
     */
    public function optional(string $xpath): string
    {
        return $xpath . '[name() = "input" or name() = "textarea" or name() = "select"][not( @required )]';
    }

    /**
     * :read-only
     *
     * @param string $xpath
     *
     * @return string
     */
    public function readOnly(string $xpath): string
    {
        return $xpath .
            '[( not(@contenteditable) or @contenteditable = "false" ) and ' .
            ' not( ( name() = "input" or name() = "textarea" or name() = "select" ) and not(@readonly) and not(@disabled) )]';
    }

    /**
     * :read-write
     *
     * @param string $xpath
     *
     * @return string
     */
    public function readWrite(string $xpath): string
    {
        return $xpath .
            '[( @contenteditable and ( @contenteditable = "true" or not(normalize-space(@contenteditable)) ) ) or ' .
            ' ( ( name() = "input" or name() = "textarea" or name() = "select" ) and not(@readonly) and not(@disabled) )]';
    }

    /**
     * :required
     *
     * @param string $xpath
     *
     * @return string
     */
    public function required(string $xpath): string
    {
        return $xpath . '[name() = "input" or name() = "textarea" or name() = "select"][@required]';
    }

    /**
     * :root
     *
     * @param string $xpath
     *
     * @return string
     */
    public function root(string $xpath): string
    {
        return sprintf('(%s/ancestor::*)[1]/*[1]', $xpath);
    }

    public function not(string $xpath, string $arguments): string
    {
        $subXpath = $this->htmlSelector->solveXpath($arguments ?? '*', XpathSolver::CONTEXT_SELF);

        return sprintf('%s[not(%s)]', $xpath, $subXpath);
    }
}