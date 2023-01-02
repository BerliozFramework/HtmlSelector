<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2023 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\HtmlSelector\Tests;

use Berlioz\HtmlSelector\HtmlSelector;
use Berlioz\Http\Message\Response;
use PHPUnit\Framework\TestCase;

class HtmlSelectorTest extends TestCase
{
    public function testQueryFromResponse()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->queryFromResponse(
            new Response(
                file_get_contents(__DIR__ . '/files/test_encoding4.html'),
                headers: [
                    'Content-Type' => 'text/html; charset=utf-8'
                ]
            )
        );

        $this->assertEquals(
            'Ceci est un test avec des accents éèàï',
            $query->find('h1')->text()
        );
    }

    public function testQueryFromResponse_base64()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->queryFromResponse(
            new Response(
                base64_decode(base64_encode('<h1>Ceci est un test avec des accents éèàï</h1>')),
                headers: [
                    'Content-Type' => 'text/html; charset=utf-8'
                ]
            )
        );

        $this->assertEquals(
            'Ceci est un test avec des accents éèàï',
            $query->find('h1')->text()
        );
    }
}
