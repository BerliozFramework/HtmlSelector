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

/**
 * Class PseudoClass.
 */
class PseudoClass implements PseudoClassInterface
{
    protected $callback;

    public function __construct(
        protected string $name,
        callable $callback,
        protected bool $withSelector = false
    ) {
        $this->callback = $callback;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function buildXpath(string $xpath, ?string $arguments, CssSelector $selector): string
    {
        $args = array_filter(
            [
                'xpath' => $xpath,
                'arguments' => $arguments,
            ],
            fn($value) => null !== $value
        );

        if (true === $this->withSelector) {
            $args['selector'] = $selector;
        }

        return call_user_func_array($this->callback, $args);
    }
}