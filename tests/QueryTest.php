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


use Berlioz\HtmlSelector\Query;
use Berlioz\Http\Message\Response;
use Berlioz\Http\Message\Stream;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    public function testUtf8()
    {
        $query = Query::loadHtml(__DIR__ . '/files/test_utf8.html', true);
        $result = $query->find('head > title');

        $this->assertEquals('Test éèà', $result->text());
    }

    public function testLoadResponseInterface()
    {
        $body = new Stream(fopen(__DIR__ . '/files/test_encoding.html', 'r'));
        $response = new Response($body, 200, ['Content-Type' => 'text/html; charset=ISO-8859-1']);
        $query = Query::loadResponse($response);

        $result = $query->find('h1');
        $this->assertEquals('Ceci est un test avec des accents éàèô', $result->text());

        $result = $query->find('head > meta[name=description]');
        $this->assertEquals('Accès à l\'espace', $result->attr('content'));
    }

    /**
     * Provider to test HTML files.
     *
     * @return array
     */
    public function htmlFilesDataProvider()
    {
        return [
            ['files/test1.html'],
            ['files/test2.html'],
            ['files/test3.html'],
            ['files/test4.html'],
            ['files/test5.html'],
        ];
    }

    /**
     * Test query init with files.
     *
     * @param string $file File name
     *
     * @dataProvider htmlFilesDataProvider
     */
    public function testQueryInit($file)
    {
        $this->assertInstanceOf(Query::class, Query::loadHtml(__DIR__ . '/' . $file, true));
    }

    /**
     * Test query selector with HTML files.
     *
     * @param string $file File name
     *
     * @dataProvider htmlFilesDataProvider
     */
    public function testQuerySelector($file)
    {
        $query = Query::loadHtml(__DIR__ . '/' . $file, true);
        $result = $query->find('h1');
        $this->assertCount(1, $result);
    }

    /**
     * Test query selector with different depth.
     */
    public function testQueryDepth()
    {
        $query = Query::loadHtml(__DIR__ . '/files/test.html', true);

        // Descendants
        $result = $query->find('[role=main] ul:eq(1)');
        $this->assertCount(1, $result);

        // Descendants of query
        $result = $result->find('li.second');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Second element of list 2', (string)$result->get(0), (string)$result->getSelector());

        // Children
        $result = $query->find('body > main');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $result = $query->find('body > ul');
        $this->assertCount(0, $result, (string)$result->getSelector());

        // Next
        $result = $query->find('#myId > :eq(0) + i');
        $this->assertCount(0, $result, (string)$result->getSelector());
        $result = $query->find('#myId > :eq(0) + span');
        $this->assertCount(1, $result, (string)$result->getSelector());

        // Next all
        $result = $query->find('#myId > :eq(0) ~ i');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $result = $query->find('#myId > :eq(0) ~ span');
        $this->assertCount(2, $result, (string)$result->getSelector());
    }

    /**
     * Test query methods.
     */
    public function testQueryMethods()
    {
        $query = Query::loadHtml(__DIR__ . '/files/test.html', true);

        // index()
        $result = $query->find('li:eq(2)');
        $this->assertEquals(2, (string)$result->index(), (string)$result->getSelector());
        $result = $query->find('li');
        $this->assertEquals(4, (string)$result->index('[role=main] ul:eq(0) > li:lt(2)'), (string)$result->getSelector());
        $result2 = $query->find('li:eq(2)');
        $this->assertEquals(2, (string)$result->index($result2), (string)$result->getSelector());

        // filter()
        $result = $query->find('[role=main] ul:eq(0) > li');
        $result = $result->filter('.second');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Second element of list 1', (string)$result->get(0), (string)$result->getSelector());

        // not()
        $result = $query->find('[role=main] ul:eq(0) > li');
        $result = $result->not('.second');
        $this->assertCount(2, $result, (string)$result->getSelector());
        $this->assertEquals('First element of list 1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Third element of list 1', (string)$result->get(1), (string)$result->getSelector());

        // parent()
        $result = $query->find('h1')->parent();
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('div', $result->get(0)->getName(), (string)$result->getSelector());
        $this->assertEquals('starter-template', $result->get(0)->attributes()->{'class'}, (string)$result->getSelector());

        // children()
        $result = $query->find('[aria-labelledby="dropdown01"]');
        $result = $result->children();
        $this->assertCount(3, $result, (string)$result->getSelector());

        // attr()
        $result = $query->find('main p:first');
        $this->assertEquals('en-us', $result->attr('lang'), (string)$result->getSelector());
        $this->assertEquals('center', $result->attr('align'), (string)$result->getSelector());
        $result->attr('align', 'left');
        $this->assertEquals('left', $result->attr('align'), (string)$result->getSelector());
        $this->assertNull($result->attr('test'), (string)$result->getSelector());
        $result->attr('valign', 'top');
        $this->assertEquals('top', $result->attr('valign'), (string)$result->getSelector());

        // prop()
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

        // data()
        $result = $query->find('#formTest');
        $this->assertEquals('valueTest', $result->data('testTest2Test3'), (string)$result->getSelector());

        // text()
        $result = $query->find('p:lang(en-us)');
        $this->assertEquals("\n      Usé this document as a way to\n      quickly start any new project. All you get is this text and a mostly barebones HTML document.\n    ", $result->text(), (string)$result->getSelector());
        $result = $query->find('p:lang(en-us)');
        $this->assertEquals("\n      Usé this document as a way to\n       any new project. All you get is this text and a mostly barebones HTML document.\n    ", $result->text(false), (string)$result->getSelector());

        // html()
        $result = $query->find('p:lang(en-us)');
        $this->assertEquals("\n      Usé this document as a way to\n      <strong>quickly start</strong> any new project.<br/> All you get is this text and a mostly barebones HTML document.\n    ", $result->html(), (string)$result->getSelector());
        $this->assertStringStartsWith("<!DOCTYPE html><html lang=\"en\">\n<head>\n  <meta charset=\"utf-8\"/>", $query->html());

        // hasClass()
        $result = $query->find('[role=main] p');
        $this->assertCount(3, $result, (string)$result->getSelector());
        $this->assertTrue($result->hasClass('lead'), (string)$result->getSelector());
        $this->assertFalse($result->hasClass('test'), (string)$result->getSelector());

        // addClass()
        $result = $query->find('#list1 li');
        $result->addClass('classAdded1 classAdded2');
        $this->assertTrue($result->hasClass('classAdded1 classAdded2'), (string)$result->getSelector());

        // removeClass()
        $result->removeClass('classAdded2');
        $this->assertTrue($result->hasClass('classAdded1'), (string)$result->getSelector());
        $this->assertFalse($result->hasClass('classAdded2'), (string)$result->getSelector());

        // toggleClass()
        $result->toggleClass('classToggled');
        $this->assertTrue($result->hasClass('classToggled'), (string)$result->getSelector());
        $result->toggleClass('classToggled', false);
        $this->assertFalse($result->hasClass('classToggled'), (string)$result->getSelector());
        $result->toggleClass('classToggled', true);
        $this->assertTrue($result->hasClass('classToggled'), (string)$result->getSelector());
        $result->toggleClass('classToggled', true);
        $this->assertTrue($result->hasClass('classToggled'), (string)$result->getSelector());

        // next()
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

        // nextAll()
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

        // prev()
        $result = $query->find('footer > div:last :last-child');
        $result2 = $result->prev();
        $this->assertCount(1, $result2, (string)$result2->getSelector());
        $this->assertEquals('Contact 4', (string)$result2->get(0), (string)$result2->getSelector());
        $result2 = $result->prev('span');
        $this->assertCount(0, $result2, (string)$result2->getSelector());
        $result = $query->find('footer > ul:last :eq(1)');
        $result = $result->prev();
        $this->assertCount(1, $result, (string)$result2->getSelector());
        $this->assertEquals('Link 4.1', (string)$result->get(0), (string)$result->getSelector());

        // prevAll()
        $result = $query->find('footer > div:last :last-child');
        $result2 = $result->prevAll();
        $this->assertCount(7, $result2, (string)$result2->getSelector());
        $this->assertEquals('Contact 4', (string)$result2->get(0), (string)$result2->getSelector());
        $this->assertEquals('Contact 5', (string)$result2->get(1), (string)$result2->getSelector());
        $result2 = $result->prev('span');
        $this->assertCount(0, $result2, (string)$result2->getSelector());
        $result = $query->find('footer > ul:last :last-child');
        $result = $result->prevAll();
        $this->assertCount(2, $result, (string)$result2->getSelector());
        $this->assertEquals('Link 4.1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 4.2', (string)$result->get(1), (string)$result->getSelector());

        // serializeArray()
        $result = $query->find('form#formTest');
        $this->assertCount(11, $result->serializeArray(), '"form#formTest".serializeArray()');
        $result = $query->find('form#formTest select');
        $this->assertCount(2, $result->serializeArray(), '"form#formTest select".serializeArray()');

        // serialize()
        $result = $query->find('form#formTest');
        $this->assertEquals(
            'text1=&password1=&text2=&checkbox2=&checkbox3=&radio=radio2&select1%5B%5D=option2&select1%5B%5D=Option+3&textarea1=Text+inside.&file1%5B%5D=&image1=',
            $result->serialize(),
            '"form#formTest".serialize()'
        );
    }

    /**
     * Test query with selector who have filters.
     * Test all filters.
     */
    public function testQuerySelectorWithFiltersResult()
    {
        $query = Query::loadHtml(__DIR__ . '/files/test.html', true);

        // :any
        $result = $query->find('body :any(ul, p)');
        $this->assertCount(11, $result, (string)$result->getSelector());
        $result = $query->find('body :any(ul, p) li');
        $this->assertCount(33, $result, (string)$result->getSelector());

        // :any-link
        $result = $query->find('body :any-link');
        $this->assertCount(8, $result, (string)$result->getSelector());

        // :dir()
        $result = $query->find('main[role=main] .starter-template p:dir(ltr)');
        $this->assertCount(2, $result, (string)$result->getSelector());
        $result = $query->find('main[role=main] .starter-template p:dir(rtl)');
        $this->assertCount(1, $result, (string)$result->getSelector());

        // :empty
        $result = $query->find('main[role=main] .starter-template :blank');
        $this->assertCount(2, $result, (string)$result->getSelector());

        // :button
        $result = $query->find('body :button');
        $this->assertCount(2, $result, (string)$result->getSelector());
        $this->assertEquals('Toggle navigation', (string)$result->get(0)->attributes()->{'aria-label'}, (string)$result->getSelector());
        $this->assertEquals('form-button', (string)$result->get(1)->attributes()->{'name'}, (string)$result->getSelector());

        // :checkbox
        $result = $query->find('form :checkbox');
        $this->assertCount(5, $result, (string)$result->getSelector());
        $this->assertEquals('checkbox1', (string)$result->get(0)->attributes()->{'name'}, (string)$result->getSelector());
        $this->assertEquals('checkbox2', (string)$result->get(1)->attributes()->{'name'}, (string)$result->getSelector());
        $this->assertEquals('checkbox3', (string)$result->get(2)->attributes()->{'name'}, (string)$result->getSelector());
        $this->assertEquals('checkbox4', (string)$result->get(3)->attributes()->{'name'}, (string)$result->getSelector());
        $this->assertEquals('checkbox5', (string)$result->get(4)->attributes()->{'name'}, (string)$result->getSelector());

        // :checked
        $result = $query->find('form :checked');
        $this->assertCount(5, $result, (string)$result->getSelector());
        $this->assertEquals('checkbox2', (string)$result->get(0)->attributes()->{'name'}, (string)$result->getSelector());
        $this->assertEquals('checkbox3', (string)$result->get(1)->attributes()->{'name'}, (string)$result->getSelector());
        $this->assertEquals('radio2', (string)$result->get(2)->attributes()->{'value'}, (string)$result->getSelector());

        // :checkbox:checked
        $result = $query->find('form :checkbox:checked');
        $this->assertCount(2, $result, (string)$result->getSelector());
        $this->assertEquals('checkbox2', (string)$result->get(0)->attributes()->{'name'}, (string)$result->getSelector());
        $this->assertEquals('checkbox3', (string)$result->get(1)->attributes()->{'name'}, (string)$result->getSelector());

        // :contains
        $result = $query->find('main[role=main] :contains(document as a way)');
        $this->assertCount(1, $result, (string)$result->getSelector());

        // :count
        $result = $query->find('footer li:count(3)');
        $this->assertCount(3, $result, (string)$result->getSelector());
        $this->assertEquals('Link 4.1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 4.2', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Link 4.3', (string)$result->get(2), (string)$result->getSelector());
        $result = $query->find('footer ul:has(li:count(3))');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $result = $query->find('footer ul:has(li:count(>=3))');
        $this->assertCount(2, $result, (string)$result->getSelector());
        $result = $query->find('footer ul:has(li:count(=3))');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $result = $query->find('footer ul:has(li:count(>3))');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $result = $query->find('footer ul:has(li:count(<3))');
        $this->assertCount(2, $result, (string)$result->getSelector());

        // :disabled
        $result = $query->find('main[role=main] :disabled');
        $this->assertCount(1, $result, (string)$result->getSelector());

        // :empty
        $result = $query->find('main[role=main] .starter-template :empty');
        $this->assertCount(7, $result, (string)$result->getSelector());

        // :enabled
        $result = $query->find('main[role=main] form > :enabled');
        $this->assertCount(17, $result, (string)$result->getSelector());

        // :eq
        $result = $query->find('main[role=main] li:eq(4)');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Second element of list 2', (string)$result->get(0), (string)$result->getSelector());
        $result = $query->find('main[role=main] li:eq(-2)');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Second element of list 3', (string)$result->get(0), (string)$result->getSelector());

        // :even
        $result = $query->find('main[role=main] li:even');
        $this->assertCount(4, $result, (string)$result->getSelector());
        $this->assertEquals('Second element of list 1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('First element of list 2', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Third element of list 2', (string)$result->get(2), (string)$result->getSelector());
        $this->assertEquals('Second element of list 3', (string)$result->get(3), (string)$result->getSelector());

        // :file
        $result = $query->find('main[role=main] form > :file');
        $this->assertCount(1, $result, (string)$result->getSelector());

        // :first
        $result = $query->find('main[role=main] li:first');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('First element of list 1', (string)$result->get(0), (string)$result->getSelector());
        // :first-child
        $result = $query->find('main[role=main] li:first-child');
        $this->assertCount(3, $result, (string)$result->getSelector());
        $this->assertEquals('First element of list 1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('First element of list 2', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('First element of list 3', (string)$result->get(2), (string)$result->getSelector());
        // :first-of-type
        $result = $query->find('footer div:last button:first-of-type');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Contact 4', (string)$result->get(0), (string)$result->getSelector());
        $result = $query->find('footer div:last span:first-of-type');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Contact 5', (string)$result->get(0), (string)$result->getSelector());
        $result = $query->find('footer div:last button:first-of-type');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Contact 4', (string)$result->get(0), (string)$result->getSelector());

        // :gt
        $result = $query->find('main[role=main] li:gt(6)');
        $this->assertCount(2, $result, (string)$result->getSelector());
        $this->assertEquals('Second element of list 3', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Third element of list 3', (string)$result->get(1), (string)$result->getSelector());
        $result = $query->find('main[role=main] li:gt(-2)');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Third element of list 3', (string)$result->get(0), (string)$result->getSelector());

        // :gt:lt
        $result = $query->find('footer ul:first :gt(2):lt(5)');
        $this->assertCount(5, $result, (string)$result->getSelector());
        $result = $query->find('footer ul:first :lt(5):gt(2)');
        $this->assertCount(2, $result, (string)$result->getSelector());

        // :gte
        $result = $query->find('main[role=main] li:gte(6)');
        $this->assertCount(3, $result, (string)$result->getSelector());
        $this->assertEquals('First element of list 3', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Second element of list 3', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Third element of list 3', (string)$result->get(2), (string)$result->getSelector());
        $result = $query->find('main[role=main] li:gte(-2)');
        $this->assertCount(2, $result, (string)$result->getSelector());
        $this->assertEquals('Second element of list 3', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Third element of list 3', (string)$result->get(1), (string)$result->getSelector());

        // :has
        $result = $query->find('main[role=main] p:has(span:contains(foo))');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('p', $result->get(0)->getName(), (string)$result->getSelector());

        // :header
        $result = $query->find('main[role=main] :header');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Bootstrap starter template', (string)$result->get(0), (string)$result->getSelector());

        // :image
        $result = $query->find('main[role=main] form > :image');
        $this->assertCount(1, $result, (string)$result->getSelector());

        // :input
        $result = $query->find('main[role=main] :input');
        $this->assertCount(18, $result, (string)$result->getSelector());

        // :lang
        $result = $query->find(':lang(en)');
        $this->assertCount(2, $result, (string)$result->getSelector());
        $this->assertEquals('html', $result->get(0)->getName(), (string)$result->getSelector());
        $this->assertEquals('p', $result->get(1)->getName(), (string)$result->getSelector());

        // :last
        $result = $query->find('main[role=main] li:last');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Third element of list 3', (string)$result->get(0), (string)$result->getSelector());
        // :last-child
        $result = $query->find('main[role=main] li:last-child');
        $this->assertCount(3, $result, (string)$result->getSelector());
        $this->assertEquals('Third element of list 1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Third element of list 2', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Third element of list 3', (string)$result->get(2), (string)$result->getSelector());
        // :last-of-type
        $result = $query->find('footer div:last button:last-of-type');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Contact 11', (string)$result->get(0), (string)$result->getSelector());
        $result = $query->find('footer div:last span:last-of-type');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Contact 10', (string)$result->get(0), (string)$result->getSelector());

        // :lt
        $result = $query->find('main[role=main] li:lt(3)');
        $this->assertCount(3, $result, (string)$result->getSelector());
        $this->assertEquals('First element of list 1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Second element of list 1', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Third element of list 1', (string)$result->get(2), (string)$result->getSelector());
        $result = $query->find('main[role=main] li:lt(-5)');
        $this->assertCount(4, $result, (string)$result->getSelector());
        $this->assertEquals('First element of list 1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Second element of list 1', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Third element of list 1', (string)$result->get(2), (string)$result->getSelector());
        $this->assertEquals('First element of list 2', (string)$result->get(3), (string)$result->getSelector());

        // :lte
        $result = $query->find('main[role=main] li:lte(3)');
        $this->assertCount(4, $result, (string)$result->getSelector());
        $this->assertEquals('First element of list 1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Second element of list 1', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Third element of list 1', (string)$result->get(2), (string)$result->getSelector());
        $this->assertEquals('First element of list 2', (string)$result->get(3), (string)$result->getSelector());
        $result = $query->find('main[role=main] li:lte(-5)');
        $this->assertCount(5, $result, (string)$result->getSelector());
        $this->assertEquals('First element of list 1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Second element of list 1', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Third element of list 1', (string)$result->get(2), (string)$result->getSelector());
        $this->assertEquals('First element of list 2', (string)$result->get(3), (string)$result->getSelector());
        $this->assertEquals('Second element of list 2', (string)$result->get(4), (string)$result->getSelector());

        // not()
        $result = $query->find('[role=main] ul:eq(0) > li:not(.second)');
        $this->assertCount(2, $result, (string)$result->getSelector());
        $this->assertEquals('First element of list 1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Third element of list 1', (string)$result->get(1), (string)$result->getSelector());

        // :nth-child
        $result = $query->find('footer ul:first :nth-child(2n)');
        $this->assertCount(7, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.2', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.4', (string)$result->get(1), (string)$result->getSelector());
        $result = $query->find('footer ul:first :nth-child(2n of .important)');
        $this->assertCount(3, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.3', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.8', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Link 1.10', (string)$result->get(2), (string)$result->getSelector());
        $result = $query->find('footer ul:first :nth-child(n+5)');
        $this->assertCount(10, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.5', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.14', (string)$result->get(9), (string)$result->getSelector());
        $result = $query->find('footer ul:first :nth-child(3n+3)');
        $this->assertCount(4, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.3', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.6', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Link 1.9', (string)$result->get(2), (string)$result->getSelector());
        $this->assertEquals('Link 1.12', (string)$result->get(3), (string)$result->getSelector());
        $result = $query->find('footer ul:first :nth-child(3n-2)');
        $this->assertCount(5, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.4', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Link 1.7', (string)$result->get(2), (string)$result->getSelector());
        $this->assertEquals('Link 1.10', (string)$result->get(3), (string)$result->getSelector());
        $this->assertEquals('Link 1.13', (string)$result->get(4), (string)$result->getSelector());
        $result = $query->find('footer ul:first :nth-child(-3n+8)');
        $this->assertCount(3, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.2', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.5', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Link 1.8', (string)$result->get(2), (string)$result->getSelector());
        $result = $query->find('footer ul:first :nth-child(-2n+7)');
        $this->assertCount(4, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.3', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Link 1.5', (string)$result->get(2), (string)$result->getSelector());
        $this->assertEquals('Link 1.7', (string)$result->get(3), (string)$result->getSelector());
        $result = $query->find('footer ul:first :nth-child(odd)');
        $this->assertCount(7, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.13', (string)$result->get(6), (string)$result->getSelector());
        $result = $query->find('footer ul:first :nth-child(even)');
        $this->assertCount(7, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.2', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.14', (string)$result->get(6), (string)$result->getSelector());

        // :nth-last-child
        $result = $query->find('footer ul:first :nth-last-child(3n+2)');
        $this->assertCount(5, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.4', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Link 1.7', (string)$result->get(2), (string)$result->getSelector());
        $this->assertEquals('Link 1.10', (string)$result->get(3), (string)$result->getSelector());
        $this->assertEquals('Link 1.13', (string)$result->get(4), (string)$result->getSelector());
        $result = $query->find('footer ul:first :nth-last-child(3n-2)');
        $this->assertCount(5, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.2', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.5', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Link 1.8', (string)$result->get(2), (string)$result->getSelector());
        $this->assertEquals('Link 1.11', (string)$result->get(3), (string)$result->getSelector());
        $this->assertEquals('Link 1.14', (string)$result->get(4), (string)$result->getSelector());
        $result = $query->find('footer ul:first :nth-last-child(-3n+8)');
        $this->assertCount(3, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.7', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.10', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Link 1.13', (string)$result->get(2), (string)$result->getSelector());
        $result = $query->find('footer ul:first :nth-last-child(odd)');
        $this->assertCount(7, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.2', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.14', (string)$result->get(6), (string)$result->getSelector());
        $result = $query->find('footer ul:first :nth-last-child(even)');
        $this->assertCount(7, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.13', (string)$result->get(6), (string)$result->getSelector());

        // :nth-of-type
        $result = $query->find('footer ul:first :nth-child(3n+3)');
        $this->assertCount(4, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.3', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.6', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Link 1.9', (string)$result->get(2), (string)$result->getSelector());
        $this->assertEquals('Link 1.12', (string)$result->get(3), (string)$result->getSelector());
        $result = $query->find('footer ul:first :nth-child(3n-2)');
        $this->assertCount(5, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.4', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Link 1.7', (string)$result->get(2), (string)$result->getSelector());
        $this->assertEquals('Link 1.10', (string)$result->get(3), (string)$result->getSelector());
        $this->assertEquals('Link 1.13', (string)$result->get(4), (string)$result->getSelector());
        $result = $query->find('footer ul:first :nth-child(-3n+8)');
        $this->assertCount(3, $result, (string)$result->getSelector());
        $this->assertEquals('Link 1.2', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Link 1.5', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Link 1.8', (string)$result->get(2), (string)$result->getSelector());
        $result = $query->find('footer div:last span:nth-child(2n+2)');
        $this->assertCount(2, $result, (string)$result->getSelector());
        $this->assertEquals('Contact 5', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Contact 9', (string)$result->get(1), (string)$result->getSelector());
        $result = $query->find('footer div:last span:nth-of-type(2n+2)');
        $this->assertCount(2, $result, (string)$result->getSelector());
        $this->assertEquals('Contact 8', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Contact 10', (string)$result->get(1), (string)$result->getSelector());

        // :odd
        $result = $query->find('main[role=main] li:odd');
        $this->assertCount(5, $result, (string)$result->getSelector());
        $this->assertEquals('First element of list 1', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Third element of list 1', (string)$result->get(1), (string)$result->getSelector());
        $this->assertEquals('Second element of list 2', (string)$result->get(2), (string)$result->getSelector());
        $this->assertEquals('First element of list 3', (string)$result->get(3), (string)$result->getSelector());
        $this->assertEquals('Third element of list 3', (string)$result->get(4), (string)$result->getSelector());

        // :only-child
        $result = $query->find('footer li:only-child');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Link 2.1', (string)$result->get(0), (string)$result->getSelector());

        // :optional
        $result = $query->find('form :input:optional');
        $this->assertCount(16, $result, (string)$result->getSelector());
        $result = $query->find('form input:optional');
        $this->assertCount(14, $result, (string)$result->getSelector());

        // :only-of-type
        $result = $query->find('footer button:only-of-type');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('Contact 3', (string)$result->get(0), (string)$result->getSelector());

        // :parent
        $result = $query->find('main[role=main] .starter-template :parent');
        $this->assertCount(8, $result, (string)$result->getSelector());
        $this->assertEquals('h1', (string)$result->get(0)->getName(), (string)$result->getSelector());
        $this->assertEquals('p', $result->get(1)->getName(), (string)$result->getSelector());
        $this->assertEquals('strong', $result->get(2)->getName(), (string)$result->getSelector());
        $this->assertEquals('p', $result->get(3)->getName(), (string)$result->getSelector());
        $this->assertEquals('span', $result->get(4)->getName(), (string)$result->getSelector());
        $this->assertEquals('p', $result->get(5)->getName(), (string)$result->getSelector());
        $this->assertEquals('span', $result->get(6)->getName(), (string)$result->getSelector());
        $this->assertEquals('span', $result->get(7)->getName(), (string)$result->getSelector());

        // :password
        $result = $query->find('body :password');
        $this->assertCount(1, $result, (string)$result->getSelector());

        // :radio
        $result = $query->find('main[role=main] :radio');
        $this->assertCount(3, $result, (string)$result->getSelector());

        // :read-only / :read-write
        $result = $query->find('main[role=main] *');
        $this->assertCount(55, $result, (string)$result->getSelector());
        $result = $query->find('main[role=main] :read-only');
        $this->assertCount(37, $result, (string)$result->getSelector());
        $result = $query->find('main[role=main] :read-write');
        $this->assertCount(18, $result, (string)$result->getSelector());

        // :required
        $result = $query->find('form :input:required');
        $this->assertCount(3, $result, (string)$result->getSelector());
        $result = $query->find('form input:required');
        $this->assertCount(3, $result, (string)$result->getSelector());

        // :reset
        $result = $query->find('main[role=main] :reset');
        $this->assertCount(1, $result, (string)$result->getSelector());

        // :root
        $result = $query->find(':root');
        $this->assertCount(1, $result, (string)$result->getSelector());
        $this->assertEquals('html', (string)$result->get(0)->getName(), (string)$result->getSelector());

        // :selected
        $result = $query->find('main[role=main] :selected');
        $this->assertCount(2, $result, (string)$result->getSelector());
        $this->assertEquals('Option 2', (string)$result->get(0), (string)$result->getSelector());
        $this->assertEquals('Option 3', (string)$result->get(1), (string)$result->getSelector());

        // :submit
        $result = $query->find('body :submit');
        $this->assertCount(2, $result, (string)$result->getSelector());

        // :text
        $result = $query->find(':text');
        $this->assertCount(3, $result, (string)$result->getSelector());
    }

    /**
     * Test query iterator.
     */
    public function testQueryIterator()
    {
        $query = Query::loadHtml(__DIR__ . '/files/test.html', true);
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

    /**
     * Test user defined functions.
     */
    public function testUserDefinedFunctions()
    {
        $self = $this;

        // Define function
        Query::addFunction(
            'test',
            function (Query $query, $arg1 = null, $arg2 = null) use ($self) {
                $self->assertEquals('ul', $query->get(0)->getName());
                $self->assertEquals('Argument 1', $arg1);
                $self->assertEquals('Argument 2', $arg2);
            }
        );

        $query = Query::loadHtml(__DIR__ . '/files/test.html', true);
        $result = $query->find('footer ul:first');
        $result->{'test'}('Argument 1', 'Argument 2');
    }
}
