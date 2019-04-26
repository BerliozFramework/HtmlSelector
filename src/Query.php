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

declare(strict_types=1);

namespace Berlioz\HtmlSelector;

use Berlioz\HtmlSelector\Exception\QueryException;

/**
 * Class Query.
 *
 * @package Berlioz\HtmlSelector
 */
class Query implements \IteratorAggregate, \Countable
{
    /** @var \Berlioz\HtmlSelector\Selector Selector */
    private $selector;
    /** @var int Selector context */
    private $selectorContext = Selector::CONTEXT_ALL;
    /** @var \SimpleXMLElement[] Simple XML Element */
    private $simpleXml;
    /** @var callable[] Dynamics functions */
    private static $functions;

    /**
     * Query constructor.
     *
     * @param Query|\SimpleXMLElement|\SimpleXMLElement[] $element         Element
     * @param Selector|string                             $selector        Selector
     * @param int                                         $selectorContext Context of selector
     *
     * @throws \InvalidArgumentException if bad arguments given.
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function __construct($element, $selector = null, int $selectorContext = Selector::CONTEXT_ALL)
    {
        // Element
        /** @var \SimpleXMLElement[] $elements */
        if ($element instanceof \SimpleXMLElement) {
            $elements = [$element];
        } else {
            // Array of \SimpleXMLElement
            if (is_array($element)) {
                array_walk(
                    $element,
                    function ($v) {
                        if (!$v instanceof \SimpleXMLElement) {
                            throw new \InvalidArgumentException(sprintf('Element parameter must be a \SimpleXmlElement object (or array of this) or Query object, "%s" given', gettype($v)));
                        }
                    });

                $elements = $element;
            } else {
                // Query object
                if ($element instanceof Query) {
                    $elements = $element->get();
                } else {
                    throw new \InvalidArgumentException(sprintf('Element parameter must be a \SimpleXmlElement object (or array of this) or Query object, "%s" given', gettype($element)));
                }
            }
        }

        // Selector
        if (!is_null($selector)) {
            if ($selector instanceof Selector) {
                $this->selector = $selector;
            } else {
                if (is_string($selector)) {
                    $this->selector = new Selector($selector);
                } else {
                    throw new \InvalidArgumentException(sprintf('Selector parameter must be a string or Selector object, "%s" given', gettype($selector)));
                }
            }
        }
        $this->selectorContext = $selectorContext;

