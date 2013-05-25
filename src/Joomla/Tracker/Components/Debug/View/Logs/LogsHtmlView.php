<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Debug\View\Logs;

use Joomla\Factory;
use Joomla\Tracker\View\AbstractTrackerHtmlView;

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
	 * @throws \UnexpectedValueException
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		/* @type \Joomla\Tracker\Application\TrackerApplication $application */
		$application = Factory::$application;

		$type = $application->input->get('log_type');

		$log = array();

		$debugger = $application->getDebugger();

		$path = $debugger ? $debugger->getLogPath($type) ? : '' : '';

		if ($path)
		{
			switch ($type)
			{
				case 'php':
					if (false == file_exists($path))
					{
						$log = array('File not found in path: ' . $path);
					}
					else
					{
						// @todo beautifyMe
						$log = explode("\n\n", file_get_contents($path));
					}
					break;

				case 'database':
					if (false == file_exists($path))
					{
						$log = array('File not found in path: ' . $path);
					}
					else
					{
						$log = explode("\n\n", file_get_contents($path));
					}

					break;

				case '403':
				case '404':
				case '500':
					if (false == file_exists($path))
					{
						$log = array('File not found in path: ' . $path);
					}
					else
					{
						$log = explode("\n\n", file_get_contents($path));
					}

					break;

				default :
					throw new \UnexpectedValueException(__METHOD__ . ' - undefined type: ' . $type);
					break;
			}
		}

		$this->renderer->set('log', $log);
		$this->renderer->set('log_type', $type);

		return parent::render();
	}
}
