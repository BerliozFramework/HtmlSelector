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

namespace Berlioz\HtmlSelector\Query;

use Berlioz\HtmlSelector\CssSelector\CssSelector;
use Berlioz\HtmlSelector\Exception\QueryException;
use Berlioz\HtmlSelector\Exception\SelectorException;
use Berlioz\HtmlSelector\HtmlSelector;
use Berlioz\HtmlSelector\XpathSolver;
use Countable;
use IteratorAggregate;
use SimpleXMLElement;

/**
 * Class Query.
 */
class Query implements Countable, IteratorAggregate
{
    public function __construct(
        protected array $html,
        protected CssSelector|string|null $selector,
        protected HtmlSelector $htmlSelector,
    ) {
        $this->html = array_filter($this->html, fn($value) => $value instanceof SimpleXMLElement);
    }

    public function getIterator()
    {
        return new QueryIterator($this, $this->htmlSelector);
    }

    protected function query(string $selector, string $context = XpathSolver::CONTEXT_ALL): static
    {
        return new Query($this->selector($selector, $context), $selector, $this->htmlSelector);
    }

    protected function selector(string $selector, string $context = XpathSolver::CONTEXT_ALL): array
    {
        $xpath = $this->htmlSelector->solveXpath($selector, $context);

        return $this->xpath($xpath);
    }

    protected function xpath(string $xpath): array
    {
        $result = [];

        /** @var SimpleXMLElement $element */
        foreach ($this->html as $element) {
            if (false === ($elementResult = $element->xpath($xpath))) {
                throw new SelectorException(sprintf('Xpath error "%s"', $xpath));
            }

            array_push($result, ...$elementResult);
        }

        return $result;
    }

