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

namespace Kint\Test\Twig;

use PHPUnit\Framework\TestCase as BaseTestCase;

if (\method_exists(BaseTestCase::class, 'assertMatchesRegularExpression')) {
    /**
     * @coversNothing
     */
    class TestCase extends BaseTestCase
    {
    }
} else {
    /**
     * @coversNothing
     */
    class TestCase extends BaseTestCase
    {
        public function assertMatchesRegularExpression(...$args)
        {
            return $this->assertRegexp(...$args);
        }

        public function assertDoesNotMatchRegularExpression(...$args)
        {
            return $this->assertNotRegexp(...$args);
        }
    }
}
