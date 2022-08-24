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
use Berlioz\HtmlSelector\CssSelector\CssSelectorParser;
use Berlioz\HtmlSelector\Exception\SelectorException;
use Berlioz\HtmlSelector\HtmlSelector;
use Berlioz\HtmlSelector\XpathSolver;

class Nth implements PseudoClassInterface
{
    public function __construct(
        protected string $name,
        protected HtmlSelector $htmlSelector
    ) {
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
     * @throws SelectorException
     */
    public function buildXpath(string $xpath, ?string $arguments, CssSelector $selector): string
    {
        $arguments = $this->parseArguments($arguments);

        if ($this->isOfType()) {
            $xpath .= '/../*';
        }

        // Has selector
        if ($arguments['selector']) {
            $xpath = sprintf(
                '%s[%s]',
                $xpath,
                $this->htmlSelector->solveXpath($arguments['selector'], XpathSolver::CONTEXT_SELF)
            );
        }

        $xpath = $this->getExpression($xpath, $arguments);

        if (false === $this->isOfType()) {
            if (null !== $selector->getType() && $selector->getType() != '*') {
                $xpath = sprintf('%s[name() = "%s"]', $xpath, $selector->getType());
            }
        }

        return $xpath;
    }

    /**
     * Parse arguments.
     *
     * @param string $arguments
     *
     * @return array
     * @throws SelectorException
     */
    protected function parseArguments(string $arguments): array
    {
        // Regex
        $regex = '~' .
            CssSelectorParser::REGEX_DECLARATIONS .
            "^ \s* (?: (?<value_oddEven> odd | even ) | (?<value_a> [-+]? \d+ )? \s* n \s* (?<value_b> [-+] \s* \d+ )? | (?<value_d> [-|+]? \d+ ) ) ( \s+ of \s+ (?<selector> \g<d_selector> ) )? \s* $" .
            "~x";
        $matches = [];

        if (1 !== preg_match($regex, $arguments, $matches, PREG_UNMATCHED_AS_NULL)) {
            throw new SelectorException(sprintf('Bad syntax "%s" for :%s', $arguments, $this->name));
        }

        return $matches;
    }

    /**
     * Is NTH of type.
     *
     * @return bool
     */
    protected function isOfType(): bool
    {
        return false !== stripos($this->name, 'type');
    }

    /**
     * Is NTH last.
     *
     * @return bool
     */
    protected function isLast(): bool
    {
        return false !== stripos($this->name, 'last');
    }

    /**
     * Treat expression.
     *
     * @param string $xpath
     * @param array $arguments
     *
     * @return string
     */
    protected function getExpression(string $xpath, array $arguments): string
    {
        if (isset($arguments['value_oddEven'])) {
            if ($arguments['value_oddEven'] == 'odd') {
                if ($this->isLast()) {
                    return $xpath . '[(last() - position() + 1) mod 2 = 1]';
                }

                return $xpath . '[position() mod 2 = 1]';
            }

            if ($this->isLast()) {
                return $xpath . '[(last() - position() + 1) mod 2 = 0]';
            }

            return $xpath . '[position() mod 2 = 0]';
        }

        if (isset($arguments['value_d'])) {
            return $xpath . sprintf('[%d]', intval($arguments['value_d']) - 1);
        }

        $nth_val_a = isset($arguments['value_a']) && is_numeric($arguments['value_a']) ? intval(
            $arguments['value_a']
        ) : 1;
        $nth_val_b = isset($arguments['value_b']) ? intval($arguments['value_b']) : 0;

        if ($nth_val_a >= 0) {
            if ($this->isLast()) {
                $xpath = sprintf('%s[(last() - position() + 1) > %d]', $xpath, $nth_val_b - $nth_val_a);
            } else {
                $xpath = sprintf('%s[position() > %d]', $xpath, $nth_val_b - $nth_val_a);
            }

            if ($nth_val_a > 0) {
                if ($this->isLast()) {
                    return sprintf('(%s)[(position() - %d) mod %d = 0]', $xpath, $nth_val_b, $nth_val_a);
                }

                return sprintf('(%s)[((last() - position() + 1) - %d) mod %d = 0]', $xpath, $nth_val_b, $nth_val_a);
            }

            return $xpath;
        }


        if (!$this->isLast()) {
            $xpath = sprintf('%s[position() <= %d]', $xpath, $nth_val_b);

            return sprintf('(%s)[(last() - position()) mod %d = 0]', $xpath, abs($nth_val_a));
        }

        $xpath = sprintf('%s[(last() - position() + 1) <= %d]', $xpath, $nth_val_b);

        return sprintf('(%s)[(last() - (last() - position() + 1)) mod %d = 0]', $xpath, abs($nth_val_a));
    }
}