    /**
     * Get selector.
     *
     * @return CssSelector|string|null
     */
    public function getSelector(): CssSelector|string|null
    {
        if (null === $this->selector) {
            return null;
        }

        return $this->selector;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->html);
    }

    /**
     * Isset?
     *
     * @param int $key
     *
     * @return bool
     */
    public function isset(int $key): bool
    {
        return isset($this->html[$key]);
    }

    /**
     * Get elements.
     *
     * @param int|null $key
     *
     * @return SimpleXMLElement|SimpleXMLElement[]
     * @throws QueryException if element not found
     */
    public function get(?int $key = null): SimpleXMLElement|array
    {
        if (null === $key) {
            return $this->html;
        }

        if (isset($this->html[$key])) {
            return $this->html[$key];
        }

        throw new QueryException(sprintf('Element %d not found in DOM', $key));
    }

    /**
     * Get index of first element in selector.
     *
     * @param Query|string|null $selector Selector
     *
     * @return int
     * @throws QueryException
     */
    public function index(Query|string|null $selector = null): int
    {
        if (empty($selector)) {
            if (isset($this->html[0])) {
                return count($this->html[0]->xpath('./preceding-sibling::*'));
            }

            return -1;
        }

        if (is_string($selector)) {
            $elements = $this->selector($selector, XpathSolver::CONTEXT_PARENTS);
            $index = array_search(reset($elements), $this->get());

            if (false !== $index) {
                return intval($index);
            }
            return -1;
        }

        if ($selector instanceof Query) {
            $index = array_search($selector->get(0), $this->get());

            if (false !== $index) {
                return intval($index);
            }
        }


        return -1;
    }

    /**
     * Find child elements with selector.
     *
     * @param string $selector Selector
     *
     * @return static
     */
    public function find(string $selector): static
    {
        $result = $this->selector($selector);

        return new Query($result, $selector, $this->htmlSelector);
    }

    /**
     * Filter current elements with selector.
     *
     * @param string $selector Selector
     *
     * @return static
     */
    public function filter(string $selector): static
    {
        return new Query($this->selector($selector, XpathSolver::CONTEXT_SELF), $selector, $this->htmlSelector);
    }

    /**
     * Check if elements valid the selector specified or if elements are in Query elements given.
     *
     * @param string|Query $selector Selector
     *
     * @return bool
     * @throws QueryException
     */
    public function is(Query|string $selector): bool
    {
        // Selector
        if (!$selector instanceof Query) {
            $selector = $this->find($selector);
        }

        foreach ($this->html as $simpleXml) {
            if (in_array($simpleXml, $selector->get())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Not elements of selector in current elements.
     *
     * @param string $selector Selector
     *
     * @return static
     */
    public function not(string $selector): static
    {
        return $this->query(sprintf(':not(%s)', $selector), XpathSolver::CONTEXT_SELF);
    }

    /**
     * Get parent of currents elements.
     *
     * @return static
     */
    public function parent(): static
    {
        return new Query($this->xpath('./..'), null, $this->htmlSelector);
    }

    /**
     * Get all parents of currents elements.
     *
     * @param string|null $selector Selector
     *
     * @return static
     */
    public function parents(?string $selector = null): static
    {
        return $this->query($selector ?? '*', XpathSolver::CONTEXT_PARENTS);
    }

    /**
     * Get children of current elements.
     *
     * @param string|null $selector Selector
     *
     * @return static
     * @throws SelectorException
     */
    public function children(?string $selector = null): static
    {
        if (null === $selector) {
            return new Query($this->xpath('./child::*'), null, $this->htmlSelector);
        }

        return new Query(
            $this->xpath(sprintf('./child::*[boolean(%s)]', $this->selector($selector, XpathSolver::CONTEXT_SELF))),
            null,
            $this->htmlSelector
        );
    }

    /**
     * Get html of the first element.
     *
     * @return string
     */
    public function html(): string
    {
        if (!isset($this->html[0])) {
            return '';
        }

        $regex = <<<'EOD'
~
(?(DEFINE)
(?<d_quotes> '(?>[^'\\]++|\\.)*' | "(?>[^"\\]++|\\.)*" )
    (?<d_tag_content> \g<d_quotes> | [^>]+ )
    (?<d_tag_open> < \g<d_tag_content>+ > )
    (?<d_tag_close> <\/ \g<d_tag_content> > )
)

^ \s* \g<d_tag_open> (?<html> .*) \g<d_tag_close> \s* $
~ixs
EOD;

        if (preg_match($regex, (string)$this->html[0]->asXML(), $matches) === 1) {
            return $matches['html'] ?? '';
        }

        return '';
    }

    /**
     * Get text of elements and children elements.
     *
     * @param bool $withChildren With children (default: true)
     *
     * @return string
     */
    public function text(bool $withChildren = true): string
    {
        $str = '';

        /** @var SimpleXMLElement $simpleXml */
        foreach ($this->html as $simpleXml) {
            if ($withChildren) {
                $str .= strip_tags((string)$simpleXml->asXML());
                continue;
            }

            $str .= (string)$simpleXml;
        }

        return $str;
    }

    /**
     * Get/Set attribute value of the first element, null if attribute undefined.
     *
     * @param string $name Name
     * @param string|null $value Value
     *
     * @return static|string|null
     */
    public function attr(string $name, string $value = null): static|string|null
    {
        if (isset($this->html[0])) {
            // Setter
            if (null !== $value) {
                if (isset($this->html[0]->attributes()->{$name})) {
                    $this->html[0]->attributes()->{$name} = $value;
                } else {
                    $this->html[0]->addAttribute($name, $value);
                }

                return $this;
            }

            // Getter
            if ($this->html[0]->attributes()->{$name}) {
                return (string)$this->html[0]->attributes()->{$name};
            }

            return null;
        }

        if (null !== $value) {
            return $this;
        }

        return null;
    }

    /**
     * Get/Set property value of attribute of the first element, false if attribute undefined.
     *
     * @param string $name Name
     * @param bool|null $value Value
     *
     * @return bool|Query
     */
    public function prop(string $name, bool $value = null): static|bool
    {
        if (isset($this->html[0])) {
            // Set & Unset
            if (null !== $value) {
                // Set
                if ($value === true) {
                    if (isset($this->html[0]->attributes()->{$name})) {
                        $this->html[0]->attributes()->{$name} = $name;

                        return $this;
                    }

                    $this->html[0]->addAttribute($name, $name);

                    return $this;
                }

                // Unset
                unset($this->html[0]->attributes()->{$name});

                return $this;
            }

            // Getter
            return isset($this->html[0]->attributes()->{$name});
        }

        if (null !== $value) {
            return $this;
        }

        return false;
    }

    /**
     * Get data value.
     *
     * @param string $name Name of data with camelCase syntax
     * @param string|null $value Value
     *
     * @return static|string|null
     */
    public function data(string $name, string $value = null): static|string|null
    {
        $name = mb_strtolower(preg_replace('/([a-z0-9])([A-Z])/', '\\1-\\2', $name));

        return $this->attr(sprintf('data-%s', $name), $value);
    }

    /**
     * Has class?
     *
     * @param string $classes Classes separated by space
     *
     * @return bool
     */
    public function hasClass(string $classes): bool
    {
        $classes = explode(' ', $classes);

        // Filter values
        $classes = array_map('trim', $classes);
        $classes = array_filter($classes);

        if (count($classes) === 0) {
            return false;
        }

        // Make selector
        $selector = implode(array_map(fn($class) => sprintf('[class~="%s"]', $class), $classes));

        return count($this->selector($selector, XpathSolver::CONTEXT_SELF)) > 0;
    }

    /**
     * Add class.
     *
     * @param string $classes Classes separated by space
     *
     * @return static
     */
    public function addClass(string $classes): static
    {
        $classes = explode(' ', $classes);
        $classes = array_map('trim', $classes);
        $classes = array_filter($classes);
        $classes = array_unique($classes);

        foreach ($this->html as $simpleXml) {
            $elClasses = (string)($simpleXml->attributes()->class ?? '');
            $elClasses = explode(' ', $elClasses);
            $elClasses = array_map('trim', $elClasses);
            $elClasses = array_filter($elClasses);
            $elClasses = array_merge($elClasses, $classes);
            $elClasses = array_unique($elClasses);

            if (null === $simpleXml->attributes()->class) {
                $simpleXml->addAttribute('class', implode(' ', $elClasses));
                continue;
            }

            $simpleXml->attributes()->class = implode(' ', $elClasses);
        }

        return $this;
    }

    /**
     * Remove class.
     *
     * @param string $classes Classes separated by space
     *
     * @return static
     */
    public function removeClass(string $classes): static
    {
        $classes = explode(' ', $classes);
        $classes = array_map('trim', $classes);
        $classes = array_filter($classes);
        $classes = array_unique($classes);

        foreach ($this->html as $simpleXml) {
            if (null === $simpleXml->attributes()->class) {
                continue;
            }

            $elClasses = (string)($simpleXml->attributes()->class ?? '');
            $elClasses = explode(' ', $elClasses);
            $elClasses = array_map('trim', $elClasses);
            $elClasses = array_filter($elClasses);
            $elClasses = array_diff($elClasses, $classes);
            $elClasses = array_unique($elClasses);

            $simpleXml->attributes()->class = implode(' ', $elClasses);
        }

        return $this;
    }

    /**
     * Toggle class.
     *
     * @param string $classes Classes separated by space
     * @param bool|callable|null $test
     *
     * @return static
     */
    public function toggleClass(string $classes, bool|callable|null $test = null): static
    {
        // With test parameter
        if (null !== $test) {
            if (is_callable($test)) {
                $test = !!$test();
            }

            if ($test === false) {
                return $this->removeClass($classes);
            }

            return $this->addClass($classes);
        }

        $classes = explode(' ', $classes);
        $classes = array_map('trim', $classes);
        $classes = array_filter($classes);
        $classes = array_unique($classes);

        foreach ($this->html as $simpleXml) {
            $elClasses = (string)($simpleXml->attributes()->class ?? '');
            $elClasses = explode(' ', $elClasses);
            $elClasses = array_map('trim', $elClasses);
            $elClasses = array_filter($elClasses);
            $elClasses = array_unique($elClasses);

            foreach ($classes as $class) {
                if (($foundClass = array_search($class, $elClasses)) === false) {
                    $elClasses[] = $class;
                    continue;
                }

                unset($elClasses[$foundClass]);
            }

            if (null === $simpleXml->attributes()->class) {
                $simpleXml->addAttribute('class', implode(' ', $elClasses));
                continue;
            }

            $simpleXml->attributes()->class = implode(' ', $elClasses);
        }

        return $this;
    }

    /**
     * Get strictly immediately next element.
     *
     * @param string|null $selector Selector
     *
     * @return static
     */
    public function next(string $selector = null): static
    {
        return new Query($this->selector($selector ?? '*', XpathSolver::CONTEXT_NEXT), null, $this->htmlSelector);
    }

    /**
     * Get all next elements.
     *
     * @param string|null $selector Selector
     *
     * @return static
     */
    public function nextAll(string $selector = null): static
    {
        return new Query($this->selector($selector ?? '*', XpathSolver::CONTEXT_NEXT_ALL), null, $this->htmlSelector);
    }

    /**
     * Get strictly immediately prev element.
     *
     * @param string|null $selector Selector
     *
     * @return static
     */
    public function prev(string $selector = null): static
    {
        return new Query($this->selector($selector ?? '*', XpathSolver::CONTEXT_PREV), null, $this->htmlSelector);
    }

    /**
     * Get all prev elements.
     *
     * @param string|null $selector Selector
     *
     * @return static
     */
    public function prevAll(string $selector = null): static
    {
        return new Query($this->selector($selector ?? '*', XpathSolver::CONTEXT_PREV_ALL), null, $this->htmlSelector);
    }

    /**
     * Get value of a form element.
     *
     * @return array|string|null
     */
    public function val(): array|string|null
    {
        if (!isset($this->html[0])) {
            return null;
        }

        switch ($this->html[0]->getName()) {
            case 'button':
            case 'input':
                return
                    match ($this->html[0]->attributes()->{'type'} ?? 'text') {
                        'checkbox' => (string)$this->html[0]->attributes()->{'value'} ?? null,
                        'radio' => (string)$this->html[0]->attributes()->{'value'} ?? 'on',
                        default => (string)$this->html[0]->attributes()->{'value'} ?? '',
                    };
            case 'select':
                $allSelected = $this->html[0]->xpath('./option[@selected]');
                $values = [];

                if (empty($allSelected)) {
                    $options = $this->html[0]->xpath('./option');

                    if (!empty($options)) {
                        array_push($allSelected, $this->html[0]->xpath('./option')[0]);
                    }
                }

                foreach ($allSelected as $selected) {
                    if (isset($selected->attributes()->{'value'})) {
                        if (isset($selected->attributes()->{'value'})) {
                            $values[] = (string)$selected->attributes()->{'value'};
                            continue;
                        }

                        $values[] = (string)$selected;
                        continue;
                    }

                    $values[] = (string)$selected;
                }

                if (!isset($this->html[0]->attributes()->{'multiple'})) {
                    if (($value = end($values)) !== false) {
                        return $value;
                    }

                    return null;
                }

                return $values;
            case 'textarea':
                return (string)$this->html[0];
            default:
                return null;
        }
    }

    /**
     * Serialize values of forms elements in an array.
     *
     * Typically, the function is called on main form elements, but can be called on input elements.
     *
     * @return array
     */
    public function serializeArray(): array
    {
        $result = [];

        $query =
            $this
                ->filter('form :input, :input')
                ->filter(
                    '[name]:enabled:not(:button, :submit, [type=reset], [type="checkbox"]:not(:checked), [type="radio"]:not(:checked))'
                );

        foreach ($query as $element) {
            foreach ((array)$element->val() as $value) {
                $result[] = [
                    'name' => $element->attr('name'),
                    'value' => $value,
                ];
            }
        }

        return $result;
    }

    /**
     * Encode form elements as a string for HTTP submission.
     *
     * @return string
     */
    public function serialize(): string
    {
        $arraySerialized = $this->serializeArray();
        $queryStrings = [];

        foreach ($arraySerialized as $element) {
            $queryStrings[] = sprintf('%s=%s', urlencode($element['name']), urlencode($element['value']));
        }

        return implode('&', $queryStrings);
    }

    /**
     * Remove elements.
     *
     * @param string|null $selector Selector
     *
     * @return static
     * @throws QueryException
     */
    public function remove(string $selector = null): static
    {
        $query = $this;
        if (!is_null($selector)) {
            $query = $this->filter($selector);
        }

        /** @var SimpleXMLElement $simpleXml */
        foreach ($query->get() as $i => $simpleXml) {
            $domNode = dom_import_simplexml($simpleXml);
            $domNode->parentNode->removeChild($domNode);
        }

        return $query;
    }
}