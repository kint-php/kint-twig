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

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules(array(
        '@Symfony' => true,
        'array_indentation' => true,
        'array_syntax' => array('syntax' => 'long'),
        'class_keyword_remove' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'compact_nullable_typehint' => true,
        'dir_constant' => true,
        'escape_implicit_backslashes' => array(
            'single_quoted' => true,
        ),
        'explicit_indirect_variable' => true,
        'explicit_string_variable' => true,
        'fully_qualified_strict_types' => true,
        'header_comment' => array(
            'header' => \trim(\file_get_contents(__DIR__.'/LICENSE.short')),
        ),
        'is_null' => true,
        'linebreak_after_opening_tag' => true,
        'list_syntax' => array(
            'syntax' => 'long',
        ),
        'method_chaining_indentation' => true,
        'modernize_types_casting' => true,
        'multiline_comment_opening_closing' => true,
        'multiline_whitespace_before_semicolons' => true,
        'native_function_invocation' => true,
        'no_alias_functions' => true,
        'no_alternative_syntax' => true,
        'no_blank_lines_before_namespace' => false,
        'no_homoglyph_names' => true,
        'no_null_property_initialization' => true,
        'no_superfluous_elseif' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'non_printable_character' => true,
        'ordered_class_elements' => array(
            'order' => array(
                'use_trait',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public_static',
                'property_protected_static',
                'property_private_static',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
                'method_public_static',
                'method_protected_static',
                'method_private_static',
            ),
            'sortAlgorithm' => 'none',
        ),
        'ordered_imports' => array(
            'sortAlgorithm' => 'alpha',
        ),
        'php_unit_construct' => true,
        'php_unit_dedicate_assert' => array(
            'target' => '3.5',
        ),
        'php_unit_namespaced' => array(
            'target' => '4.8',
        ),
        'php_unit_ordered_covers' => true,
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_strict' => false,
        'php_unit_test_annotation' => false,
        'php_unit_test_class_requires_covers' => false, // I wish this worked properly :(
        'phpdoc_add_missing_param_annotation' => array(
            'only_untyped' => false,
        ),
        'phpdoc_order' => true,
        'phpdoc_to_comment' => false, // Required for certain Psalm workarounds
        'phpdoc_types_order' => true,
        'psr4' => true,
        'simplified_null_return' => false, // phpstan checks that we're actually returning an actual null value
        'strict_param' => true,
        'string_line_ending' => true,

        // To be enabled when our min PHP support allows
        // 'self_accessor' => true, // Requires min PHP support 5.4.1
        // 'static_lambda' => true, // Requires min PHP support 5.4
    ))
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
            ->exclude(array('build'))
            ->append(array(__FILE__))
    );
