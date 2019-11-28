<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Cli\Output;

use Application\Cli\CliOutput;

/**
 * Class Stdout.
 *
 * @since       1.0
 * @deprecated  2.0  Use the `joomla/console` package instead
 */
class Stdout extends CliOutput
{
	/**
	 * Write a string to standard output
	 *
	 * @param   string   $text  The text to display.
	 * @param   boolean  $nl    True (default) to append a new line at the end of the output string.
	 *
	 * @return  Stdout  Instance of $this to allow chaining.
	 *
	 * @codeCoverageIgnore
	 * @since   1.0
	 */
	public function out($text = '', $nl = true)
	{
		fwrite(STDOUT, $this->getProcessor()->process($text) . ($nl ? "\n" : null));

		return $this;
	}
}
