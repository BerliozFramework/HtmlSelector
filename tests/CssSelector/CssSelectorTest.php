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

namespace Berlioz\HtmlSelector\Tests\CssSelector;

use Berlioz\HtmlSelector\CssSelector\CssSelector;
use Berlioz\HtmlSelector\CssSelector\NextCssSelector;
use PHPUnit\Framework\TestCase;

class CssSelectorTest extends TestCase
{
    public function test()
    {
        $selector = new CssSelector(
            selector: $selectorStr = 'input#foo.bar.baz[qux="value"]["baz" != "value"][bar]:pseudo(1):pseudo2',
            type: $type = 'input',
            id: $id = '#foo',
            classes: $classes = ['bar', 'baz'],
            attributes: $attributes = [
            [
                'name' => 'qux',
                'comparison' => '=',
                'value' => 'value',
            ],
            [
                'name' => 'baz',
                'comparison' => '!=',
                'value' => 'value',
            ],
            [
                'name' => 'bar',
                'comparison' => null,
                'value' => null,
            ],
        ],
            pseudoClasses: $pseudoClasses = [
            'pseudo' => '1',
            'pseudo2' => null
        ],
        );

        $this->assertEquals($selectorStr, (string)$selector);
        $this->assertEquals($type, $selector->getType());
        $this->assertEquals($id, $selector->getId());
        $this->assertEquals($classes, $selector->getClasses());
        $this->assertEquals($attributes, $selector->getAttributes());
        $this->assertEquals($pseudoClasses, $selector->getPseudoClasses());
    }

    public function testNext()
    {
        $selector = new CssSelector('');

        $this->assertNull($selector->getNext());

        $selector->setNext($next = new NextCssSelector(new CssSelector(''), '>'));

        $this->assertSame($next, $selector->getNext());
    }
}
