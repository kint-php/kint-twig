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
use Kint\Renderer\RichRenderer;
use Kint\Twig\TwigExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TwigExtensionTest extends TestCase
{
    public function outputProvider()
    {
        return [
            'basic test d' => [
                '{{ d("magical" ~ "_" ~ "hullaballoo") }}',
                '/magical_hullaballoo/',
                true,
            ],
            'basic test s' => [
                '{{ s("magical" ~ "_" ~ "hullaballoo") }}',
                '/magical_hullaballoo/',
                true,
            ],
            'echoless test d' => [
                '{% set x = d("magical" ~ "_" ~ "hullaballoo") %}',
                '/magical_hullaballoo/',
                false,
            ],
            'echoless test s' => [
                '{% set x = s("magical" ~ "_" ~ "hullaballoo") %}',
                '/magical_hullaballoo/',
                false,
            ],
            'array test d' => [
                '{{ d(["asdf"], {"magic": "hullaballoo"}) }}',
                '/array.+?1.+?0.+?string.+?asdf.+?array.+?magic.+?string.+?hullaballoo/',
                true,
            ],
            'array test s' => [
                '{{ s(["asdf"], {"magic": "hullaballoo"}) }}',
                '/array.+?1.+?0.+?string.+?asdf.+?array.+?magic.+?string.+?hullaballoo/s',
                true,
            ],
        ];
    }

    /**
     * @dataProvider outputProvider
     *
     * @covers \Kint\Twig\TwigExtension::__construct
     * @covers \Kint\Twig\TwigExtension::dump
     * @covers \Kint\Twig\TwigExtension::getFunctions
     * @covers \Kint\Twig\TwigExtension::getInstance
     *
     * @param mixed $template
     * @param mixed $regex
     * @param mixed $matches
     */
    public function testOutput($template, $regex, $matches)
    {
        $loader = new ArrayLoader(['template' => $template]);
        $twig = new Environment($loader, ['debug' => true]);

        $twig->addExtension(new TwigExtension());

        $output = $twig->render('template');

        if ($matches) {
            $this->assertRegexp($regex, $output);
        } else {
            $this->assertNotRegexp($regex, $output);
        }

        $this->assertSame($output, $twig->render('template'));
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
        $loader = new ArrayLoader(['template' => $template]);
        $twig = new Environment($loader);

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
        return [
            'basic test custom function' => [
                '{{ dump("magical" ~ "_" ~ "hullaballoo") }}',
                '/magical_hullaballoo/',
                true,
                ['dump' => 'Kint\\Renderer\\RichRenderer'],
            ],
            'multiple test custom function' => [
                '{{ debug("magical" ~ "_" ~ "hullaballoo") }}',
                '/magical_hullaballoo/',
                true,
                [
                    'dump' => 'Kint\\Renderer\\RichRenderer',
                    'debug' => 'Kint\\Renderer\\RichRenderer',
                ],
            ],
        ];
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
        $loader = new ArrayLoader(['template' => $template]);
        $twig = new Environment($loader, ['debug' => true]);

        $ext = new TwigExtension();

        $ext->setAliases([]);
        $this->assertSame([], $ext->getAliases());

        $ext->setAliases($funcs);
        $this->assertSame($funcs, $ext->getAliases());

        $twig->addExtension($ext);

        if ($matches) {
            $this->assertRegexp($regex, $twig->render('template'));
        } else {
            $this->assertNotRegexp($regex, $twig->render('template'));
        }

        $ext->setAliases([]);
        $this->assertSame($funcs, $ext->getAliases());
    }

    /**
     * @covers \Kint\Twig\TwigExtension::setAliases
     * @expectedException \InvalidArgumentException
     */
    public function testAliasNotString()
    {
        $ext = new TwigExtension();
        $ext->setAliases([
            'd' => new RichRenderer(),
        ]);
    }

    /**
     * @covers \Kint\Twig\TwigExtension::setAliases
     * @expectedException \InvalidArgumentException
     */
    public function testAliasNotRenderer()
    {
        $ext = new TwigExtension();
        $ext->setAliases([
            'd' => 'stdClass',
        ]);
    }

    /**
     * @covers \Kint\Twig\TwigExtension::__construct
     * @covers \Kint\Twig\TwigExtension::getStatics
     * @covers \Kint\Twig\TwigExtension::setStatics
     */
    public function testStatics()
    {
        $statics = Kint::getStatics();
        $statics['return'] = true;

        $ext = new TwigExtension();
        $this->assertSame($statics, $ext->getStatics());

        $ext->setStatics(['max_depth' => null]);
        $this->assertSame(['max_depth' => null, 'return' => true], $ext->getStatics());

        $statics['id'] = $ext;

        $ext->setStatics($statics);
        $this->assertSame($statics, $ext->getStatics());

        $ext->getFunctions();

        $ext->setStatics([]);
        $this->assertSame($statics, $ext->getStatics());
    }

    /**
     * @covers \Kint\Twig\TwigExtension::getFunctions
     */
    public function testGetFunctionsCache()
    {
        $ext = new TwigExtension();

        $funcs = $ext->getFunctions();

        $this->assertSame($funcs, $ext->getFunctions());
    }

    /**
     * @covers \Kint\Twig\TwigExtension::dump
     */
    public function testDumpAll()
    {
        $loader = new ArrayLoader(['template' => '{{ d() }}']);
        $twig = new Environment($loader, ['debug' => true]);

        $twig->addExtension(new TwigExtension());

        $this->assertRegexp(
            '/var1.+val1.+foo.+bar/',
            $twig->render(
                'template',
                [
                    'var1' => 'val1',
                    'foo' => 'bar',
                ]
            )
        );
    }
}
