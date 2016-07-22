<?php
/**
 * Part of the Joomla! Tracker
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Input;

use Joomla\Input\Cli as JCli;

/**
 * Class Cli
 *
 * @since  1.0
 */
class Cli extends JCli
{
	/**
	 * Get the names of input arguments.
	 *
	 * @return array
	 */
	public function getArguments()
	{
		return array_keys($this->data);
	}
}
