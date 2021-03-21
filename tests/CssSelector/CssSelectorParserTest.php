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
use Berlioz\HtmlSelector\CssSelector\CssSelectorParser;
use PHPUnit\Framework\TestCase;

class CssSelectorParserTest extends TestCase
{
    public function provider(): array
    {
        return [
            [
                'selectorStr' => 'input[type=text][required]:disabled',
                'expected' => [
                    [
                        'type' => 'input',
                        'id' => null,
                        'classes' => [],
                        'attributes' => [
                            ['name' => 'type', 'comparison' => '=', 'value' => 'text'],
                            ['name' => 'required', 'comparison' => null, 'value' => null],
                        ],
                        'pseudoClasses' => ['disabled' => null]
                    ],
                ],
            ],
            [
                'selectorStr' => '#foo.bar.baz:nth-child(n+1)',
                'expected' => [
                    [
                        'type' => null,
                        'id' => 'foo',
                        'classes' => ['bar', 'baz'],
                        'attributes' => [],
                        'pseudoClasses' => ['nth-child' => 'n+1'],
                    ],
                ]
            ],
            [
                'selectorStr' => '#foo.bar, input.baz[type=text]',
                'expected' => [
                    [
                        'type' => null,
                        'id' => 'foo',
                        'classes' => ['bar'],
                        'attributes' => [],
                        'pseudoClasses' => [],
                    ],
                    [
                        'type' => 'input',
                        'id' => null,
                        'classes' => ['baz'],
                        'attributes' => [['name' => 'type', 'comparison' => '=', 'value' => 'text']],
                        'pseudoClasses' => [],
                    ],
                ]
            ],
            [
                'selectorStr' => '[role=main] ul:eq(0) > li',
                'expected' => [
                    [
                        'type' => null,
                        'id' => null,
                        'classes' => [],
                        'attributes' => [['name' => 'role', 'comparison' => '=', 'value' => 'main']],
                        'pseudoClasses' => [],
                        'next' => [
                            'predecessor' => null,
                            'type' => 'ul',
                            'id' => null,
                            'classes' => [],
                            'attributes' => [],
                            'pseudoClasses' => ['eq' => '0'],
                            'next' => [
                                'predecessor' => '>',
                                'type' => 'li',
                                'id' => null,
                                'classes' => [],
                                'attributes' => [],
                                'pseudoClasses' => [],
                            ],
                        ],
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider provider
     *
     * @param string $selectorStr
     * @param array $expected
     */
    public function testParse(string $selectorStr, array $expected)
    {
        $selectorParser = new CssSelectorParser();
        $selectorSet = $selectorParser->parse($selectorStr);

        $this->assertCount(count($expected), $selectorSet);

        foreach ($selectorSet->all() as $iSelector => $selector) {
            $this->subtestSelector($selector, $expected[$iSelector]);
        }
    }

    public function subtestSelector(CssSelector $selector, array $expected)
    {
        $this->assertEquals($expected['type'], $selector->getType());
        $this->assertEquals($expected['id'], $selector->getId());
        $this->assertEquals($expected['classes'], $selector->getClasses());
        $this->assertEquals($expected['attributes'], $selector->getAttributes());
        $this->assertEquals($expected['pseudoClasses'], $selector->getPseudoClasses());

        if (isset($expected['next'])) {
            $this->assertEquals($expected['next']['predecessor'], $selector->getNext()?->getPredecessor());
            $this->subtestSelector($selector->getNext()->getSelector(), $expected['next']);
        }
    }
}
