<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Cli;

/**
 * Class CliInput
 *
 * @since       1.6.0
 * @deprecated  2.0  Use the `joomla/console` package instead
 */
class CliInput
{
	/**
	 * Get a value from standard input.
	 *
	 * @return  string  The input string from standard input.
	 *
	 * @codeCoverageIgnore
	 * @since   1.6.0
	 */
	public function in()
	{
		return rtrim(fread(STDIN, 8192), "\n\r");
	}
}
