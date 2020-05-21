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
    /** @var string[] */
    protected $aliases = [
        'd' => 'Kint\\Renderer\\RichRenderer',
        's' => 'Kint\\Renderer\\PlainRenderer',
    ];

    protected $frozen = false;
    protected $statics = [];
    protected $instances = [];
    protected $functions = [];

    public function __construct()
    {
        if (\class_exists('Kint\\Renderer\\JsRenderer', true)) {
            $this->aliases['j'] = 'Kint\\Renderer\\JsRenderer'; // @codeCoverageIgnore
        }

        $this->setStatics(Kint::getStatics());
    }

    /**
     * @param array $statics Array of statics expected by the Kint\Kint class
     *
     * @return void
     */
    public function setStatics(array $statics)
    {
        if ($this->frozen) {
            return;
        }

        $this->statics = $statics;
        $this->statics['return'] = true;
    }

    public function getStatics(): array
    {
        return $this->statics;
    }

    /**
     * Sets an array of function aliases to renderers.
     *
     * Note that this has no effect once getFunctions has been
     * called once, and is only supported in PHP 5.3 and above
     *
     * @return void
     */
    public function setAliases(array $aliases)
    {
        if ($this->frozen) {
            return;
        }

        foreach ($aliases as $alias) {
            if (!\is_string($alias)) {
                throw new InvalidArgumentException('Alias renderer is not a string');
            }
            if (!\is_a($alias, 'Kint\\Renderer\\Renderer', true)) {
                throw new InvalidArgumentException('Alias renderer string is not a Kint\\Renderer\\Renderer');
            }
        }

        $this->aliases = $aliases;
        $this->instances = [];
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function getFunctions(): array
    {
        if ($this->functions) {
            return $this->functions;
        }

        if (\version_compare(Environment::VERSION, '2') < 0) {
            $class = 'Twig_SimpleFunction'; // @codeCoverageIgnore
        } else {
            $class = 'Twig\\TwigFunction';
        }

        $opts = [
            'is_safe' => ['html'],
            'is_variadic' => true,
            'needs_context' => true,
            'needs_environment' => true,
        ];

        $ret = [];

        foreach ($this->aliases as $func => $renderer) {
            $ret[] = new $class(
                $func,
                function (Environment $env, array $context, array $args = []) use ($func) {
                    return $this->dump($func, $env, $context, $args);
                },
                $opts
            );
        }

        $this->frozen = true;

        /** @var \Twig\TwigFunction[] */
        return $this->functions = $ret;
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
     * @param mixed $alias
     *
     * @return string Kint output
     */
    public function dump($alias, Environment $env, array $context, array $args = []): string
    {
        if (!$env->isDebug()) {
            return '';
        }

        $k = $this->getInstance($alias);

        if ($args) {
            $bases = Kint::getBasesFromParamInfo([], \count($args));
        } else {
            $params = [];
            foreach ($context as $key => $arg) {
                $params[] = [
                    'name' => $key,
                    'path' => null,
                    'expression' => false,
                ];
                $args[] = $arg;
            }

            $bases = Kint::getBasesFromParamInfo($params, \count($context));
        }

        return $k->dumpAll($args, $bases);
    }

    /**
     * Gets a Kint instance, from cache if possible.
     *
     * @param string $alias Function alias
     */
    protected function getInstance(string $alias): Kint
    {
        if (!isset($this->aliases[$alias])) {
            throw new InvalidArgumentException('Invalid function alias'); // @codeCoverageIgnore
        }

        $renderer = $this->aliases[$alias];

        if (isset($this->instances[$renderer])) {
            return $this->instances[$renderer];
        }

        if (!\is_a($renderer, 'Kint\\Renderer\\Renderer', true)) {
            throw new InvalidArgumentException('Invalid renderer class'); // @codeCoverageIgnore
        }

        $instance = new $renderer();

        $k = new Kint(new Parser(), $instance);

        $k->setStatesFromStatics($this->statics);

        // Only cache after functions are frozen
        if ($this->frozen) {
            $this->instances[$renderer] = $k;
        }

        return $k;
    }
}
