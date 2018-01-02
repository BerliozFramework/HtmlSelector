<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2017 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\HtmlSelector;


use Berlioz\HtmlSelector\Exception\SelectorException;

/**
 * Class Selector.
 *
 * @package Berlioz\HtmlSelector
 */
class Selector
{
    /** Context definitions */
    const CONTEXT_ROOT = 0;
    const CONTEXT_ALL = 1;
    const CONTEXT_CHILD = 2;
    const CONTEXT_SELF = 3;
    const CONTEXT_PARENTS = 4;
    const CONTEXT_NEXT = 5;
    const CONTEXT_NEXT_ALL = 6;
    const CONTEXT_PREV = 7;
    const CONTEXT_PREV_ALL = 8;
    /** Regex declarations */
    const REGEX_DECLARATIONS = <<<'EOD'
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
    /** @var string Selector */
    private $selector;
    /** @var string Xpath */
    private $xpath;

    /**
     * Selector constructor.
     *
     * @param string $selector
     *
     * @throws \InvalidArgumentException if it's an invalid selector.
     */
    public function __construct(string $selector)
    {
        // Check selector
        $regex = "~" .
                 static::REGEX_DECLARATIONS .
                 "^ \g<d_selectors> $" .
                 "~xis";

        if (preg_match($regex, $selector) == 1) {
            $this->selector = $selector;
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid selector "%s" format', $selector));
        }
    }

    /**
     * __toString() magic method.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->selector;
    }

    /**
     * Extract selectors from a multiple selector.
     *
     * Like ".class, .class2[attribute]" > 2 selectors: ".class" and ".class2[attribute]".
     *
     * @param string $pSelector
     *
     * @return array
     */
    private function extractSelectors(string $pSelector): array
    {
        $selectors = [];

        // Regex
        $regex =
            '~' .
            static::REGEX_DECLARATIONS .
            '(?<selector> \g<d_selector> )' .
            '~xis';

        $matches = [];
        if (preg_match_all($regex, $pSelector, $matches, PREG_SET_ORDER) !== false) {
            $matches = array_filter(array_column($matches, 'selector'));

            foreach ($matches as $selector) {
                $selectors[] = $this->extractExpressions($selector);
            }
        }

        return $selectors;
    }

    /**
     * Extract expressions from a selector.
     *
     * Like ".class[attribute] .class2" > 2 expressions: ".class[attribute]" and ".class2".
     *
     * @param string $selector
     *
     * @return array
     */
    private function extractExpressions(string $selector): array
    {
        $expressions = [];

        // Regex
        $regex =
            '~' .
            static::REGEX_DECLARATIONS .
            '(?<predecessor> [+>\~] | >> )? \s* (?<expression> \g<d_expression> )' .
            '~xis';

        $matches = [];
        if (preg_match_all($regex, $selector, $matches, PREG_SET_ORDER) !== false) {
            foreach ($matches as $match) {
                if (!empty($match[0])) {
                    if (!empty($expression = $this->extractExpression($match['expression']))) {
                        $expression['predecessor'] = $match['predecessor'];

                        $expressions[] = $expression;
                    }
                }
            }
        }

        return $expressions;
    }