        // Perform selection
        if (!is_null($this->getSelector())) {
            $this->simpleXml = [];
            foreach ($elements as $simpleXml) {
                if (($result = $simpleXml->xpath($this->getSelector()->xpath($this->selectorContext))) !== false) {
                    $this->simpleXml = array_merge(($this->simpleXml ?? []), $result);
                }
            }
        } else {
            $this->simpleXml = $elements;
        }
    }

    /**
     * __sleep() magic method.
     *
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     */
    public function __sleep()
    {
        throw new QueryException('It\'s not possible to serialize Query object.');
    }

    /**
     * __call magic method.
     *
     * @param string $name      Name
     * @param array  $arguments Arguments
     *
     * @return mixed
     * @throws \Berlioz\HtmlSelector\Exception\QueryException if function not declared
     */
    public function __call($name, $arguments)
    {
        if (isset(self::$functions[$name])) {
            return call_user_func_array(self::$functions[$name], array_merge([$this], $arguments));
        } else {
            throw new QueryException(sprintf('Function "%s" not declared', $name));
        }
    }

    /**
     * Add user defined function.
     *
     * Must be a function, the first argument given during call is the Query object.
     * The others arguments, are the arguments given by user.
     *
     * @param string   $name     Name
     * @param callable $callback Callback
     */
    public static function addFunction(string $name, callable $callback): void
    {
        self::$functions[$name] = $callback;
    }

    /**
     * Create new iterator.
     *
     * @return \Berlioz\HtmlSelector\QueryIterator
     */
    public function getIterator(): QueryIterator
    {
        return new QueryIterator($this);
    }

    /**
     * Load HTML file.
     *
     * @param string $html   HTML string.
     * @param bool   $isFile If first parameter is filename (default: false)
     *
     * @return \Berlioz\HtmlSelector\Query
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public static function loadHtml(string $html, bool $isFile = false): Query
    {
        // Load file
        if ($isFile) {
            if (($html = @file_get_contents($html)) === false) {
                throw new QueryException(sprintf('Unable to load file "%s"', $html));
            }
        }

        // Encoding
        $encoding = mb_detect_encoding($html) ?: 'ASCII';

        // Prepare html
        $html = str_replace(['&nbsp;', chr(13)], [' ', ''], $html);
        $html = static::stripInvalidXml($html);

        // Convert HTML string to \DOMDocument
        libxml_use_internal_errors(true);
        $domHtml = new \DOMDocument('1.0', $encoding);
        if (!$domHtml->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', $encoding), LIBXML_COMPACT)) {
            throw new QueryException('Unable to parse HTML data.');
        } else {
            // Add 'document' root node
            $nodeDocument = $domHtml->createElement('document');
            $nodeDocument->setAttribute('dir', 'ltr');
            while (isset($domHtml->childNodes[0])) {
                $nodeDocument->appendChild($domHtml->childNodes[0]);
            }
            $domHtml->appendChild($nodeDocument);

            // Convert \DOMDocument to \SimpleXMLElement object
            $simpleXml = simplexml_import_dom($domHtml);

            return new Query($simpleXml);
        }
    }

    /**
     * Strip invalid XML for init method.
     *
     * @param string $xml XML file
     *
     * @return string
     */
    private static function stripInvalidXml($xml)
    {
        $ret = "";

        if (empty($xml)) {
            return $ret;
        }

        $length = strlen($xml);
        for ($i = 0; $i < $length; $i++) {
            $current = ord($xml{$i});

            if ((0x9 == $current) ||
                (0xA == $current) ||
                (0xD == $current) ||
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF))
            ) {
                $ret .= chr($current);
            } else {
                $ret .= " ";
            }
        }

        return $ret;
    }

    /**
     * Get selector.
     *
     * @return \Berlioz\HtmlSelector\Selector|null
     */
    public function getSelector(): ?Selector
    {
        return $this->selector;
    }

    /**
     * Count direct elements in query.
     *
     * @return int
     */
    public function count()
    {
        return count($this->simpleXml);
    }

    /**
     * Isset SimpleXMLElement ?
     *
     * @param int $key
     *
     * @return bool
     */
    public function isset(int $key): bool
    {
        return isset($this->simpleXml[$key]);
    }

    /**
     * Get SimpleXMLElements.
     *
     * @param int|null $key
     *
     * @return \SimpleXMLElement|\SimpleXMLElement[]
     * @throws \Berlioz\HtmlSelector\Exception\QueryException if element not found
     */
    public function get(?int $key = null)
    {
        if (is_null($key)) {
            return $this->simpleXml;
        } else {
            if (isset($this->simpleXml[$key])) {
                return $this->simpleXml[$key];
            } else {
                throw new QueryException(sprintf('Element %d not found in DOM', $key));
            }
        }
    }

    /**
     * Get index of first element in selector.
     *
     * @param string|Query|null $selector Selector
     *
     * @return int
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function index($selector = null): int
    {
        if (empty($selector)) {
            if (isset($this->simpleXml[0])) {
                return count($this->simpleXml[0]->xpath('./preceding-sibling::*'));
            }
        } else {
            if (!$selector instanceof Query) {
                // Make selector
                $selector = new Query($this, $selector ?? '*', Selector::CONTEXT_ROOT);
            }

            if ($selector->isset(0) && ($result = array_search($selector->get(0), $this->get())) !== false) {
                return intval($result);
            }
        }

        return -1;
    }

    /**
     * Find child elements with selector.
     *
     * @param string $selector Selector
     *
     * @return \Berlioz\HtmlSelector\Query
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function find(string $selector): Query
    {
        return new Query($this, $selector);
    }

    /**
     * Filter current elements with selector.
     *
     * @param string $selector Selector
     *
     * @return \Berlioz\HtmlSelector\Query
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function filter(string $selector): Query
    {
        return new Query($this, new Selector($selector), Selector::CONTEXT_SELF);
    }

    /**
     * Check if elements valid the selector specified or if elements are in Query elements given.
     *
     * @param string|Query $selector Selector
     *
     * @return bool
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function is($selector): bool
    {
        // Selector
        if (!$selector instanceof Query) {
            $selector = new Selector($selector);
        }

        foreach ($this->simpleXml as $simpleXml) {
            if ($selector instanceof Query) {
                if (in_array($simpleXml, $selector->get())) {
                    return true;
                }
            } else {
                if (count($simpleXml->xpath(sprintf('self::*[%s]', $selector->xpath(Selector::CONTEXT_SELF)))) == 1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Not elements of selector in current elements.
     *
     * @param string $selector Selector
     *
     * @return \Berlioz\HtmlSelector\Query
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function not(string $selector): Query
    {
        return new Query($this, new Selector(sprintf(':not(%s)', $selector)), Selector::CONTEXT_SELF);
    }

    /**
     * Get parent of currents elements.
     *
     * @return \Berlioz\HtmlSelector\Query
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function parent(): Query
    {
        $parents = [];

        foreach ($this->simpleXml as $simpleXml) {
            $parents = array_merge($parents, $simpleXml->xpath('./..'));
        }

        return new Query($parents);
    }

    /**
     * Get all parents of currents elements.
     *
     * @param string|null $selector Selector
     *
     * @return \Berlioz\HtmlSelector\Query
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function parents(?string $selector = null): Query
    {
        $parents = [];

        // Selector
        $selector = new Selector($selector ?? '*');

        foreach ($this->simpleXml as $simpleXml) {
            $parents = array_merge($parents, $simpleXml->xpath($selector->xpath(Selector::CONTEXT_PARENTS)));
        }

        return new Query($parents);
    }

    /**
     * Get children of current elements.
     *
     * @param string|null $selector Selector
     *
     * @return \Berlioz\HtmlSelector\Query
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function children(?string $selector = null): Query
    {
        $children = [];

        // Selector
        if (!is_null($selector)) {
            $selector = new Selector($selector);
        }

        foreach ($this->simpleXml as $simpleXml) {
            if (!is_null($selector)) {
                $children = array_merge($children, $simpleXml->xpath('./child::*[boolean(' . $selector->xpath(Selector::CONTEXT_SELF) . ')]'));
            } else {
                $children = array_merge($children, $simpleXml->xpath('./child::*'));
            }
        }

        return new Query($children);
    }

    /**
     * Get html of the first element.
     *
     * @return string
     */
    public function html(): string
    {
        if (isset($this->simpleXml[0])) {
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

            if (preg_match($regex, (string) $this->simpleXml[0]->asXML(), $matches) === 1) {
                return $matches['html'] ?? '';
            }
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

        /** @var \SimpleXMLElement $simpleXml */
        foreach ($this->simpleXml as $simpleXml) {
            if ($withChildren) {
                $str .= strip_tags((string) $simpleXml->asXML());
            } else {
                $str .= (string) $simpleXml;
            }
        }

        return $str;
    }

    /**
     * Get/Set attribute value of the first element, null if attribute undefined.
     *
     * @param string      $name  Name
     * @param string|null $value Value
     *
     * @return null|string|\Berlioz\HtmlSelector\Query
     */
    public function attr(string $name, string $value = null)
    {
        if (isset($this->simpleXml[0])) {
            if (!is_null($value)) {
                if (isset($this->simpleXml[0]->attributes()->{$name})) {
                    $this->simpleXml[0]->attributes()->{$name} = $value;
                } else {
                    $this->simpleXml[0]->addAttribute($name, $value);
                }

                return $this;
            } else {
                if ($this->simpleXml[0]->attributes()->{$name}) {
                    return (string) $this->simpleXml[0]->attributes()->{$name};
                } else {
                    return null;
                }
            }
        } else {
            if (!is_null($value)) {
                return $this;
            } else {
                return null;
            }
        }
    }

    /**
     * Get/Set property value of attribute of the first element, false if attribute undefined.
     *
     * @param string    $name  Name
     * @param bool|null $value Value
     *
     * @return bool|\Berlioz\HtmlSelector\Query
     */
    public function prop(string $name, bool $value = null)
    {
        if (isset($this->simpleXml[0])) {
            if (!is_null($value)) {
                if ($value === true) {
                    if (isset($this->simpleXml[0]->attributes()->{$name})) {
                        $this->simpleXml[0]->attributes()->{$name} = $name;
                    } else {
                        $this->simpleXml[0]->addAttribute($name, $name);
                    }
                } else {
                    unset($this->simpleXml[0]->attributes()->{$name});
                }

                return $this;
            } else {
                return isset($this->simpleXml[0]->attributes()->{$name});
            }
        } else {
            if (!is_null($value)) {
                return $this;
            } else {
                return false;
            }
        }
    }

    /**
     * Get data value.
     *
     * @param string      $name  Name of data with camelCase syntax
     * @param string|null $value Value
     *
     * @return null|string|\Berlioz\HtmlSelector\Query
     */
    public function data(string $name, string $value = null)
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
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function hasClass(string $classes)
    {
        $classes = explode(' ', $classes);

        // Filter values
        $classes = array_map('trim', $classes);
        $classes = array_filter($classes);

        if (count($classes) > 0) {
            // Make selector
            $selector = implode('',
                                array_map(
                                    function ($class) {
                                        return sprintf('[class~="%s"]', $class);
                                    },
                                    $classes));
            $selector = new Selector($selector);

            // Check all elements
            foreach ($this->simpleXml as $simpleXml) {
                if (count($simpleXml->xpath($selector->xpath(Selector::CONTEXT_SELF))) > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add class.
     *
     * @param string $classes Classes separated by space
     *
     * @return static
     */
    public function addClass(string $classes): Query
    {
        $classes = explode(' ', $classes);
        $classes = array_map('trim', $classes);

        foreach ($this->simpleXml as $simpleXml) {
            $elClasses = (string) ($simpleXml->attributes()->class ?? '');
            $elClasses = explode(' ', $elClasses);
            $elClasses = array_map('trim', $elClasses);
            $elClasses = array_merge($elClasses, $classes);
            $elClasses = array_unique($elClasses);

            if (is_null($simpleXml->attributes()->class)) {
                $simpleXml->addAttribute('class', '');
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
    public function removeClass(string $classes): Query
    {
        $classes = explode(' ', $classes);
        $classes = array_map('trim', $classes);

        foreach ($this->simpleXml as $simpleXml) {
            $elClasses = (string) ($simpleXml->attributes()->class ?? '');
            $elClasses = explode(' ', $elClasses);
            $elClasses = array_map('trim', $elClasses);
            $elClasses = array_diff($elClasses, $classes);
            $elClasses = array_unique($elClasses);

            if (!is_null($simpleXml->attributes()->class)) {
                $simpleXml->attributes()->class = implode(' ', $elClasses);
            }
        }

        return $this;
    }

    /**
     * Toggle class.
     *
     * @param string    $classes Classes separated by space
     * @param bool|null $test
     *
     * @return \Berlioz\HtmlSelector\Query
     */
    public function toggleClass(string $classes, bool $test = null): Query
    {
        if (!is_null($test)) {
            if ($test === false) {
                return $this->removeClass($classes);
            }

            return $this->addClass($classes);
        }

        $classes = explode(' ', $classes);
        $classes = array_map('trim', $classes);

        foreach ($this->simpleXml as $simpleXml) {
            $elClasses = (string) ($simpleXml->attributes()->class ?? '');
            $elClasses = explode(' ', $elClasses);
            $elClasses = array_map('trim', $elClasses);

            foreach ($classes as $class) {
                if (($foundClass = array_search($class, $elClasses)) === false) {
                    $elClasses[] = $class;
                    continue;
                }

                unset($elClasses[$foundClass]);
            }

            if (is_null($simpleXml->attributes()->class)) {
                $simpleXml->addAttribute('class', '');
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
     * @return \Berlioz\HtmlSelector\Query
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function next(string $selector = null): Query
    {
        $next = [];

        // Selector
        $selector = new Selector($selector ?? '*');

        foreach ($this->simpleXml as $simpleXml) {
            $next = array_merge($next, $simpleXml->xpath($selector->xpath(Selector::CONTEXT_NEXT)));
        }

        return new Query($next);
    }

    /**
     * Get all next elements.
     *
     * @param string|null $selector Selector
     *
     * @return \Berlioz\HtmlSelector\Query
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function nextAll(string $selector = null): Query
    {
        $nextAll = [];

        // Selector
        $selector = new Selector($selector ?? '*');

        foreach ($this->simpleXml as $simpleXml) {
            $nextAll = array_merge($nextAll, $simpleXml->xpath($selector->xpath(Selector::CONTEXT_NEXT_ALL)));
        }

        return new Query($nextAll);
    }

    /**
     * Get strictly immediately prev element.
     *
     * @param string|null $selector Selector
     *
     * @return \Berlioz\HtmlSelector\Query
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function prev(string $selector = null): Query
    {
        $prev = [];

        // Selector
        $selector = new Selector($selector ?? '*');

        foreach ($this->simpleXml as $simpleXml) {
            $prev = array_merge($prev, $simpleXml->xpath($selector->xpath(Selector::CONTEXT_PREV)));
        }

        return new Query($prev);
    }

    /**
     * Get all prev elements.
     *
     * @param string|null $selector Selector
     *
     * @return \Berlioz\HtmlSelector\Query
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function prevAll(string $selector = null): Query
    {
        $prevAll = [];

        // Selector
        $selector = new Selector($selector ?? '*');

        foreach ($this->simpleXml as $simpleXml) {
            $prevAll = array_merge($prevAll, $simpleXml->xpath($selector->xpath(Selector::CONTEXT_PREV_ALL)));
        }

        return new Query($prevAll);
    }

    /**
     * Get value of a form element.
     *
     * @return array|null|string
     */
    public function val()
    {
        if (isset($this->simpleXml[0])) {
            switch ($this->simpleXml[0]->getName()) {
                case 'button':
                case 'input':
                    switch ($this->simpleXml[0]->attributes()->{'type'} ?? 'text') {
                        case 'checkbox':
                            return (string) $this->simpleXml[0]->attributes()->{'value'} ?? null;
                        case 'radio':
                            return (string) $this->simpleXml[0]->attributes()->{'value'} ?? 'on';
                        default:
                            return (string) $this->simpleXml[0]->attributes()->{'value'} ?? '';
                    }
                    break;
                case 'select':
                    $allSelected = $this->simpleXml[0]->xpath('./option[@selected]');
                    $values = [];

                    if (empty($allSelected)) {
                        $options = $this->simpleXml[0]->xpath('./option');

                        if (!empty($options)) {
                            array_push($allSelected, $this->simpleXml[0]->xpath('./option')[0]);
                        }
                    }

                    foreach ($allSelected as $selected) {
                        if (isset($selected->attributes()->{'value'})) {
                            if (isset($selected->attributes()->{'value'})) {
                                $values[] = (string) $selected->attributes()->{'value'};
                            } else {
                                $values[] = (string) $selected;
                            }
                        } else {
                            $values[] = (string) $selected;
                        }
                    }

                    if (!isset($this->simpleXml[0]->attributes()->{'multiple'})) {
                        if (($value = end($values)) !== false) {
                            return $value;
                        } else {
                            return null;
                        }
                    } else {
                        return $values;
                    }
                case 'textarea':
                    return (string) $this->simpleXml[0];
                default:
                    return null;
            }
        }

        return null;
    }

    /**
     * Serialize values of forms elements in an array.
     *
     * Typically, the function is called on main form elements, but can be called on input elements.
     *
     * @return array
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function serializeArray()
    {
        $result = [];

        $query = $this->filter('form :input, :input')
                      ->filter('[name]:enabled:not(:button, :submit, [type=reset], [type="checkbox"]:not(:checked), [type="radio"]:not(:checked))');

        foreach ($query as $element) {
            foreach ((array) $element->val() as $value) {
                $result[] = ['name'  => $element->attr('name'),
                             'value' => $value];
            }
        }

        return $result;
    }

    /**
     * Encode form elements as a string for HTTP submission.
     *
     * @return string
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function serialize()
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
     * @return \Berlioz\HtmlSelector\Query
     * @throws \Berlioz\HtmlSelector\Exception\QueryException
     * @throws \Berlioz\HtmlSelector\Exception\SelectorException
     */
    public function remove(string $selector = null): Query
    {
        if (!is_null($selector)) {
            $query = $this->filter($selector);
        } else {
            $query = $this;
        }

        /** @var \SimpleXMLElement $simpleXml */
        foreach ($this->simpleXml as $i => $simpleXml) {
            $domNode = dom_import_simplexml($simpleXml);
            $domNode->parentNode->removeChild($domNode);
        }

        return $query;
    }
}
