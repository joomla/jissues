<?php
/**
 * Part of the Joomla Tracker's Debug Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Debug\Database;

/**
 * Describes a SQL logger-aware instance
 *
 * @since  1.0
 */
interface SQLLoggerAwareInterface
{
	/**
	 * Sets a logger instance on the object
	 *
	 * @param   SQLLogger  $logger  The logger object.
	 *
	 * @return $this
	 *
	 * @since  1.0
	 */
	public function setSQLLogger(SQLLogger $logger);
}