    /**
     * Extract expression into parameters.
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
     * @return array
     */
    private function extractExpression(string $expression): array
    {
        $expressionDef = [];
        $regex =
            '~' .
            static::REGEX_DECLARATIONS .
            '^ \s* (?<type> \g<d_type>)? (?<id> \g<d_id>)? (?<classes> \g<d_classes>)? (?<attributes> \g<d_attributes>)? (?<filters> \g<d_filters>)? \s* $' .
            '~xis';

        $match = [];
        if (preg_match($regex, $expression, $match) !== false) {
            if (!empty($match[0])) {
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
                        if (preg_match_all($regexClass, $match['classes'], $matchesClass, PREG_SET_ORDER)) {
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
                        if (preg_match_all($regexAttribute, $match['attributes'], $matchesAttribute, PREG_SET_ORDER)) {
                            foreach ($matchesAttribute as $matchAttribute) {
                                $attributes[] = ['name'       => $matchAttribute['name'],
                                                 'comparison' => $matchAttribute['comparison'] ?? null,
                                                 'value'      =>
                                                     !empty($matchAttribute['quotes']) ?
                                                         stripslashes(substr($matchAttribute['quotes'], 1, -1)) :
                                                         (!empty($matchAttribute['value']) ?
                                                             $matchAttribute['value'] :
                                                             null)];
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
                        if (preg_match_all($regexFilter, $match['filters'], $matchesFilter, PREG_SET_ORDER)) {
                            foreach ($matchesFilter as $matchFilter) {
                                $filters[$matchFilter['name']] = $matchFilter['value'] ?? null;
                            }
                        }
                    }
                }

                // Definition
                $expressionDef = ['type'       => $match['type'] ?? null,
                                  'id'         => isset($match['id']) ? substr($match['id'], 1) : null,
                                  'classes'    => $classes,
                                  'attributes' => $attributes,
                                  'filters'    => $filters];
            }
        }

        return $expressionDef;
    }

    /**
     * Convert selector to an xpath selector.
     *
     * "%CONTEXT%" special variable is inserted in Xpath selector to define context.
     * She will be replaced by the good context like './/' for all children, referred to the class constants.
     *
     * Not implemented CSS pseudo classes:
     *     - :default
     *     - :fullscreen
     *     - :focus
     *     - :hover
     *     - :in-range
     *     - :indeterminate
     *     - :invalid
     *     - :left
     *     - :link
     *     - :matches()
     *     - :nth-column()
     *     - :nth-last-column()
     *     - :out-of-range
     *     - :right
     *     - :root
     *     - :scope
     *     - :target
     *     - :valid
     *     - :visited
     *
     * @param string $pSelector
     *
     * @return string
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException if a filter hasn't good value.
     *
     * @link http://erwy.developpez.com/tutoriels/xml/xpath-langage-selection-xml/
     * @link http://erwy.developpez.com/tutoriels/xml/xpath-liste-fonctions/
     */
    private function xpathConversion(string $pSelector)
    {
        $xpath = '';

        $iSelector = 0;
        foreach ($this->extractSelectors($pSelector) as $selector) {
            $anXpath = '%CONTEXT%';

            $iExpression = 0;
            foreach ($selector as $expression) {
                // Predecessor
                if ($iExpression > 0) {
                    switch ($expression['predecessor']) {
                        case '>':
                            $anXpath .= '/';
                            break;
                        case '+':
                            $anXpath .= '/following-sibling::*[1]/self::';
                            break;
                        case '~':
                            $anXpath .= '/following-sibling::';
                            break;
                        default:
                            $anXpath .= '//';
                    }
                }

                // Type
                $expression['type'] = !empty($expression['type']) ? $expression['type'] : '*';
                $anXpath .= $expression['type'];

                // ID
                if (!empty($expression['id'])) {
                    $anXpath .= '[@id="' . addslashes($expression['id']) . '"]';
                }

                // Classes
                foreach ($expression['classes'] as $class) {
                    $anXpath .= '[contains(concat(" ", @class, " "), " ' . addslashes($class) . ' ")]';
                }

                // Attributes
                foreach ($expression['attributes'] as $attribute) {
                    switch ($attribute['comparison']) {
                        case '=':
                            $anXpath .= '[@' . $attribute['name'] . '="' . addslashes($attribute['value']) . '"]';
                            break;
                        case '^=':
                            $anXpath .= '[starts-with(@' . $attribute['name'] . ', "' . addslashes($attribute['value']) . '")]';
                            break;
                        case '$=':
                            $anXpath .= '[\'' . addslashes($attribute['value']) . '\' = substring(@' . $attribute['name'] . ', string-length(@' . $attribute['name'] . ') - string-length(\'' . addslashes($attribute['value']) . '\') +1)]';
                            break;
                        case '*=':
                            $anXpath .= '[contains(@' . $attribute['name'] . ', "' . addslashes($attribute['value']) . '")]';
                            break;
                        case '!=':
                            $anXpath .= '[@' . $attribute['name'] . '!="' . addslashes($attribute['value']) . '"]';
                            break;
                        case '~=':
                            $anXpath .= sprintf('[contains(concat(" ", @%s, " "), " %s ")]', $attribute['name'], addslashes($attribute['value']));
                            break;
                        case '|=':
                            $anXpath .= sprintf('[@%1$s = \'%2$s\' or starts-with(@%1$s, \'%2$s\')]', $attribute['name'], addslashes($attribute['value']));
                            break;
                        default:
                            $anXpath .= '[@' . $attribute['name'] . ']';
                    }
                }

                // Filters
                foreach ($expression['filters'] as $filterName => $filter) {
                    switch ($filterName) {
                        // CSS Pseudo Classes
                        case 'any':
                            $subSelector = new Selector($filter ?? '*');
                            $anXpath .= sprintf('[%s]', $subSelector->xpath(Selector::CONTEXT_SELF));
                            break;
                        case 'any-link':
                            $anXpath .= '[( name() = "a" or name() = "area" or name() = "link" ) and @href]';
                            break;
                        case 'blank':
                            $anXpath .= '[count(child::*) = 0 and not(normalize-space())]';
                            break;
                        case 'checked':
                            $anXpath .= '[( name() = "input" and ( @type = "checkbox" or @type = "radio" ) and @checked ) or ( name() = "option" and @selected )]';
                            break;
                        case 'dir':
                            if (in_array(trim($filter), ['ltr', 'rtl'])) {
                                $anXpath .= sprintf('[(ancestor-or-self::*[@dir])[last()][@dir = "%s"]]', trim($filter));
                            }
                            break;
                        case 'disabled':
                            $anXpath .= '[( name() = "button" or name() = "input" or name() = "optgroup" or name() = "option" or name() = "select" or name() = "textarea" or name() = "menuitem" or name() = "fieldset" ) and @disabled]';
                            break;
                        case 'empty':
                            $anXpath .= '[count(child::*) = 0]';
                            break;
                        case 'enabled':
                            $anXpath .= '[( name() = "button" or name() = "input" or name() = "optgroup" or name() = "option" or name() = "select" or name() = "textarea" ) and not( @disabled )]';
                            break;
                        case 'first':
                            $anXpath = sprintf('(%s)[1]', $anXpath);
                            break;
                        case 'first-child':
                            $anXpath .= '[../*[1] = node()]';
                            break;
                        case 'first-of-type':
                            if ($expression['type'] != '*') {
                                $anXpath .= '[1]';
                            } else {
                                throw new SelectorException('"*:first-of-type" isn\'t implemented');
                            }
                            break;
                        case 'has':
                            $subSelector = new Selector($filter ?? '*');
                            $anXpath .= sprintf('[%s]', $subSelector->xpath(Selector::CONTEXT_CHILD));
                            break;
                        case 'lang':
                            $anXpath .= sprintf('[@lang = \'%1$s\' or starts-with(@lang, \'%1$s\')]', addslashes($filter));
                            break;
                        case 'last-child':
                            $anXpath .= '[../*[last()] = node()]';
                            break;
                        case 'last-of-type':
                            if ($expression['type'] != '*') {
                                $anXpath .= '[last()]';
                            } else {
                                throw new SelectorException('"*:last-of-type" isn\'t implemented');
                            }
                            break;
                        case 'not':
                            $subSelector = new Selector($filter ?? '*');
                            $anXpath .= sprintf('[not(%s)]', $subSelector->xpath(Selector::CONTEXT_SELF));
                            break;
                        case 'nth-child':
                        case 'nth-last-child':
                        case 'nth-of-type':
                        case 'nth-last-of-type':
                            //$filter = preg_replace("/\s+/", '', $filter);
                            $nth_type = in_array($filterName, ['nth-of-type', 'nth-last-of-type']);
                            $nth_last = in_array($filterName, ['nth-last-of-type', 'nth-last-child']);

                            // Not implemented ?
                            if ($nth_type && $expression['type'] == '*') {
                                throw new SelectorException(sprintf('"*:%s" isn\'t implemented', $nth_last ? 'nth-last-of-type' : 'nth-of-type'));
                            }

                            // Regex
                            $nth_regex = '~' .
                                         static::REGEX_DECLARATIONS .
                                         "^ \s* (?: (?<value_oddEven> odd | even ) | (?<value_a> [-+]? \d+ )? \s* n \s* (?<value_b> [-+] \s* \d+ )? | (?<value_d> [-|+]? \d+ ) ) ( \s+ of \s+ (?<selector> \g<d_selector> ) )? \s* $" .
                                         "~x";
                            $nth_matches = [];

                            if (preg_match($nth_regex, $filter, $nth_matches)) {
                                if ($nth_type === false) {
                                    $anXpath .= '/../*';
                                }

                                // Selector ?
                                if (!empty($nth_matches['selector'])) {
                                    $subSelector = new Selector($nth_matches['selector'] ?? '*');
                                    $anXpath .= sprintf('[%s]', $subSelector->xpath(Selector::CONTEXT_SELF));
                                }

                                if (isset($nth_matches['value_oddEven']) && $nth_matches['value_oddEven'] == 'odd') {
                                    if (!$nth_last) {
                                        $anXpath .= '[position() mod 2 = 1]';
                                    } else {
                                        $anXpath .= '[(last() - position() + 1) mod 2 = 1]';
                                    }
                                } else {
                                    if (isset($nth_matches['value_oddEven']) && $nth_matches['value_oddEven'] == 'even') {
                                        if (!$nth_last) {
                                            $anXpath .= '[position() mod 2 = 0]';
                                        } else {
                                            $anXpath .= '[(last() - position() + 1) mod 2 = 0]';
                                        }
                                    } else {
                                        if (isset($nth_matches['value_d']) && is_numeric($nth_matches['value_d'])) {
                                            $anXpath .= sprintf('[%d]', intval($nth_matches['value_d']) - 1);
                                        } else {
                                            $nth_val_a = isset($nth_matches['value_a']) && is_numeric($nth_matches['value_a']) ? intval($nth_matches['value_a']) : 1;
                                            $nth_val_b = isset($nth_matches['value_b']) ? intval($nth_matches['value_b']) : 0;

                                            if ($nth_val_a >= 0) {
                                                if (!$nth_last) {
                                                    $anXpath = sprintf('%s[position() > %d]', $anXpath, $nth_val_b - $nth_val_a);
                                                } else {
                                                    $anXpath = sprintf('%s[(last() - position() + 1) > %d]', $anXpath, $nth_val_b - $nth_val_a);
                                                }

                                                if ($nth_val_a > 0) {
                                                    if (!$nth_last) {
                                                        $anXpath = sprintf('(%s)[(position() - %d) mod %d = 0]', $anXpath, $nth_val_b, $nth_val_a);
                                                    } else {
                                                        $anXpath = sprintf('(%s)[((last() - position() + 1) - %d) mod %d = 0]', $anXpath, $nth_val_b, $nth_val_a);
                                                    }
                                                }
                                            } else {
                                                if (!$nth_last) {
                                                    $anXpath = sprintf('%s[position() <= %d]', $anXpath, $nth_val_b);
                                                    $anXpath = sprintf('(%s)[(last() - position()) mod %d = 0]', $anXpath, abs($nth_val_a));
                                                } else {
                                                    $anXpath = sprintf('%s[(last() - position() + 1) <= %d]', $anXpath, $nth_val_b);
                                                    $anXpath = sprintf('(%s)[(last() - (last() - position() + 1)) mod %d = 0]', $anXpath, abs($nth_val_a));
                                                }
                                            }
                                        }
                                    }
                                }

                                if ($nth_type === false) {
                                    if ($expression['type'] != '*') {
                                        $anXpath = sprintf('%s[name() = "%s"]', $anXpath, $expression['type']);
                                    }
                                }
                            }
                            break;
                        case 'only-child':
                            $anXpath .= '[last() = 1]';
                            break;
                        case 'only-of-type':
                            $anXpath .= sprintf('[count(../%s)=1]', $expression['type']);
                            break;
                        case 'optional':
                            $anXpath .= '[name() = "input" or name() = "textarea" or name() = "select"][not( @required )]';
                            break;
                        case 'read-only':
                            $anXpath .= '[( not(@contenteditable) or @contenteditable = "false" ) and ' .
                                        ' not( ( name() = "input" or name() = "textarea" or name() = "select" ) and not(@readonly) and not(@disabled) )]';
                            break;
                        case 'read-write':
                            $anXpath .= '[( @contenteditable and ( @contenteditable = "true" or not(normalize-space(@contenteditable)) ) ) or ' .
                                        ' ( ( name() = "input" or name() = "textarea" or name() = "select" ) and not(@readonly) and not(@disabled) )]';
                            break;
                        case 'required':
                            $anXpath .= '[name() = "input" or name() = "textarea" or name() = "select"][@required]';
                            break;
                        case 'root':
                            $anXpath = sprintf('(%s/ancestor::*)[1]/*[1]', $anXpath);
                            break;

                        // Additional pseudo classes (not in CSS specifications) from jQuery library
                        case 'button':
                            $anXpath .= '[( name() = "button" and @type != "submit" ) or ( name() = "input" and @type = "button" )]';
                            break;
                        case 'checkbox':
                            $anXpath .= '[@type = "checkbox"]';
                            break;
                        case 'contains':
                            $anXpath .= sprintf('[contains(text(), \'%s\')]', addslashes($filter));
                            break;
                        case 'eq':
                            if (intval($filter) >= 0) {
                                $anXpath = sprintf('(%s)[position() = %d]', $anXpath, intval($filter) + 1);
                            } else {
                                $anXpath = sprintf('(%s)[last() - position() = %d]', $anXpath, abs(intval($filter) + 1));
                            }
                            break;
                        case 'even':
                            $anXpath = sprintf('(%s)[position() mod 2 != 1]', $anXpath);
                            break;
                        case 'file':
                            $anXpath .= '[@type="file"]';
                            break;
                        case 'gt':
                            if (intval($filter) >= 0) {
                                $anXpath = sprintf('(%s)[position() > %d]', $anXpath, intval($filter) + 1);
                            } else {
                                $anXpath = sprintf('(%s)[last() - position() < %d]', $anXpath, abs(intval($filter) + 1));
                            }
                            break;
                        case 'gte':
                            if (intval($filter) >= 0) {
                                $anXpath = sprintf('(%s)[position() >= %d]', $anXpath, intval($filter) + 1);
                            } else {
                                $anXpath = sprintf('(%s)[last() - position() <= %d]', $anXpath, abs(intval($filter) + 1));
                            }
                            break;
                        case 'header':
                            $anXpath .= '[name() = "h1" or name() = "h2" or name() = "h3" or name() = "h4" or name() = "h5" or name() = "h6"]';
                            break;
                        case 'image':
                            $anXpath .= '[@type="image"]';
                            break;
                        case 'input':
                            $anXpath .= '[name() = "input" or name() = "textarea" or name() = "select" or name() = "button"]';
                            break;
                        case 'last':
                            $anXpath = sprintf('(%s)[last()]', $anXpath);
                            break;
                        case 'lt':
                            if (intval($filter) >= 0) {
                                $anXpath = sprintf('(%s)[position() < %d]', $anXpath, intval($filter) + 1);
                            } else {
                                $anXpath = sprintf('(%s)[last() - position() > %d]', $anXpath, abs(intval($filter) + 1));
                            }
                            break;
                        case 'lte':
                            if (intval($filter) >= 0) {
                                $anXpath = sprintf('(%s)[position() <= %d]', $anXpath, intval($filter) + 1);
                            } else {
                                $anXpath = sprintf('(%s)[last() - position() >= %d]', $anXpath, abs(intval($filter) + 1));
                            }
                            break;
                        case 'odd':
                            $anXpath = sprintf('(%s)[position() mod 2 = 1]', $anXpath);
                            break;
                        case 'parent':
                            $anXpath .= '[normalize-space()]';
                            break;
                        case 'password':
                            $anXpath .= '[@type="password"]';
                            break;
                        case 'radio':
                            $anXpath .= '[@type="radio"]';
                            break;
                        case 'reset':
                            $anXpath .= '[@type="reset"]';
                            break;
                        case 'selected':
                            $anXpath .= '[name() = "option" and @selected]';
                            break;
                        case 'submit':
                            $anXpath .= '[( name() = "button" or name() = "input" ) and @type = "submit"]';
                            break;
                        case 'text':
                            $anXpath .= '[name() = "input" and ( @type="text" or not( @type ) )]';
                            break;

                        // Additional pseudo classes (not in CSS specifications)
                        case 'count':
                            $anXpath .= sprintf('[last() = %d]', intval($filter));
                            break;

                        default:
                            throw new SelectorException(sprintf('Filter "%s" is not valid in selector "%s"', $filterName, $this->selector));
                    }
                }

                $iExpression++;
            }

            // Concat all xpath
            $xpath .= ($iSelector == 0 ? '' : ' | ') . $anXpath;
            $iSelector++;
        }

        return $xpath;
    }

    /**
     * Get xpath with a context defined.
     *
     * @param int $context Context (checks constants)
     *
     * @return string
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     * @throws \InvalidArgumentException if a bad context chosen.
     */
    public function xpath(int $context = Selector::CONTEXT_ALL): string
    {
        if (is_null($this->xpath)) {
            $this->xpath = $this->xpathConversion($this->selector);
        }

        switch ($context) {
            case self::CONTEXT_ROOT:
                $contextValue = '//';
                break;
            case self::CONTEXT_ALL:
                $contextValue = './/';
                break;
            case self::CONTEXT_CHILD:
                $contextValue = './';
                break;
            case self::CONTEXT_SELF:
                $contextValue = 'self::';
                break;
            case self::CONTEXT_PARENTS:
                $contextValue = 'ancestor::';
                break;
            case self::CONTEXT_NEXT:
                $contextValue = 'following-sibling::*[1]/self::';
                break;
            case self::CONTEXT_NEXT_ALL:
                $contextValue = 'following-sibling::';
                break;
            case self::CONTEXT_PREV:
                $contextValue = 'preceding-sibling::*[last()]/self::';
                break;
            case self::CONTEXT_PREV_ALL:
                $contextValue = 'preceding-sibling::';
                break;
            default:
                throw new \InvalidArgumentException('Bad context chosen, checks Selector class constants');
        }

        return str_replace('%CONTEXT%', $contextValue, $this->xpath);
    }
}