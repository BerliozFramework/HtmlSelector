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

namespace Berlioz\HtmlSelector\Tests;

use Berlioz\HtmlSelector\Exception\SelectorException;
use Berlioz\HtmlSelector\HtmlSelector;
use Berlioz\HtmlSelector\XpathSolver;
use PHPUnit\Framework\TestCase;

class XpathSolverTest extends TestCase
{
    public function provider(): array
    {
        return [
            [
                'selector' => '*',
                'xpath' => './/*'
            ],
            [
                'selector' => '*[data-foo="bar"]',
                'xpath' => './/*[@data-foo="bar"]'
            ],
            [
                'selector' => '#id',
                'xpath' => './/*[@id="id"]'
            ],
            [
                'selector' => '.foo.bar',
                'xpath' => './/*[contains(concat(" ", @class, " "), " foo ")][contains(concat(" ", @class, " "), " bar ")]'
            ],
            [
                'selector' => '[foo="value"][bar="value2"]',
                'xpath' => './/*[@foo="value"][@bar="value2"]'
            ],
            [
                'selector' => 'a[href$="php"]',
                'xpath' => './/a["php" = substring(@href, string-length(@href) - string-length("php") + 1)]'
            ],
            [
                'selector' => '[role=main] ul:eq(0) > li',
                'xpath' => '(.//*[@role="main"]//ul)[position() = 1]/li'
            ],
            [
                'selector' => 'footer button:only-of-type',
                'xpath' => './/footer//button[count(../button)=1]'
            ],
            [
                'selector' => 'main[role=main] .starter-template :parent',
                'xpath' => './/main[@role="main"]//*[contains(concat(" ", @class, " "), " starter-template ")]//*[normalize-space()]'
            ],
            [
                'selector' => 'footer ul:first :nth-child(2n of .foo)',
                'xpath' => '((.//footer//ul)[1]//*[self::*[contains(concat(" ", @class, " "), " foo ")]][position() > -2])[((last() - position() + 1) - 0) mod 2 = 0]'
            ],
        ];
    }

    /**
     * Test xpath.
     *
     * @param string $selector
     * @param string $xpath
     *
     * @throws SelectorException
     * @dataProvider provider
     */
    public function testSolve(string $selector, string $xpath)
    {
        $htmlSelector = new HtmlSelector();
        $xpathSolver = new XpathSolver($htmlSelector->getPseudoClasses());

        $this->assertEquals($xpath, $xpathSolver->solve($selector));
    }
}
