<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Registry\Registry;

/**
 * Configuration service provider
 *
 * @since  1.0
 */
class ConfigurationProvider implements ServiceProviderInterface
{
	/**
	 * Configuration instance
	 *
	 * @var    Registry
	 * @since  1.0
	 */
	private $config;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function __construct()
	{
		// Check for a custom configuration.
		$type = trim(getenv('JTRACKER_ENVIRONMENT'));

		$name = ($type) ? 'config.' . $type : 'config';

		// Set the configuration file path for the application.
		$file = JPATH_ROOT . '/etc/' . $name . '.json';

		// Verify the configuration exists and is readable.
		if (!is_readable($file))
		{
			throw new \RuntimeException('Configuration file does not exist or is unreadable.');
		}

		// Load the configuration file into an object.
		$configObject = json_decode($this->replaceEnvVars(file_get_contents($file)));

		if ($configObject === null)
		{
			throw new \RuntimeException(sprintf('Unable to parse the configuration file %s.', $file));
		}

		$this->config = new Registry($configObject);

		defined('JDEBUG') || define('JDEBUG', ($this->config->get('debug.system') || $this->config->get('debug.database')));
	}

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function register(Container $container)
	{
		$container->share('config',
			function ()
			{
				return $this->config;
			}, true
		);
	}

	/**
	 * Replace any env vars referenced in a string with their values.
	 *
	 * @param   string  $string  The string to replace.
	 *
	 * @return  string
	 *
	 * @since  1.0
	 */
	private function replaceEnvVars($string)
	{
		foreach (array_keys($_ENV) as $var)
		{
			if (strstr($string, '$' . $var))
			{
				$string = str_replace('$' . $var, $_ENV[$var], $string);
			}
		}

		return $string;
	}
}
