<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\System\Controller\Config;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to save the configuration
 *
 * @since  1.0
 */
class Save extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @throws  \UnexpectedValueException
	 */
	public function execute()
	{
		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$application->getUser()->authorize('admin');

		$config = $this->cleanArray($application->input->get('config', [], 'array'));

		if (!$config)
		{
			throw new \UnexpectedValueException('No config to save...');
		}

		$type = trim(getenv('JTRACKER_ENVIRONMENT'));

		$fileName = ($type) ? 'config.' . $type . '.json' : 'config.json';

		$data = json_encode($config, JSON_PRETTY_PRINT);

		// Check for errors converting the config to JSON
		if (json_last_error())
		{
			throw new \RuntimeException('Could not convert config data to JSON: ' . json_last_error_msg());
		}

		if (!file_put_contents(JPATH_ROOT . '/etc/' . $fileName, $data))
		{
			throw new \RuntimeException('Could not write the configuration data to file /etc/' . $fileName);
		}

		$application->enqueueMessage(g11n3t('The configuration file has been saved.'), 'success')
			->redirect('/');

		// To silence PHPCS expecting a return
		return '';
	}

	/**
	 * Remove empty fields from an array.
	 *
	 * @param   array  $array  The array to clean.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	private function cleanArray(array $array)
	{
		foreach ($array as $k => $v)
		{
			if (is_array($v))
			{
				$array[$k] = $this->cleanArray($v);

				continue;
			}

			if ('' === $v)
			{
				unset($array[$k]);
			}
		}

		return $array;
	}
}
