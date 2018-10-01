<?php

/*
 * Twig plugin for Kint
 * Copyright (C) 2017 Jonathan Vollebregt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Kint\Test\Twig;

use Kint\Kint;
use Kint\Parser\Parser;
use Kint\Parser\ProxyPlugin;
use Kint\Twig\TwigExtension;
use PHPUnit\Framework\TestCase;
use Twig_Environment;
use Twig_Loader_Array;

class TwigExtensionTest extends TestCase
{
    public function outputProvider()
    {
        return array(
            'basic test d' => array(
                '{{ d("magical" ~ "_" ~ "hullaballoo") }}',
                '/magical_hullaballoo/',
                true,
            ),
            'basic test s' => array(
                '{{ s("magical" ~ "_" ~ "hullaballoo") }}',
                '/magical_hullaballoo/',
                true,
            ),
            'echoless test d' => array(
                '{% set x = d("magical" ~ "_" ~ "hullaballoo") %}',
                '/magical_hullaballoo/',
                false,
            ),
            'echoless test s' => array(
                '{% set x = s("magical" ~ "_" ~ "hullaballoo") %}',
                '/magical_hullaballoo/',
                false,
            ),
            'array test d' => array(
                '{{ d(["asdf"], {"magic": "hullaballoo"}) }}',
                '/array.+?1.+?0.+?string.+?asdf.+?array.+?magic.+?string.+?hullaballoo/',
                true,
            ),
            'array test s' => array(
                '{{ s(["asdf"], {"magic": "hullaballoo"}) }}',
                '/array.+?1.+?0.+?string.+?asdf.+?array.+?magic.+?string.+?hullaballoo/s',
                true,
            ),
        );
    }

    /**
     * @dataProvider outputProvider
     *
     * @param mixed $template
     * @param mixed $regex
     * @param mixed $matches
     */
    public function testOutput($template, $regex, $matches)
    {
        $loader = new Twig_Loader_Array(array('template' => $template));
        $twig = new Twig_Environment($loader, array('debug' => true));

        $twig->addExtension(new TwigExtension());

        if ($matches) {
            $this->assertRegexp($regex, $twig->render('template'));
        } else {
            $this->assertNotRegexp($regex, $twig->render('template'));
        }
    }

    /**
     * @dataProvider outputProvider
     *
     * @covers \Kint\Twig\TwigExtension::dump
     *
     * @param mixed $template
     * @param mixed $regex
     * @param mixed $matches
     */
    public function testDisabled($template, $regex, $matches)
    {
        $loader = new Twig_Loader_Array(array('template' => $template));
        $twig = new Twig_Environment($loader);

        $twig->addExtension(new TwigExtension());

        $this->assertEquals('', $twig->render('template'));

        $twig->enableDebug();

        if ($matches) {
            $this->assertRegexp($regex, $twig->render('template'));
        } else {
            $this->assertNotRegexp($regex, $twig->render('template'));
        }

        $twig->disableDebug();

        $this->assertEquals('', $twig->render('template'));
    }

    public function customFunctionProvider()
    {
        return array(
            'basic test custom function' => array(
                '{{ dump("magical" ~ "_" ~ "hullaballoo") }}',
                '/magical_hullaballoo/',
                true,
                array('dump' => 'Kint\\Renderer\\RichRenderer'),
            ),
            'multiple test custom function' => array(
                '{{ debug("magical" ~ "_" ~ "hullaballoo") }}',
                '/magical_hullaballoo/',
                true,
                array(
                    'dump' => 'Kint\\Renderer\\RichRenderer',
                    'debug' => 'Kint\\Renderer\\RichRenderer',
                ),
            ),
        );
    }

    /**
     * @dataProvider customFunctionProvider
     * @covers \Kint\Twig\TwigExtension::dump
     * @covers \Kint\Twig\TwigExtension::getAliases
     * @covers \Kint\Twig\TwigExtension::setAliases
     *
     * @param mixed $template
     * @param mixed $regex
     * @param mixed $matches
     * @param mixed $funcs
     */
    public function testAliases($template, $regex, $matches, $funcs)
    {
        $loader = new Twig_Loader_Array(array('template' => $template));
        $twig = new Twig_Environment($loader, array('debug' => true));

        $ext = new TwigExtension();

        $ext->setAliases(array());
        $this->assertSame(array(), $ext->getAliases());

        $ext->setAliases($funcs);
        $this->assertSame($funcs, $ext->getAliases());

        $twig->addExtension($ext);

        if ($matches) {
            $this->assertRegexp($regex, $twig->render('template'));
        } else {
            $this->assertNotRegexp($regex, $twig->render('template'));
        }

        $ext->setAliases(array());
        $this->assertSame($funcs, $ext->getAliases());
    }

    /**
     * @cover setAliases
     * @expectedException \InvalidArgumentException
     */
    public function testBadAliases()
    {
        $ext = new TwigExtension();
        $ext->setAliases(array(
            'd' => 'stdClass',
        ));
    }

    /**
     * @covers \Kint\Twig\TwigExtension::getStatics
     * @covers \Kint\Twig\TwigExtension::setStatics
     */
    public function testStatics()
    {
        $statics = Kint::getStatics();
        $statics['return'] = true;

        $ext = new TwigExtension();
        $this->assertSame($statics, $ext->getStatics());

        $ext->setStatics(array('max_depth' => null));
        $this->assertSame(array('max_depth' => null, 'return' => true), $ext->getStatics());

        $statics['id'] = $ext;

        $ext->setStatics($statics);
        $this->assertSame($statics, $ext->getStatics());

        $ext->getFunctions();

        $ext->setStatics(array());
        $this->assertSame($statics, $ext->getStatics());
    }

    /**
     * @covers \Kint\Twig\TwigExtension::getName
     */
    public function testGetName()
    {
        $ext = new TwigExtension();
        $this->assertSame('kint', $ext->getName());
    }

    public function testGetParser()
    {
        $loader = new Twig_Loader_Array(array('template' => '{{ d(1) }}'));
        $twig = new Twig_Environment($loader, array('debug' => true));

        $ext = new TwigExtension();

        $twig->addExtension($ext);

        $hit = false;

        $plugin = new ProxyPlugin(
            array('integer'),
            Parser::TRIGGER_COMPLETE,
            function () use (&$hit) {
                $hit = true;
            }
        );

        $this->assertFalse($hit);
        $twig->render('template');
        $this->assertFalse($hit);
        $ext->getParser()->addPlugin($plugin);
        $this->assertFalse($hit);
        $twig->render('template');
        $this->assertTrue($hit);
    }

    public function testGetFunctionsCache()
    {
        $ext = new TwigExtension();

        $funcs = $ext->getFunctions();

        $this->assertSame($funcs, $ext->getFunctions());
    }

    public function testDumpAll()
    {
        $loader = new Twig_Loader_Array(array('template' => '{{ d() }}'));
        $twig = new Twig_Environment($loader, array('debug' => true));

        $twig->addExtension(new TwigExtension());

        $this->assertRegexp(
            '/var1.+val1.+foo.+bar/',
            $twig->render(
                'template',
                array(
                    'var1' => 'val1',
                    'foo' => 'bar',
                )
            )
        );
    }
}
