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

class NextCssSelectorTest extends TestCase
{
    public function test()
    {
        $next = new NextCssSelector($selector = new CssSelector(''), $predecessor = '>');

        $this->assertSame($selector, $next->getSelector());
        $this->assertEquals($predecessor, $next->getPredecessor());
    }
}
