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

/**
 * Class CssSelectorParser.
 */
class CssSelectorParser
{
    public const REGEX_DECLARATIONS = <<<'EOD'
(?(DEFINE)
    (?<d_quotes> '(?>[^'\\]++|\\.)*' | "(?>[^"\\]++|\\.)*" )

    (?<d_type> \w+ | \* )
    (?<d_id> \#(?:[\w\-]+) )
    (?<d_class> \.(?:[\w\-]+) )
    (?<d_classes> \g<d_class>+ )
    (?<d_attribute> \[ \s* [\w\-]+ (?: \s* (?: = | \^= | \$= | \*= | != | \~= | \|= ) \s* (\g<d_quotes>|[^\]]+))? \s* \] )
    (?<d_attributes> \g<d_attribute>+ )
    (?<d_filter> :([\w\-]+ (?: \( \s* (\g<d_quotes> | \g<d_selectors> | [^)]*) \s* \) )? ) )
    (?<d_filters> \g<d_filter>+ )

    (?<d_expression> \g<d_type>? \g<d_id>? \g<d_classes>? \g<d_attributes>? \g<d_filters>? )
    (?<d_selector> \g<d_expression> \s* ( \s* ([+>\~] | >> )? \s* \g<d_expression> )* )
    (?<d_selectors> \g<d_selector> \s* ( , \s* \g<d_selector> )* )
)
EOD;

    /**
     * Parse.
     *
     * @param string $selector
     *
     * @return CssSelectorSet
     */
    public function parse(string $selector): CssSelectorSet
    {
        return $this->parseSelectors($selector);
    }

    /**
     * Parse selectors from a multiple selector.
     *
     * Like ".class, .class2[attribute]" > 2 selectors: ".class" and ".class2[attribute]".
     *
     * @param string $selector
     *
     * @return CssSelectorSet
     */
    private function parseSelectors(string $selector): CssSelectorSet
    {
        $selectors = [];

        // Regex
        $regex =
            '~' .
            static::REGEX_DECLARATIONS .
            '(?<selector> \g<d_selector> )' .
            '~xis';

        $matches = [];
        if (false !== preg_match_all($regex, $selector, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL)) {
            $matches = array_filter(array_column($matches, 'selector'));

            foreach ($matches as $expression) {
                $selectors[] = $this->parseExpressions($expression);
            }
        }

        $selectors = array_filter($selectors);

        return new CssSelectorSet(...$selectors);
    }

    /**
     * Parse expressions from a selector.
     *
     * Like ".class[attribute] .class2" > 2 expressions: ".class[attribute]" and ".class2".
     *
     * @param string $selector
     *
     * @return CssSelector|null
     */
    private function parseExpressions(string $selector): ?CssSelector
    {
        $expressions = [];

        // Regex
        $regex =
            '~' .
            static::REGEX_DECLARATIONS .
            '(?<predecessor> [+>\~] | >> )? \s* (?<expression> \g<d_expression> )' .
            '~xis';

        $matches = [];
        if (false !== preg_match_all($regex, $selector, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL)) {
            $lastExpression = null;

            foreach ($matches as $match) {
                if (empty(trim($match[0]))) {
                    continue;
                }

                $expression = $this->parseExpression($match['expression']);

                if ($lastExpression) {
                    $lastExpression->setNext(
                        new NextCssSelector(
                            selector: $expression,
                            predecessor: $match['predecessor'] ?? null
                        )
                    );
                }

                $expressions[] = $lastExpression = $expression;
            }
        }

        return reset($expressions) ?? null;
    }

    /**
     * Parse expression into parameters.
     *
     * Example of result for expression "select#toto.class.class2[attribute1][attribute2^="value"]:disabled:eq(1)":
     *     ['type'       => 'select',
     *      'id'         => 'toto',
     *      'classes'    => ['class', 'class2'],
     *      'attributes' => [['name'       => 'attribute1',
     *                        'comparison' => null,
     *                        'value'      => null],
     *                       ['name'       => 'attribute2',
     *                        'comparison' => '^=',
     *                        'value'      => 'value']]],
     *      'filters'    => ['disabled' => null,
     *                       'eq'       => '1']]
     *
     * @param string $expression
     *
     * @return CssSelector
     */
    private function parseExpression(string $expression): CssSelector
    {
        $regex =
            '~' .
            static::REGEX_DECLARATIONS .
            '^ \s* (?<type> \g<d_type>)? (?<id> \g<d_id>)? (?<classes> \g<d_classes>)? (?<attributes> \g<d_attributes>)? (?<filters> \g<d_filters>)? \s* $' .
            '~xis';

        $match = [];
        if (1 !== preg_match($regex, $expression, $match, PREG_UNMATCHED_AS_NULL)) {
            return new CssSelector($expression);
        }

        // Classes
        {
            $classes = [];

            if (!empty($match['classes'])) {
                $regexClass =
                    '~' .
                    static::REGEX_DECLARATIONS .
                    '\.(?<class> [\w\-]+ )' .
                    '~xis';

                $matchesClass = [];
                if (preg_match_all(
                    $regexClass,
                    $match['classes'],
                    $matchesClass,
                    PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL
                )) {
                    foreach ($matchesClass as $matchClass) {
                        $classes[] = $matchClass['class'];
                    }
                }
            }
        }

        // Attributes
        {
            $attributes = [];

            if (!empty($match['attributes'])) {
                $regexAttribute =
                    '~' .
                    static::REGEX_DECLARATIONS .
                    '\[ \s* (?<name> [\w\-]+ ) (?: \s* (?<comparison> = | \^= | \$= | \*= | != | \~= | \|= ) \s* (?: (?<quotes> \g<d_quotes>) | (?<value> [^\]]+) ) )? \s* \]' .
                    '~xis';

                $matchesAttribute = [];
                if (preg_match_all(
                    $regexAttribute,
                    $match['attributes'],
                    $matchesAttribute,
                    PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL
                )) {
                    foreach ($matchesAttribute as $matchAttribute) {
                        $attributes[] = [
                            'name' => $matchAttribute['name'],
                            'comparison' => $matchAttribute['comparison'] ?? null,
                            'value' =>
                                !empty($matchAttribute['quotes']) ?
                                    stripslashes(substr($matchAttribute['quotes'], 1, -1)) :
                                    (!empty($matchAttribute['value']) ?
                                        $matchAttribute['value'] :
                                        null)
                        ];
                    }
                }
            }
        }

        // Filters
        {
            $filters = [];

            if (!empty($match['filters'])) {
                $regexFilter =
                    '~' .
                    static::REGEX_DECLARATIONS .
                    ':(:? (?<name> [\w\-]+ ) (?: \(  \s* (?<value> \g<d_quotes> | \g<d_selectors> | [^)]*) \s* \) )? )' .
                    '~xis';

                $matchesFilter = [];
                if (preg_match_all(
                    $regexFilter,
                    $match['filters'],
                    $matchesFilter,
                    PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL
                )) {
                    foreach ($matchesFilter as $matchFilter) {
                        $filters[$matchFilter['name']] = $matchFilter['value'] ?? null;
                    }
                }
            }
        }

        // Definition
        return
            new CssSelector(
                $expression,
                type: $match['type'] ?? null,
                id: isset($match['id']) ? substr($match['id'], 1) : null,
                classes: $classes,
                attributes: $attributes,
                pseudoClasses: $filters,
            );
    }
}