<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Cli\Output\Processor;

/**
 * Class ProcessorInterface.
 *
 * @since       1.1.0
 * @deprecated  2.0  Use the `joomla/console` package instead
 */
interface ProcessorInterface
{
	/**
	 * Process the provided output into a string.
	 *
	 * @param   string  $output  The string to process.
	 *
	 * @return  string
	 *
	 * @since   1.1.0
	 */
	public function process($output);
}
