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

namespace Berlioz\HtmlSelector\Tests\Extension;

use Berlioz\HtmlSelector\CssSelector\CssSelector;
use Berlioz\HtmlSelector\Exception\SelectorException;
use Berlioz\HtmlSelector\Extension\CssExtension;
use Berlioz\HtmlSelector\HtmlSelector;
use Berlioz\HtmlSelector\PseudoClass\PseudoClassInterface;
use PHPUnit\Framework\TestCase;

class CssExtensionTest extends TestCase
{
    public function testGetPseudoClasses()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertCount(27, $extension->getPseudoClasses());
        $this->assertContainsOnlyInstancesOf(PseudoClassInterface::class, $extension->getPseudoClasses());
    }

    public function testDisabled()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[( name() = "button" or name() = "input" or name() = "optgroup" or name() = "option" or name() = "select" or name() = "textarea" or name() = "menuitem" or name() = "fieldset" ) and @disabled]',
            $extension->disabled('XPATH')
        );
    }

    public function testEmpty()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[count(child::*) = 0]',
            $extension->empty('XPATH')
        );
    }

    public function testFirstOfType()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[last()]',
            $extension->firstOfType('XPATH', new CssSelector(selector: 'FOO', type: 'foo'))
        );
    }

    public function testFirstOfType_notSpecified()
    {
        $this->expectException(SelectorException::class);

        $extension = new CssExtension(new HtmlSelector());
        $extension->firstOfType('XPATH', new CssSelector(selector: 'FOO', type: null));
    }

    public function testFirstOfType_all()
    {
        $this->expectException(SelectorException::class);

        $extension = new CssExtension(new HtmlSelector());
        $extension->firstOfType('XPATH', new CssSelector(selector: 'FOO', type: '*'));
    }

    public function testLastChild()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[../*[last()] = node()]',
            $extension->lastChild('XPATH')
        );
    }

    public function testOnlyOfType()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[count(../foo)=1]',
            $extension->onlyOfType('XPATH', new CssSelector(selector: 'FOO', type: 'foo'))
        );
    }

    public function testOptional()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[name() = "input" or name() = "textarea" or name() = "select"][not( @required )]',
            $extension->optional('XPATH')
        );
    }

    public function testRequired()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[name() = "input" or name() = "textarea" or name() = "select"][@required]',
            $extension->required('XPATH')
        );
    }

    public function testRoot()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            '(XPATH/ancestor::*)[1]/*[1]',
            $extension->root('XPATH')
        );
    }

    public function testFirst()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            '(XPATH)[1]',
            $extension->first('XPATH')
        );
    }

    public function testLastOfType()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[last()]',
            $extension->lastOfType('XPATH', new CssSelector(selector: 'FOO', type: 'foo'))
        );
    }

    public function testLastOfType_notSpecified()
    {
        $this->expectException(SelectorException::class);

        $extension = new CssExtension(new HtmlSelector());
        $extension->lastOfType('XPATH', new CssSelector(selector: 'FOO', type: null));
    }

    public function testLastOfType_all()
    {
        $this->expectException(SelectorException::class);

        $extension = new CssExtension(new HtmlSelector());
        $extension->lastOfType('XPATH', new CssSelector(selector: 'FOO', type: '*'));
    }

    public function testBlank()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[count(child::*) = 0 and not(normalize-space())]',
            $extension->blank('XPATH')
        );
    }

    public function testHas()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[./SELECTOR]',
            $extension->has('XPATH', 'SELECTOR')
        );
    }

    public function testReadOnly()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[( not(@contenteditable) or @contenteditable = "false" ) and  not( ( name() = "input" or name() = "textarea" or name() = "select" ) and not(@readonly) and not(@disabled) )]',
            $extension->readOnly('XPATH')
        );
    }

    public function testAnyLink()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[( name() = "a" or name() = "area" or name() = "link" ) and @href]',
            $extension->anyLink('XPATH')
        );
    }

    public function testEnabled()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[( name() = "button" or name() = "input" or name() = "optgroup" or name() = "option" or name() = "select" or name() = "textarea" ) and not( @disabled )]',
            $extension->enabled('XPATH')
        );
    }

    public function testOnlyChild()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[last() = 1]',
            $extension->onlyChild('XPATH')
        );
    }

    public function testAny()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[self::ARGUMENTS]',
            $extension->any('XPATH', 'ARGUMENTS')
        );
    }

    public function testReadWrite()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[( @contenteditable and ( @contenteditable = "true" or not(normalize-space(@contenteditable)) ) ) or  ( ( name() = "input" or name() = "textarea" or name() = "select" ) and not(@readonly) and not(@disabled) )]',
            $extension->readWrite('XPATH')
        );
    }

    public function testDir()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[(ancestor-or-self::*[@dir])[last()][@dir = "ltr"]]',
            $extension->dir('XPATH', 'ltr')
        );
        $this->assertEquals(
            'XPATH[(ancestor-or-self::*[@dir])[last()][@dir = "rtl"]]',
            $extension->dir('XPATH', 'rtl')
        );
        $this->assertEquals(
            'XPATH',
            $extension->dir('XPATH', 'invalid')
        );
    }

    public function testFirstChild()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[../*[1] = node()]',
            $extension->firstChild('XPATH')
        );
    }

    public function testLang()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[@lang = "ARGUMENTS" or starts-with(@lang, "ARGUMENTS")]',
            $extension->lang('XPATH', 'ARGUMENTS')
        );
    }

    public function testChecked()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[( name() = "input" and ( @type = "checkbox" or @type = "radio" ) and @checked ) or ( name() = "option" and @selected )]',
            $extension->checked('XPATH')
        );
    }

    public function testNot()
    {
        $extension = new CssExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[not(self::ARGUMENTS)]',
            $extension->not('XPATH', 'ARGUMENTS')
        );
    }
}
