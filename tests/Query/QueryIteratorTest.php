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

namespace Berlioz\HtmlSelector\Tests\Query;

use Berlioz\HtmlSelector\HtmlSelector;
use PHPUnit\Framework\TestCase;

class QueryIteratorTest extends TestCase
{
    public function test()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);
        $result = $query->find('footer ul:first :nth-child(2n)');
        $count = 0;
        $values = [];

        // Count and get elements individually
        foreach ($result as $value) {
            $count++;
            $values[] = $value;
        }

        // Count elements
        $this->assertEquals(7, $count);

        // Compare elements
        foreach ($values as $key => $value) {
            $this->assertEquals((string)$result->get($key), $value->text());
            $this->assertEquals($result->get($key), $value->get(0));
        }
    }
}
