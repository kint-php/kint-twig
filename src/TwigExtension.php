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

namespace Kint\Twig;

use InvalidArgumentException;
use Kint\Kint;
use Kint\Parser\Parser;
use Twig\Environment;
use Twig\Extension\AbstractExtension;

class TwigExtension extends AbstractExtension
{
    protected $aliases = array(
        'd' => 'Kint\\Renderer\\RichRenderer',
        's' => 'Kint\\Renderer\\PlainRenderer',
    );

    protected $frozen = false;
    protected $parser;
    protected $statics = array();
    protected $instances = array();
    protected $functions = array();

    public function __construct()
    {
        if (\class_exists('Kint\\Renderer\\JsRenderer', true)) {
            $this->aliases['j'] = 'Kint\\Renderer\\JsRenderer'; // @codeCoverageIgnore
        }

        $this->parser = new Parser();

        $this->setStatics(Kint::getStatics());
    }

    /**
     * Sets an array of function aliases to renderers.
     *
     * Note that this has no effect once getFunctions has been
     * called once, and is only supported in PHP 5.3 and above
     *
     * @param array $functions
     * @param array $aliases
     */
    public function setAliases(array $aliases)
    {
        if ($this->frozen) {
            return;
        }

        foreach ($aliases as $alias) {
            if (!\is_a($alias, 'Kint\\Renderer\\Renderer', true)) {
                throw new InvalidArgumentException('Alias renderer is not a Kint\\Renderer\\Renderer');
            }
        }

        $this->aliases = $aliases;
        $this->instances = array();
    }

    public function getAliases()
    {
        return $this->aliases;
    }

    public function setStatics(array $statics)
    {
        if ($this->frozen) {
            return;
        }

        $this->statics = $statics;
        $this->statics['return'] = true;
        $this->parser->setDepthLimit($statics['max_depth']);
    }

    public function getStatics()
    {
        return $this->statics;
    }

    public function getParser()
    {
        return $this->parser;
    }

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
     * @param mixed             $mode     Kint::$enabled_mode (One of array_keys(Kint::$renderers))
     * @param array             $args     arguments to dump
     * @param \Twig\Environment $env
     * @param array             $context
     * @param mixed             $renderer
     *
     * @return string Kint output
     */
    public function dump($renderer, Environment $env, array $context, array $args = array())
    {
        if (!$env->isDebug()) {
            return '';
        }

        $k = $this->getInstance($renderer);

        if ($args) {
            $bases = Kint::getBasesFromParamInfo(array(), \count($args));
        } else {
            $params = array();
            foreach ($context as $key => $arg) {
                $params[] = array(
                    'name' => $key,
                    'path' => null,
                    'expression' => false,
                );
                $args[] = $arg;
            }

            $bases = Kint::getBasesFromParamInfo($params, \count($context));
        }

        return $k->dumpAll($args, $bases);
    }

    public function getName()
    {
        return 'kint';
    }

    public function getFunctions()
    {
        if ($this->functions) {
            return $this->functions;
        }

        if (\version_compare(Environment::VERSION, '2') < 0) {
            $class = 'Twig_SimpleFunction'; // @codeCoverageIgnore
        } else {
            $class = 'Twig\\TwigFunction';
        }

        $opts = array(
            'is_safe' => array('html'),
            'is_variadic' => true,
            'needs_context' => true,
            'needs_environment' => true,
        );

        $ret = array();

        // Workaround for 5.3 not supporting $this in closures yet
        $object = $this;

        foreach ($this->aliases as $func => $renderer) {
            $ret[] = new $class(
                $func,
                function (Environment $env, array $context, array $args = array()) use ($func, $object) {
                    return $object->dump($func, $env, $context, $args);
                },
                $opts
            );
        }

        $this->frozen = true;

        return $this->functions = $ret;
    }

    /**
     * Gets a Kint instance, from cache if possible.
     *
     * @param string $func Function alias
     *
     * @return Kint
     */
    protected function getInstance($func)
    {
        if (!isset($this->aliases[$func])) {
            throw new InvalidArgumentException('Invalid function alias'); // @codeCoverageIgnore
        }

        $renderer = $this->aliases[$func];

        if (isset($this->instances[$renderer])) {
            return $this->instances[$renderer];
        }

        if (!\is_a($renderer, 'Kint\\Renderer\\Renderer', true)) {
            throw new InvalidArgumentException('Invalid renderer class'); // @codeCoverageIgnore
        }

        /** @var \Kint\Renderer\Renderer */
        $instance = new $renderer();

        $k = new Kint($this->parser, $instance);

        $k->setStatesFromStatics($this->statics);

        if ($this->frozen) {
            $this->instances[$renderer] = $k;
        }

        return $k;
    }
}
