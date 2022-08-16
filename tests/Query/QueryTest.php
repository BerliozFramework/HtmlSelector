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
use Berlioz\HtmlSelector\Query\Query;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    public function testIndex()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('li:eq(2)');
        $this->assertEquals(2, (string)$result->index(), (string)$result->getSelector());

        $result = $query->find('li');
        $this->assertEquals(
            4,
            (string)$result->index('[role=main] ul:eq(0) > li:lt(2)'),
            (string)$result->getSelector()
        );

        $result2 = $query->find('li:eq(2)');
        $this->assertEquals(2, (string)$result->index($result2), (string)$result->getSelector());
    }

    public function testFilter()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('[role=main] ul:eq(0) > li');
        $result = $result->filter('.second');

        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Second element of list 1', (string)$result->get(0), (string)$result->getSelector());
    }

    public function testIs()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('[role=main]');

        $this->assertCount(1, $result);
        $this->assertTrue($result->is('[role=main]'));
        $this->assertTrue($result->is($result));
        $this->assertFalse($result->is('[role=fake]'));
    }

    public function testNot()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('[role=main] ul:eq(0) > li');
        $result = $result->not('.second');

        $this->assertCount(2, $result, (string)$result->getSelector());
        $this->assertEquals('First element of list 1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Third element of list 1', (string)$result->get(1), (string)$result->getSelector());
    }

    public function testParent()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('h1')->parent();

        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('div', $result->get(0)->getName(), (string)$result->getSelector());
        $this->assertEquals(
            'starter-template',
            $result->get(0)->attributes()->{'class'},
            (string)$result->getSelector()
        );
    }

    public function testChildren()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('[aria-labelledby="dropdown01"]');
        $result = $result->children();

        $this->assertCount(3, $result, (string)$result->getSelector());
    }

    public function testAttr()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('main p:first');
        $this->assertEquals('en-us', $result->attr('lang'), (string)$result->getSelector());
        $this->assertEquals('center', $result->attr('align'), (string)$result->getSelector());

        $result->attr('align', 'left');
        $this->assertEquals('left', $result->attr('align'), (string)$result->getSelector());
        $this->assertNull($result->attr('test'), (string)$result->getSelector());

        $result->attr('valign', 'top');
        $this->assertEquals('top', $result->attr('valign'), (string)$result->getSelector());
    }

    public function testProp()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('#formTest [name=checkbox1]');
        $this->assertFalse($result->prop('checked'), (string)$result->getSelector());

        $result = $query->find('#formTest [name=checkbox2]');
        $this->assertTrue($result->prop('checked'), (string)$result->getSelector());

        $result = $query->find('#formTest [name=checkbox3]');
        $this->assertTrue($result->prop('checked'), (string)$result->getSelector());

        $result = $query->find('#formTest [name=checkbox4]');
        $this->assertTrue($result->prop('required'), (string)$result->getSelector());

        $result = $query->find('#formTest [name=checkbox5]');
        $this->assertTrue($result->prop('disabled'), (string)$result->getSelector());

        $result->prop('disabled', false);
        $this->assertFalse($result->prop('disabled'), (string)$result->getSelector());

        $result->prop('disabled', true);
        $this->assertTrue($result->prop('disabled'), (string)$result->getSelector());
    }

    public function testData()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('#formTest');

        $this->assertEquals('valueTest', $result->data('testTest2Test3'), (string)$result->getSelector());
    }

    public function testText()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('p:lang(en-us)');
        $this->assertEquals(
            "\n      Usé this document as a way to\n      quickly start any new project. All you get is this text and a mostly barebones HTML document.\n    ",
            $result->text(),
            (string)$result->getSelector()
        );

        $result = $query->find('p:lang(en-us)');
        $this->assertEquals(
            "\n      Usé this document as a way to\n       any new project. All you get is this text and a mostly barebones HTML document.\n    ",
            $result->text(false),
            (string)$result->getSelector()
        );
    }

    public function testHtml()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('p:lang(en-us)');

        $this->assertEquals(
            "\n      Usé this document as a way to\n      <strong>quickly start</strong> any new project.<br/> All you get is this text and a mostly barebones HTML document.\n    ",
            $result->html(),
            (string)$result->getSelector()
        );
        $this->assertStringStartsWith(
            "<!DOCTYPE html><html lang=\"en\">\n<head>\n  <meta charset=\"utf-8\"/>",
            $query->html()
        );
    }

    public function testHasClass()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('[role=main] p');

        $this->assertCount(3, $result, (string)$result->getSelector());
        $this->assertTrue($result->hasClass('lead'), (string)$result->getSelector());
        $this->assertFalse($result->hasClass('test'), (string)$result->getSelector());
    }

    public function testClass_AddRemoveToggle()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('#list1 li');
        $result->addClass('classAdded1 classAdded2');

        $this->assertTrue($result->hasClass('classAdded1 classAdded2'), (string)$result->getSelector());

        $result->removeClass('classAdded2');

        $this->assertTrue($result->hasClass('classAdded1'), (string)$result->getSelector());
        $this->assertFalse($result->hasClass('classAdded2'), (string)$result->getSelector());

        $result->toggleClass('classToggled');
        $this->assertTrue($result->hasClass('classToggled'), (string)$result->getSelector());

        $result->toggleClass('classToggled', false);
        $this->assertFalse($result->hasClass('classToggled'), (string)$result->getSelector());

        $result->toggleClass('classToggled', true);
        $this->assertTrue($result->hasClass('classToggled'), (string)$result->getSelector());

        $result->toggleClass('classToggled', true);
        $this->assertTrue($result->hasClass('classToggled'), (string)$result->getSelector());
    }

    public function testNext()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('footer > div:last :first-child');
        $result2 = $result->next();

        $this->assertCount(1, $result2, (string)$result2->getSelector());
        $this->assertEquals('Contact 5', (string)$result2->get(0), (string)$result2->getSelector());

        $result2 = $result->next('button');

        $this->assertCount(0, $result2, (string)$result2->getSelector());

        $result = $query->find('footer > ul:last :eq(1)');
        $result = $result->next();

        $this->assertCount(1, $result, (string)$result2->getSelector());
        $this->assertEquals('Link 4.3', (string)$result->get(0), (string)$result->getSelector());
    }

    public function testNextAll()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('footer > div:last :first-child');
        $result2 = $result->nextAll();

        $this->assertCount(7, $result2, (string)$result2->getSelector());
        $this->assertEquals('Contact 5', (string)$result2->get(0), (string)$result2->getSelector());
        $this->assertEquals('Contact 6', (string)$result2->get(1), (string)$result2->getSelector());

        $result2 = $result->next('button');

        $this->assertCount(0, $result2, (string)$result2->getSelector());

        $result = $query->find('footer > ul:last :first-child');
        $result = $result->nextAll();

        $this->assertCount(2, $result, (string)$result2->getSelector());
        $this->assertEquals('Link 4.2', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 4.3', (string)$result->get(1), (string)$result->getSelector());
    }

    public function testPrev()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('footer > div:last :last-child');

        $result2 = $result->prev();

        $this->assertCount(1, $result2, (string)$result2->getSelector());
        $this->assertEquals('Contact 10', (string)$result2->get(0), (string)$result2->getSelector());

        $result2 = $result->prev('span');
        $this->assertCount(1, $result2, (string)$result2->getSelector());

        $result2 = $result->prev('button');
        $this->assertCount(0, $result2, (string)$result2->getSelector());

        $result = $query->find('footer > ul:last :eq(1)');
        $result = $result->prev();

        $this->assertCount(1, $result, (string)$result2->getSelector());
        $this->assertEquals('Link 4.1', (string)$result->get(0), (string)$result->getSelector());
    }

    public function testPrevAll()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('footer > div:last :last-child');
        $result2 = $result->prevAll();

        $this->assertCount(7, $result2, (string)$result2->getSelector());
        $this->assertEquals('Contact 4', (string)$result2->get(0), (string)$result2->getSelector());
        $this->assertEquals('Contact 5', (string)$result2->get(1), (string)$result2->getSelector());

        $result = $query->find('footer > ul:last :last-child');
        $result = $result->prevAll();

        $this->assertCount(2, $result, (string)$result2->getSelector());
        $this->assertEquals('Link 4.1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 4.2', (string)$result->get(1), (string)$result->getSelector());

        $result = $query->find('footer > div:last :eq(3)');
        $result = $result->prevAll();
        $this->assertCount(3, $result, (string)$result->getSelector());
        $this->assertEquals('Contact 4', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Contact 5', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Contact 6', (string)$result->get(2), (string)$result->getSelector());
    }

    public function testSerializeArray()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('form#formTest');

        $this->assertCount(11, $result->serializeArray(), '"form#formTest".serializeArray()');

        $result = $query->find('form#formTest select');

        $this->assertCount(2, $result->serializeArray(), '"form#formTest select".serializeArray()');
    }

    public function testSerialize()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);

        $result = $query->find('form#formTest');

        $this->assertEquals(
            'text1=&password1=&text2=&checkbox2=&checkbox3=&radio=radio2&select1%5B%5D=option2&select1%5B%5D=Option+3&textarea1=Text+inside.&file1%5B%5D=&image1=',
            $result->serialize(),
            '"form#formTest".serialize()'
        );
    }

    public function testMap()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);
        $result = $query->find('footer > ul li')->map(fn(Query $query) => $query->text());

        $this->assertEquals(
            [
                'Link 1.1',
                'Link 1.2',
                'Link 1.3',
                'Link 1.4',
                'Link 1.5',
                'Link 1.6',
                'Link 1.7',
                'Link 1.8',
                'Link 1.9',
                'Link 1.10',
                'Link 1.11',
                'Link 1.12',
                'Link 1.13',
                'Link 1.14',
                'Link 2.1',
                'Link 3.1',
                'Link 3.2',
                'Link 4.1',
                'Link 4.2',
                'Link 4.3'
            ],
            $result
        );
    }

    public function testMap_withKey()
    {
        $htmlSelector = new HtmlSelector();
        $query = $htmlSelector->query(__DIR__ . '/../files/test.html', true);
        $result = $query
            ->find('footer > ul li')
            ->map(function (Query $query, &$key) {
                $key = 'link' . ($key + 1);
                return $query->text();
            });

        $this->assertEquals(
            [
                'link1' => 'Link 1.1',
                'link2' => 'Link 1.2',
                'link3' => 'Link 1.3',
                'link4' => 'Link 1.4',
                'link5' => 'Link 1.5',
                'link6' => 'Link 1.6',
                'link7' => 'Link 1.7',
                'link8' => 'Link 1.8',
                'link9' => 'Link 1.9',
                'link10' => 'Link 1.10',
                'link11' => 'Link 1.11',
                'link12' => 'Link 1.12',
                'link13' => 'Link 1.13',
                'link14' => 'Link 1.14',
                'link15' => 'Link 2.1',
                'link16' => 'Link 3.1',
                'link17' => 'Link 3.2',
                'link18' => 'Link 4.1',
                'link19' => 'Link 4.2',
                'link20' => 'Link 4.3'
            ],
            $result
        );
    }
}