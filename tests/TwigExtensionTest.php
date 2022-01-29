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
use Kint\Renderer\RichRenderer;
use Kint\Twig\TwigExtension;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;

class TwigExtensionTest extends KintTwigTestCase
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
     * @covers \Kint\Twig\TwigExtension::getFunctions
     * @covers \Kint\Twig\TwigExtension::dump
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
            $this->assertMatchesRegularExpression($regex, $output);
        } else {
            $this->assertDoesNotMatchRegularExpression($regex, $output);
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
        $loader = new ArrayLoader(['template' => $template]);
        $twig = new Environment($loader);
        $twig->addExtension(new TwigExtension());

        $this->assertEquals('', $twig->render('template'));

        $twig->enableDebug();

        $output = $twig->render('template');

        if ($matches) {
            $this->assertMatchesRegularExpression($regex, $output);
        } else {
            $this->assertDoesNotMatchRegularExpression($regex, $output);
        }

        $twig->disableDebug();

        $this->assertEquals('', $twig->render('template'));
    }

    public function testCustomConstruct()
    {
        $kintstance = new Kint(new Parser(), new RichRenderer());
        $kintstance->setStatesFromStatics(Kint::getStatics());

        $loader = new ArrayLoader([
            'custom' => '{{ c("magical" ~ "_" ~ "hullaballoo") }}',
            'fail' => '{{ d("magical" ~ "_" ~ "hullaballoo") }}',
        ]);
        $twig = new Environment($loader, ['debug' => true]);
        $twig->addExtension(new TwigExtension(['c' => $kintstance]));

        $output = $twig->render('custom');

        $this->assertMatchesRegularExpression('/magical_hullaballoo/', $output);

        $this->expectException(SyntaxError::class);

        $twig->render('fail');
    }

    /**
     * @covers \Kint\Twig\TwigExtension::dump
     */
    public function testDumpAll()
    {
        $loader = new ArrayLoader(['template' => '{{ d() }}']);
        $twig = new Environment($loader, ['debug' => true]);
        $twig->addExtension(new TwigExtension());

        $this->assertMatchesRegularExpression(
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

    /**
     * @covers \Kint\Twig\TwigExtension::setAliases
     * @covers \Kint\Twig\TwigExtension::getAliases
     */
    public function testSetAliases()
    {
        $loader = new ArrayLoader(['template' => '{{ test() }}']);
        $twig = new Environment($loader, ['debug' => true]);
        $twig->addExtension($ext = new TwigExtension());

        $this->assertNotEmpty($ext->getAliases());

        $kintstance = new Kint(new Parser(), new RichRenderer());
        $kintstance->setStatesFromStatics(Kint::getStatics());

        $newAliases = ['test' => $kintstance];

        $ext->setAliases($newAliases);

        $this->assertSame($newAliases, $ext->getAliases());

        $twig->render('template');

        $ext->setAliases(['test number two' => $kintstance]);

        $this->assertSame($newAliases, $ext->getAliases());
    }
}
