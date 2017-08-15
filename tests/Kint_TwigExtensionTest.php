<?php

class Kint_TwigExtension_Test extends PHPUnit_Framework_TestCase
{
    protected $kint_status;

    public function setUp()
    {
        $this->kint_status = Kint::settings();
    }

    public function tearDown()
    {
        Kint::settings($this->kint_status);
    }

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
                '/literal.+?array.+?1.+?0.+?string.+?asdf.+?literal.+?array.+?magic.+?string.+?hullaballoo/s',
                true,
            ),
        );
    }

    /**
     * @dataProvider outputProvider
     */
    public function testOutput($template, $regex, $matches)
    {
        $loader = new Twig_Loader_Array(array('template' => $template));
        $twig = new Twig_Environment($loader, array('debug' => true));

        $twig->addExtension(new Kint_TwigExtension());

        if ($matches) {
            $this->assertRegexp($regex, $twig->render('template'));
        } else {
            $this->assertNotRegexp($regex, $twig->render('template'));
        }
    }

    /**
     * @dataProvider outputProvider
     */
    public function testDisabled($template, $regex, $matches)
    {
        $loader = new Twig_Loader_Array(array('template' => $template));
        $twig = new Twig_Environment($loader);

        $twig->addExtension(new Kint_TwigExtension());

        $this->assertEquals('', $twig->render('template'));

        $twig->enableDebug();

        if ($matches) {
            $this->assertRegexp($regex, $twig->render('template'));
        } else {
            $this->assertNotRegexp($regex, $twig->render('template'));
        }

        Kint::$enabled_mode = false;

        $this->assertEquals('', $twig->render('template'));
    }

    public function customFunctionProvider()
    {
        return array(
            'basic test custom function' => array(
                '{{ dump("magical" ~ "_" ~ "hullaballoo") }}',
                '/magical_hullaballoo/',
                true,
                array('dump' => Kint::MODE_RICH),
            ),
            'multiple test custom function' => array(
                '{{ debug("magical" ~ "_" ~ "hullaballoo") }}',
                '/magical_hullaballoo/',
                true,
                array(
                    'dump' => Kint::MODE_RICH,
                    'debug' => Kint::MODE_RICH,
                ),
            ),
        );
    }

    /**
     * @dataProvider customFunctionProvider
     */
    public function testSetFunctions($template, $regex, $matches, $funcs)
    {
        $loader = new Twig_Loader_Array(array('template' => $template));
        $twig = new Twig_Environment($loader, array('debug' => true));

        $ext = new Kint_TwigExtension();
        $ext->setFunctions($funcs);
        $twig->addExtension($ext);

        if ($matches) {
            $this->assertRegexp($regex, $twig->render('template'));
        } else {
            $this->assertNotRegexp($regex, $twig->render('template'));
        }
    }

    public function testDumpAll()
    {
        $loader = new Twig_Loader_Array(array('template' => '{{ d() }}'));
        $twig = new Twig_Environment($loader, array('debug' => true));

        $twig->addExtension(new Kint_TwigExtension());

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
