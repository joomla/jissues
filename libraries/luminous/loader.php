<?php
/*
 * If we're running in a web environment, this is simply an include
 * file which includes everything necessary to use Luminous.
 *
 * If we're running in CLI-mode then this handles the CLI interface.
 *
 */

require_once(dirname(__FILE__) . '/luminous/src/luminous.php');

if (PHP_SAPI === 'cli')
{
	// cli mode

	echo 'Sorry, CLI mode is unsupported in this version :(';

	exit(1);

	if (isset($argv[0]) && $argv[0] === basename(__FILE__))
	{
		require(dirname(__FILE__) . '/src/cli.php');
	}
}
