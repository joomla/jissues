<?php
/**
 * @package     BabDev.Tracker
 * @subpackage  Application
 *
 * @copyright   Copyright (C) 2012 Michael Babker. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Joomla! Issue Tracker Site Application class
 *
 * @package     BabDev.Tracker
 * @subpackage  Application
 * @since       1.0
 */
final class JApplicationSite extends JApplicationTracker
{
	/**
	 * Class constructor.
	 *
	 * @param   mixed  $input   An optional argument to provide dependency injection for the application's
	 *                          input object.  If the argument is a JInput object that object will become
	 *                          the application's input object, otherwise a default input object is created.
	 * @param   mixed  $config  An optional argument to provide dependency injection for the application's
	 *                          config object.  If the argument is a JRegistry object that object will become
	 *                          the application's config object, otherwise a default config object is created.
	 * @param   mixed  $client  An optional argument to provide dependency injection for the application's
	 *                          client object.  If the argument is a JApplicationWebClient object that object will become
	 *                          the application's client object, otherwise a default client object is created.
	 *
	 * @since   1.0
	 */
	public function __construct(JInput $input = null, JRegistry $config = null, JApplicationWebClient $client = null)
	{
		// Set the client ID
		$this->clientId = 0;

		// Set the app name
		$this->name = 'site';

		// Run the parent constructor
		parent::__construct($input, $config, $client);
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
	public static function getRouter($name = null, array $options = array())
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
		$template->template = 'protostar';
		$template->params   = new JRegistry;

		if ($params)
		{
			return $template;
		}

		return $template->template;
	}

	public function getLanguageFilter()
	{
		return false;
	}
}
