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

use Berlioz\HtmlSelector\HtmlSelector;
use Berlioz\HtmlSelector\PseudoClass\PseudoClass;

/**
 * Class QueryExtension.
 */
class QueryExtension implements ExtensionInterface
{
    public function __construct(protected HtmlSelector $htmlSelector)
    {
    }

    public function getPseudoClasses(): array
    {
        return [
            new PseudoClass('button', [$this, 'button']),
            new PseudoClass('checkbox', [$this, 'checkbox']),
            new PseudoClass('contains', [$this, 'contains']),
//            new PseudoClass('count', [$this, 'count']),
            new PseudoClass('eq', [$this, 'eq']),
            new PseudoClass('even', [$this, 'even']),
            new PseudoClass('file', [$this, 'file']),
            new PseudoClass('gt', [$this, 'gt']),
            new PseudoClass('gte', [$this, 'gte']),
            new PseudoClass('header', [$this, 'header']),
            new PseudoClass('image', [$this, 'image']),
            new PseudoClass('input', [$this, 'input']),
            new PseudoClass('last', [$this, 'last']),
            new PseudoClass('lt', [$this, 'lt']),
            new PseudoClass('lte', [$this, 'lte']),
            new PseudoClass('odd', [$this, 'odd']),
            new PseudoClass('parent', [$this, 'parent']),
            new PseudoClass('password', [$this, 'password']),
            new PseudoClass('radio', [$this, 'radio']),
            new PseudoClass('reset', [$this, 'reset']),
            new PseudoClass('selected', [$this, 'selected']),
            new PseudoClass('submit', [$this, 'submit']),
            new PseudoClass('text', [$this, 'text']),
        ];
    }

    public function button(string $xpath): string
    {
        return $xpath . '[( name() = "button" and @type != "submit" ) or ( name() = "input" and @type = "button" )]';
    }

    public function checkbox(string $xpath): string
    {
        return $xpath . '[@type = "checkbox"]';
    }

    public function contains(string $xpath, string $arguments): string
    {
        return $xpath . sprintf('[contains(text(), "%s")]', addslashes($arguments));
    }

    public function eq(string $xpath, string $arguments): string
    {
        if (intval($arguments) >= 0) {
            return sprintf('(%s)[position() = %d]', $xpath, intval($arguments) + 1);
        }

        return sprintf('(%s)[last() - position() = %d]', $xpath, abs(intval($arguments) + 1));
    }

    public function even(string $xpath): string
    {
        return sprintf('(%s)[position() mod 2 != 1]', $xpath);
    }

    public function file(string $xpath): string
    {
        return $xpath . '[@type="file"]';
    }

    public function gt(string $xpath, string $arguments): string
    {
        if (intval($arguments) >= 0) {
            return sprintf('(%s)[position() > %d]', $xpath, intval($arguments) + 1);
        }

        return sprintf('(%s)[last() - position() < %d]', $xpath, abs(intval($arguments) + 1));
    }

    public function gte(string $xpath, string $arguments): string
    {
        if (intval($arguments) >= 0) {
            return sprintf('(%s)[position() >= %d]', $xpath, intval($arguments) + 1);
        }

        return sprintf('(%s)[last() - position() <= %d]', $xpath, abs(intval($arguments) + 1));
    }

    public function header(string $xpath): string
    {
        return $xpath . '[name() = "h1" or name() = "h2" or name() = "h3" or name() = "h4" or name() = "h5" or name() = "h6"]';
    }

    public function image(string $xpath): string
    {
        return $xpath . '[@type="image"]';
    }

    public function input(string $xpath): string
    {
        return $xpath . '[name() = "input" or name() = "textarea" or name() = "select" or name() = "button"]';
    }

    public function last(string $xpath): string
    {
        return sprintf('(%s)[last()]', $xpath);
    }

    public function lt(string $xpath, string $arguments): string
    {
        if (intval($arguments) >= 0) {
            return sprintf('(%s)[position() < %d]', $xpath, intval($arguments) + 1);
        }

        return sprintf('(%s)[last() - position() > %d]', $xpath, abs(intval($arguments) + 1));
    }

    public function lte(string $xpath, string $arguments): string
    {
        if (intval($arguments) >= 0) {
            return sprintf('(%s)[position() <= %d]', $xpath, intval($arguments) + 1);
        }

        return sprintf('(%s)[last() - position() >= %d]', $xpath, abs(intval($arguments) + 1));
    }

    public function odd(string $xpath): string
    {
        return sprintf('(%s)[position() mod 2 = 1]', $xpath);
    }

    public function parent(string $xpath): string
    {
        return $xpath . '[normalize-space()]';
    }

    public function password(string $xpath): string
    {
        return $xpath . '[@type="password"]';
    }

    public function radio(string $xpath): string
    {
        return $xpath . '[@type="radio"]';
    }

    public function reset(string $xpath): string
    {
        return $xpath . '[@type="reset"]';
    }

    public function selected(string $xpath): string
    {
        return $xpath . '[name() = "option" and @selected]';
    }

    public function submit(string $xpath): string
    {
        return $xpath . '[( name() = "button" or name() = "input" ) and @type = "submit"]';
    }

    public function text(string $xpath): string
    {
        return $xpath . '[name() = "input" and ( @type="text" or not( @type ) )]';
    }
}