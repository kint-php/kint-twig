<?php

class Kint_TwigExtension_Test extends PHPUnit_Framework_TestCase
{
    public function outputProvider()
    {
        return array(
            'basic test d' => array(
                '/magical_hullaballoo/',
                '{{ d("magical" ~ "_" ~ "hullaballoo") }}',
                true,
            ),
            'basic test s' => array(
                '/magical_hullaballoo/',
                '{{ s("magical" ~ "_" ~ "hullaballoo") }}',
                true,
            ),
            'echoless test d' => array(
                '/magical_hullaballoo/',
                '{% set x = d("magical" ~ "_" ~ "hullaballoo") %}',
                false,
            ),
            'echoless test s' => array(
                '/magical_hullaballoo/',
                '{% set x = s("magical" ~ "_" ~ "hullaballoo") %}',
                false,
            ),
            'array test d' => array(
                '/array.+?1.+?0.+?string.+?asdf.+?array.+?magic.+?string.+?hullaballoo/',
                '{{ d(["asdf"], {"magic": "hullaballoo"}) }}',
                true,
            ),
            'array test s' => array(
                '/literal.+?array.+?1.+?0.+?string.+?asdf.+?literal.+?array.+?magic.+?string.+?hullaballoo/s',
                '{{ s(["asdf"], {"magic": "hullaballoo"}) }}',
                true,
            ),
        );
    }

    /**
     * @dataProvider outputProvider
     */
    public function testOutput($regex, $template, $matches)
    {
        $loader = new Twig_Loader_Array(array('template' => $template));
        $twig = new Twig_Environment($loader);

        $twig->addExtension(new Kint_TwigExtension());

        if ($matches) {
            $this->assertRegexp($regex, $twig->render('template'));
        } else {
            $this->assertNotRegexp($regex, $twig->render('template'));
        }
    }
}
