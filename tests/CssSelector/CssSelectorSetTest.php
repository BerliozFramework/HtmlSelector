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
use Berlioz\HtmlSelector\CssSelector\CssSelectorSet;
use PHPUnit\Framework\TestCase;

class CssSelectorSetTest extends TestCase
{
    public function test__toString()
    {
        $selectorSet = new CssSelectorSet(new CssSelector('#foo'), new CssSelector('#bar'));

        $this->assertEquals('#foo, #bar', (string)$selectorSet);
    }

    public function testAll()
    {
        $selectors = [new CssSelector('#foo'), new CssSelector('#bar')];
        $selectorSet = new CssSelectorSet(...$selectors);

        $this->assertCount(2, $selectorSet);
        $this->assertSame($selectors, $selectorSet->all());
    }
}
