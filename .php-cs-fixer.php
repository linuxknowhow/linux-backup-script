<?php

# declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(
        [
            __DIR__ . DIRECTORY_SEPARATOR . 'src'
        ]
    )
    ->append(
        [
            __DIR__ . DIRECTORY_SEPARATOR . '.php-cs-fixer.php',
        ]
    );

return (new PhpCsFixer\Config())
    ->setRules([
        'braces' => [
            'allow_single_line_closure' => true,
            'position_after_control_structures' => 'same',
            'position_after_functions_and_oop_constructs' => 'same',
        ],
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . DIRECTORY_SEPARATOR . '.php_cs.cache');