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

use Berlioz\HtmlSelector\Extension\QueryExtension;
use Berlioz\HtmlSelector\HtmlSelector;
use Berlioz\HtmlSelector\PseudoClass\PseudoClassInterface;
use PHPUnit\Framework\TestCase;

class QueryExtensionTest extends TestCase
{
    public function testGetPseudoClasses()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertCount(23, $extension->getPseudoClasses());
        $this->assertContainsOnlyInstancesOf(PseudoClassInterface::class, $extension->getPseudoClasses());
    }

    public function testInput()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[name() = "input" or name() = "textarea" or name() = "select" or name() = "button"]',
            $extension->input('XPATH')
        );
    }

    public function testEq()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            '(XPATH)[position() = 2]',
            $extension->eq('XPATH', 1)
        );
        $this->assertEquals(
            '(XPATH)[last() - position() = 0]',
            $extension->eq('XPATH', -1)
        );
    }

    public function testLt()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            '(XPATH)[position() < 2]',
            $extension->lt('XPATH', 1)
        );
        $this->assertEquals(
            '(XPATH)[last() - position() > 0]',
            $extension->lt('XPATH', -1)
        );
    }

    public function testLte()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            '(XPATH)[position() <= 2]',
            $extension->lte('XPATH', 1)
        );
        $this->assertEquals(
            '(XPATH)[last() - position() >= 0]',
            $extension->lte('XPATH', -1)
        );
    }

    public function testGt()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            '(XPATH)[position() > 2]',
            $extension->gt('XPATH', 1)
        );
        $this->assertEquals(
            '(XPATH)[last() - position() < 0]',
            $extension->gt('XPATH', -1)
        );
    }

    public function testGte()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            '(XPATH)[position() >= 2]',
            $extension->gte('XPATH', 1)
        );
        $this->assertEquals(
            '(XPATH)[last() - position() <= 0]',
            $extension->gte('XPATH', -1)
        );
    }

    public function testReset()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[@type="reset"]',
            $extension->reset('XPATH')
        );
    }

    public function testSubmit()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[( name() = "button" or name() = "input" ) and @type = "submit"]',
            $extension->submit('XPATH')
        );
    }

    public function testSelected()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[name() = "option" and @selected]',
            $extension->selected('XPATH')
        );
    }

    public function testLast()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            '(XPATH)[last()]',
            $extension->last('XPATH')
        );
    }

    public function testOdd()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            '(XPATH)[position() mod 2 = 1]',
            $extension->odd('XPATH')
        );
    }

    public function testFile()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[@type="file"]',
            $extension->file('XPATH')
        );
    }

    public function testCheckbox()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[@type = "checkbox"]',
            $extension->checkbox('XPATH')
        );
    }

    public function testRadio()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[@type="radio"]',
            $extension->radio('XPATH')
        );
    }

    public function testPassword()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[@type="password"]',
            $extension->password('XPATH')
        );
    }

    public function testEven()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            '(XPATH)[position() mod 2 != 1]',
            $extension->even('XPATH')
        );
    }

    public function testContains()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[contains(text(), "ARGUMENTS")]',
            $extension->contains('XPATH', 'ARGUMENTS')
        );
    }

    public function testText()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[name() = "input" and ( @type="text" or not( @type ) )]',
            $extension->text('XPATH')
        );
    }

    public function testButton()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[( name() = "button" and @type != "submit" ) or ( name() = "input" and @type = "button" )]',
            $extension->button('XPATH')
        );
    }

    public function testHeader()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[name() = "h1" or name() = "h2" or name() = "h3" or name() = "h4" or name() = "h5" or name() = "h6"]',
            $extension->header('XPATH')
        );
    }

    public function testParent()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[normalize-space()]',
            $extension->parent('XPATH')
        );
    }

    public function testImage()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals(
            'XPATH[@type="image"]',
            $extension->image('XPATH')
        );
    }

    public function testCount()
    {
        $extension = new QueryExtension(new HtmlSelector());

        $this->assertEquals('XPATH[last() = 2]', $extension->count('XPATH', '2'));
        $this->assertEquals('XPATH[last() = 2]', $extension->count('XPATH', '= 2'));
        $this->assertEquals('XPATH[last() != 2]', $extension->count('XPATH', '!= 2'));
        $this->assertEquals('XPATH[last() > 2]', $extension->count('XPATH', '> 2'));
        $this->assertEquals('XPATH[last() >= 2]', $extension->count('XPATH', '>= 2'));
        $this->assertEquals('XPATH[last() < 2]', $extension->count('XPATH', '< 2'));
        $this->assertEquals('XPATH[last() <= 2]', $extension->count('XPATH', '<= 2'));
    }
}
