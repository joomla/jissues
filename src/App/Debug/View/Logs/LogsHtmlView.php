<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Debug\View\Logs;

use App\Debug\TrackerDebugger;
use JTracker\View\AbstractTrackerHtmlView;
use JTracker\Container;

/**
 * System configuration view.
 *
 * @since  1.0
 */
class LogsHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function render()
	{
		/* @type \JTracker\Application $application */
		$application = Container::retrieve('app');

		$type = $application->input->get('log_type');

		$debugger = new TrackerDebugger($application);

		switch ($type)
		{
			case 'php' :
				$path = $debugger->getLogPath('php');
				break;

			case '403' :
			case '404' :
			case '500' :
			case 'database' :
			case 'error' :
			case 'github_issues' :
			case 'github_comments' :
			case 'github_pulls' :
				$path = $debugger->getLogPath('root') . '/' . $type . '.log';
				break;

			default :
				throw new \UnexpectedValueException('Invalid log type');
			break;
		}

		$log = (realpath($path))
			? $this->processLog($type, $path)
			: array(sprintf(g11n3t('No %s log file found.'), $type));

		$this->renderer->set('log', $log);
		$this->renderer->set('log_type', $type);

		return parent::render();
	}

	/**
	 * Process a log file.
	 *
	 * @param   string  $type  The log type
	 * @param   string  $path  Path to log file
	 *
	 * @return  array
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	protected function processLog($type, $path)
	{
		if (false == file_exists($path))
		{
			return array('File not found in path: ' . $path);
		}

		switch ($type)
		{
			case '403':
			case '404':
			case '500':
			case 'database':
			case 'error':
			case 'php':
			case 'github_issues':
			case 'github_comments':
			case 'github_pulls':
				// @todo beautifyMe
				$log = explode("\n\n", file_get_contents($path));
				break;

			default :
				throw new \UnexpectedValueException(__METHOD__ . ' - undefined type: ' . $type);
				break;
		}

		// Reverse log
		$log = array_reverse($log);

		return $log;
	}
}
