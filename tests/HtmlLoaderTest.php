<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2022 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\HtmlSelector\Tests;

use Berlioz\HtmlSelector\HtmlSelector;
use Berlioz\Http\Message\Response;
use PHPUnit\Framework\TestCase;

class HtmlLoaderTest extends TestCase
{
    public function providesTest(): array
    {
        return [
            [
                'file' => __DIR__ . '/files/test_encoding.html',
                'selector' => 'body > h1',
                'expected' => 'Ceci est un test avec des accents éèàï',
            ],
            [
                'file' => __DIR__ . '/files/test_encoding2.html',
                'selector' => 'body > h1',
                'expected' => 'Ceci est un test avec des accents éèàï',
            ],
            [
                'file' => __DIR__ . '/files/test_encoding3.html',
                'selector' => 'body > h1',
                'expected' => 'Ceci est un test avec des accents éèàï',
            ],
            [
                'file' => __DIR__ . '/files/test_encoding4.html',
                'selector' => 'h1',
                'expected' => 'Ceci est un test avec des accents éèàï',
            ],
            [
                'file' => __DIR__ . '/files/test_utf8.html',
                'selector' => 'body > p',
                'expected' => 'Test éèà',
            ],
        ];
    }

    /**
     * @dataProvider providesTest
     */
    public function test(string $file, string $selector, string $expected)
    {
        $htmlSelector = new HtmlSelector();
        $this->assertEquals(
            $expected,
            $htmlSelector->query($file, true)->find($selector)->text()
        );
    }
}
