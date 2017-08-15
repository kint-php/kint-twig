<?php
/**
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
class Kint_TwigExtension extends Twig_Extension
{
    protected $functions = array(
        'd' => Kint::MODE_RICH,
        's' => Kint::MODE_PLAIN,
    );

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
    public function dump($mode, Twig_Environment $env, array $context, array $args = array())
    {
        if (!Kint::$enabled_mode || !$env->isDebug()) {
            return;
        }

        $stash = Kint::settings();

        Kint::$enabled_mode = $mode;
        Kint::$return = true;
        Kint::$display_called_from = false;

        $out = call_user_func_array(array('Kint', 'dump'), $args ? $args : array($context));

        Kint::settings($stash);

        return $out;
    }

    /**
     * Sets an array of function aliases to be returned by getFunctions.
     *
     * Note that this has no effect once getFunctions has been
     * called once, and is only supported in PHP 5.3 and above
     */
    public function setFunctions(array $functions)
    {
        $this->functions = $functions;
    }

    public function getName()
    {
        return 'kint';
    }

    public function getFunctions()
    {
        if (version_compare(Twig_Environment::VERSION, '2') < 0) {
            $class = 'Twig_SimpleFunction';
        } else {
            $class = 'Twig_Function';
        }

        $opts = array(
            'is_safe' => array('html'),
            'is_variadic' => true,
            'needs_context' => true,
            'needs_environment' => true,
        );

        $ret = array();

        if (KINT_PHP53) {
            // Workaround for 5.3 not supporting $this in closures yet
            $object = $this;

            foreach ($this->functions as $func => $mode) {
                $ret[] = new $class(
                    $func,
                    function (Twig_Environment $env, array $context, array $args = array()) use ($mode, $object) {
                        return $object->dump($mode, $env, $context, $args);
                    },
                    $opts
                );
            }
        } else {
            $ret[] = new $class('d', array($this, 'd'), $opts);
            $ret[] = new $class('s', array($this, 's'), $opts);
        }

        return $ret;
    }

    public function d(array $args = array())
    {
        return $this->dump(Kint::MODE_RICH, $args);
    }

    public function s(array $args = array())
    {
        return $this->dump(Kint::MODE_PLAIN, $args);
    }
}
