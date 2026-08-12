<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = new Finder()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/controllers',
        __DIR__ . '/migrations',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tasks',
        __DIR__ . '/tests',
    ])
    ->exclude(['container'])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return new Config()
    ->setRiskyAllowed(true)
    ->setRules([

        // Default symfony rules
        '@Symfony'       => true,
        '@Symfony:risky' => true,

        // Prefix all core PHP functions with \ (e.g., \strlen())
        'native_function_invocation' => [
            'include' => ['@all'],
            'scope'   => 'all',
            'strict'  => true,
        ],

        // Add blank newlines after namespace and <?php tag
        'blank_line_after_namespace'   => true,
        'blank_line_after_opening_tag' => true,

        // Do not touch `declare(strict_types)`
        'declare_strict_types' => false,

        // Force global classes/objects (like \DateTime, \Exception) to be prefixed
        // inline rather than imported with a "use" statement at the top of the file.
        'global_namespace_import' => [
            'import_classes'   => false,
            'import_constants' => false,
            'import_functions' => false,
        ],

        // Always force [short] array syntax
        'array_syntax' => ['syntax' => 'short'],

        // Always use echo instead of print
        'no_mixed_echo_print' => ['use' => 'echo'],

        // Replace deprecated alias functions (like `create_function` or `is_integer`) with their modern master counterparts.
        // Note that this is a risky rule.
        'no_alias_functions' => true,

        // Override the @Symfony default to managing our own blank lines
        // at the start and end of control structures (curly brace blocks).
        'no_extra_blank_lines' => [
            'tokens' => [
                'attribute',
                'case',
                'continue',
                // 'curly_brace_block',
                'default',
                'extra',
                // 'parenthesis_brace_block',
                'return',
                // 'square_brace_block',
                'switch',
                'throw',
                'use',
            ],
        ],

        // Disable Yoda conditions.
        // Place variables on the left side of our comparisons (e.g., `$var === false` instead of `false === $var`).
        'yoda_style' => [
            'equal'            => false,
            'identical'        => false,
            'less_and_greater' => false,
        ],

        // Require spaces around concatenation.
        // Pad the dot operator with a single space on both sides for better readability (e.g., `'a' . 'b'`).
        'concat_space' => ['spacing' => 'one'],

        // Align array key-value pairs vertically.
        // Force the alignment of the double arrow `=>` operators in arrays to maintain the indented structure seen in image_53f393.png, while keeping standard single-space formatting for other operators like `=`.
        'binary_operator_spaces' => [
            'default'   => 'align_single_space_minimal',
            'operators' => [
                '=>' => 'align_single_space_minimal',
            ],
        ],

        // Disable forcing @var comments to be correctly placed
        // In some cases - like in the bootstrap files - we actually want to define it someplace else than the initial definition
        'phpdoc_to_comment' => [
            'ignored_tags' => ['var'],
        ],
    ])
    ->setFinder($finder);
