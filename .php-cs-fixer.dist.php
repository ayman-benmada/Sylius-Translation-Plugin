<?php

$finder = PhpCsFixer\Finder::create()
    ->in('src');

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@PSR12' => true,
    '@Symfony' => true,
    'blank_line_between_import_groups' => false,
    'fully_qualified_strict_types' => true,
    'no_unneeded_import_alias' => true,
    'global_namespace_import' => true,
    'lambda_not_used_import' => true,
    'function_typehint_space' => true, // https://mlocati.github.io/php-cs-fixer-configurator/#version:3.7|fixer:function_typehint_space
    'no_blank_lines_after_phpdoc' => true,
    'no_superfluous_phpdoc_tags' => true,
    'array_syntax' => ['syntax' => 'short'],
    'blank_line_after_opening_tag' => true,
    'concat_space' => ['spacing' => 'one'],
    'list_syntax' => ['syntax' => 'short'],
    'increment_style' => true,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'ordered_imports' => true,
    'phpdoc_align' => true,
    'phpdoc_order' => true,
    'single_line_comment_style' => true,
    'ternary_to_null_coalescing' => true,
    'yoda_style' => false,
    'nullable_type_declaration_for_default_null_value' => true, // conflicts with insights
])->setFinder($finder);
