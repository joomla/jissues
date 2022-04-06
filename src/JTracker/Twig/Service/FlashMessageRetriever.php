<?php
/**
 * Part of the Joomla Tracker Twig Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Twig\Service;

use JTracker\Application;

/**
 * Service class retrieving the flash messages from the message queue and resetting it
 *
 * @since  1.0
 */
class FlashMessageRetriever
{
	/**
	 * Web application
	 *
	 * @var    Application
	 * @since  1.0
	 */
	private $app;

	/**
	 * Constructor.
	 *
	 * @param   Application  $app  Web application
	 *
	 * @since   1.0
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Retrieve the flash messages from the message queue and clear it
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getMessages(): array
	{
		$messages = $this->app->getMessageQueue();

		$this->app->clearMessageQueue();

		return $messages;
	}
}
