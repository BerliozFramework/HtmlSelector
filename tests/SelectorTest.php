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

namespace Berlioz\HtmlSelector\Tests;


use Berlioz\HtmlSelector\Exception\SelectorException;
use Berlioz\HtmlSelector\Selector;
use PHPUnit\Framework\TestCase;

class SelectorTest extends TestCase
{
    /**
     * Provider to test conversions.
     *
     * @return array
     */
    public function selectorDataProvider()
    {
        return [// Element with class
                ['select.class',
                 './/select[contains(concat(" ", @class, " "), " class ")]'],
                // Element with 2 classes and a not comparison attribute
                ['select.class.class2[attr1 != "test"]',
                 './/select[contains(concat(" ", @class, " "), " class ")][contains(concat(" ", @class, " "), " class2 ")][@attr1!="test"]'],
                // Element with class and direct children element with attribute comparison
                ['select.class > option[value="test"]',
                 './/select[contains(concat(" ", @class, " "), " class ")]/option[@value="test"]'],
                // Class with not direct element with attribute comparison
                ['.class option[value="test"]',
                 './/*[contains(concat(" ", @class, " "), " class ")]//option[@value="test"]'],
                // Class with not direct element with just attribute name
                ['.class option[value]',
                 './/*[contains(concat(" ", @class, " "), " class ")]//option[@value]']];
    }

    /**
     * Test constructor with not valid selector.
     */
    public function testConstructorNotValidSelector()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Selector('$select.class.class2[attr1 != "test"]');
    }

    /**
     * Test xpath conversion with not valid selector.
     */
    public function testXpathConversionNotValidSelector()
    {
        $this->expectException(SelectorException::class);
        (new Selector('select.class.class2[attr1 != "test"]:notvalid'))->xpath();
    }

    /**
     * Test Xpath conversion.
     *
     * @param string $selector
     * @param string $xpath
     *
     * @dataProvider selectorDataProvider
     */
    public function testXpathConversion($selector, $xpath)
    {
        $selector = new Selector($selector);

        $this->assertEquals($xpath,
                            $selector->xpath(),
                            sprintf('Invalid xpath conversion for %s', $selector));
    }
}
