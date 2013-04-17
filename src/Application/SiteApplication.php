<?php
/**
 * @package     JTracker\Application
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Application;

use Joomla\Registry\Registry;

/**
 * Joomla! Issue Tracker Site Application class
 *
 * @package  JTracker\Application
 * @since    1.0
 */
final class SiteApplication extends AbstractTrackerApplication
{
	/**
	 * Class constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		// Set the client ID
		$this->clientId = 0;

		// Set the app name
		$this->name = 'site';

		// Run the parent constructor
		parent::__construct();
	}

	/**
	 * Returns the application JMenu object.
	 *
	 * @param   string  $name     The name of the application/client.
	 * @param   array   $options  An optional associative array of configuration settings.
	 *
	 * @return  JMenu  JMenu object.
	 *
	 * @since   1.0
	 */
	public function getMenu($name = null, $options = array())
	{
		return parent::getMenu('site', $options);
	}

	/**
	 * Returns the application JRouter object.
	 *
	 * @param   string  $name     The name of the application.
	 * @param   array   $options  An optional associative array of configuration settings.
	 *
	 * @return  JRouter  A JRouter object
	 *
	 * @since   1.0
	 */
	public function getRouter($name = null, array $options = array())
	{
		// TODO: Probably need to build a proper JRouter class...
		$router = parent::getRouter('tracker', $options);

		return $router;
	}

	/**
	 * Get the template information
	 *
	 * @param   boolean  $params  True to return the template params
	 *
	 * @return  mixed  String with the template name or an object containing the name and params
	 *
	 * @since   1.0
	 */
	public function getTemplate($params = false)
	{
		// Build the object
		$template = new stdClass;
		$template->template = 'joomla';
		$template->params   = new Registry;

		if ($params)
		{
			return $template;
		}

		return $template->template;
	}

	/**
	 * Return the current state of the language filter.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	public function getLanguageFilter()
	{
		return false;
	}

	/**
	 * Login authentication function.
	 *
	 * @param   array  $credentials  Array('username' => string, 'password' => string)
	 * @param   array  $options      Array('remember' => boolean)
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0
	 */
	public function login($credentials, $options = array())
	{
		// Set the application login entry point
		if (!array_key_exists('entry_url', $options))
		{
			$options['entry_url'] = JUri::base() . 'index.php?option=com_users&task=login';
		}

		// Set the access control action to check.
		$options['action'] = 'core.login.site';

		return parent::login($credentials, $options);
	}
}
