<?php

$mainFinder = PhpCsFixer\Finder::create()
	->in(
		[
			__DIR__ . '/cli',
			__DIR__ . '/src',
			__DIR__ . '/tests',
		]
	);

return PhpCsFixer\Config::create()
	->setRules(
		[
			// psr-1
			'encoding'                              => true,
			'full_opening_tag'                      => true,
			// psr-2
			'blank_line_after_namespace'            => true,
			'braces'                                => [
				'allow_single_line_closure'                   => false,
				'position_after_anonymous_constructs'         => 'next',
				'position_after_control_structures'           => 'next',
				'position_after_functions_and_oop_constructs' => 'next',
			],
			'elseif'                                => true,
			'function_declaration'                  => true,
			'line_ending'                           => true,
			'lowercase_constants'                   => true,
			'lowercase_keywords'                    => true,
			'method_argument_space'                 => true,
			'no_spaces_after_function_name'         => true,
			'no_spaces_inside_parenthesis'          => true,
			'no_trailing_whitespace'                => true,
			'ordered_imports'                       => true,
			'single_blank_line_at_eof'              => true,
			'single_import_per_statement'           => true,
			'single_line_after_imports'             => true,
			'switch_case_semicolon_to_colon'        => true,
			'visibility_required'                   => true,
			// symfony
			'binary_operator_spaces'                => ['align_double_arrow' => true, 'align_equals' => true],
			'blank_line_before_statement'           => [
				'statements' => [
					'break', 'case', 'continue', 'declare', 'for', 'foreach', 'if', 'return', 'switch', 'throw', 'try',
				],
			],
			'cast_spaces'                           => true,
			'concat_space'                          => ['spacing' => 'one'],
			'dir_constant'                          => true,
			'function_to_constant'                  => true,
			'function_typehint_space'               => true,
			'include'                               => true,
			'increment_style'                       => ['style' => 'post'],
			'is_null'                               => ['use_yoda_style' => false],
			'lowercase_static_reference'            => true,
			'magic_constant_casing'                 => true,
			'modernize_types_casting'               => true,
			'native_function_casing'                => true,
			'no_alias_functions'                    => true,
			'no_blank_lines_after_class_opening'    => true,
			'no_blank_lines_after_phpdoc'           => true,
			'no_empty_statement'                    => true,
			'no_extra_consecutive_blank_lines'      => true,
			'no_trailing_comma_in_list_call'        => true,
			'no_trailing_comma_in_singleline_array' => true,
			'no_unneeded_control_parentheses'       => true,
			'no_unused_imports'                     => true,
			'no_whitespace_before_comma_in_array'   => true,
			'no_whitespace_in_blank_line'           => true,
			'phpdoc_trim'                           => true,
			'return_type_declaration'               => true,
			'self_accessor'                         => true,
			'simplified_null_return'                => true,
			'single_blank_line_before_namespace'    => true,
			'single_quote'                          => true,
			'trailing_comma_in_multiline_array'     => true,
			'whitespace_after_comma_in_array'       => true,
			'yoda_style'                            => ['equal' => false, 'identical' => false],
			// misc
			'array_indentation'                     => true,
			'array_syntax'                          => ['syntax' => 'short'],
			'combine_consecutive_issets'            => true,
			'combine_consecutive_unsets'            => true,
			'linebreak_after_opening_tag'           => true,
			'logical_operators'                     => true,
			'native_function_invocation'            => ['include' => ['@compiler_optimized']],
			'no_null_property_initialization'       => true,
			'no_superfluous_elseif'                 => true,
			'no_useless_else'                       => true,
			'no_useless_return'                     => true,
			'ternary_to_null_coalescing'            => true,
		]
	)
	->setRiskyAllowed(true)
	->setIndent("\t")
	->setFinder($mainFinder);
