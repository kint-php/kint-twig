<?php

class Kint_TwigExtension extends Twig_Extension
{
    /**
     * Dumper function sets return and mode.
     *
     * Because we promise above that the Kint output is safe for HTML, we can't
     * allow the user to set the Kint mode to text/cli in PHP and keep using it
     * here, so we explicitly set the mode every time.
     *
     * This means you can't use text or CLI mode through twig, but if you need
     * text or CLI mode you probably aren't using twig anyway.
     *
     * @param mixed $mode Kint::$enabled_mode (One of array_keys(Kint::$renderers))
     * @param array $args arguments to dump
     *
     * @return string Kint output
     */
    protected function dump($mode, array $args = array())
    {
        if (!Kint::$enabled_mode) {
            return 0;
        }

        $stash = Kint::settings();

        Kint::$enabled_mode = $mode;
        Kint::$return = true;
        Kint::$display_called_from = false;

        $out = call_user_func_array(array('Kint', 'dump'), $args);

        Kint::settings($stash);

        return $out;
    }

    public function getName()
    {
        return 'kint';
    }

    public function getFunctions()
    {
        $opts = array(
            'is_safe' => array('html'),
            'is_variadic' => true,
        );

        if (version_compare(Twig_Environment::VERSION, '2') < 0) {
            return array(
                new Twig_SimpleFunction('d', array($this, 'd'), $opts),
                new Twig_SimpleFunction('s', array($this, 's'), $opts),
            );
        } else {
            return array(
                new Twig_Function('d', array($this, 'd'), $opts),
                new Twig_Function('s', array($this, 's'), $opts),
            );
        }
    }

    public function d(array $args = array())
    {
        return self::dump(Kint::MODE_RICH, $args);
    }

    public function s(array $args = array())
    {
        return self::dump(Kint::MODE_PLAIN, $args);
    }
}
