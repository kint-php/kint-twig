<?php

declare(strict_types=1);

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

use Kint\Kint;
use Kint\Parser\Parser;
use Kint\Renderer\PlainRenderer;
use Kint\Renderer\RichRenderer;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig_SimpleFunction;

class TwigExtension extends AbstractExtension
{
    protected $aliases;
    protected $frozen = false;

    public function __construct(array $aliases = null)
    {
        if ($aliases) {
            $this->aliases = $aliases;
        } else {
            $statics = Kint::getStatics();

            $statics['display_called_from'] = false;

            $rich = new Kint(new Parser(), new RichRenderer());
            $rich->setStatesFromStatics($statics);

            $plain = new Kint(new Parser(), new PlainRenderer());
            $plain->setStatesFromStatics($statics);

            $this->aliases = [
                'd' => $rich,
                's' => $plain,
            ];
        }
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function setAliases(array $aliases): void
    {
        if ($this->frozen) {
            return;
        }

        $this->aliases = $aliases;
    }

    /**
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        if (\version_compare(Environment::VERSION, '2') < 0) {
            /** @psalm-suppress UndefinedClass */
            $class = Twig_SimpleFunction::class; // @codeCoverageIgnore
        } else {
            $class = TwigFunction::class;
        }

        $opts = [
            'is_safe' => ['html'],
            'is_variadic' => true,
            'needs_context' => true,
            'needs_environment' => true,
        ];

        $ret = [];

        foreach ($this->aliases as $alias => $renderer) {
            $ret[] = new $class(
                $alias,
                function (Environment $env, array $context, array $args = []) use ($alias) {
                    return $this->dump($alias, $env, $context, $args);
                },
                $opts
            );
        }

        $this->frozen = true;

        /** @var \Twig\TwigFunction[] */
        return $ret;
    }

    /**
     * Dumper function sets return and mode.
     *
     * @param mixed $alias
     *
     * @return string Kint output
     */
    protected function dump(string $alias, Environment $env, array $context, array $args = []): string
    {
        if (!$env->isDebug()) {
            return '';
        }

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

        return $this->aliases[$alias]->dumpAll($args, $bases);
    }
}
