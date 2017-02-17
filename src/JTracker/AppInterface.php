<?php
/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker;

use Joomla\DI\Container;

/**
 * Interface defining a Joomla! Issue Tracker App
 *
 * @since  1.0
 */
interface AppInterface
{
	/**
	 * Loads services for the component into the application's DI Container
	 *
	 * @param   Container  $container  DI Container to load services into
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function loadServices(Container $container);
}
