<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Debug\View\Logs;

use App\Debug\TrackerDebugger;

use JTracker\View\AbstractTrackerHtmlView;

/**
 * Log file view.
 *
 * @since  1.0
 */
class LogsHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * The log type
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $logType = '';

	/**
	 * Debugger object
	 *
	 * @var    TrackerDebugger
	 * @since  1.0
	 */
	protected $debugger = null;

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
		$type = $this->getLogType();

		switch ($type)
		{
			case 'php' :
				$path = $this->getDebugger()->getLogPath('php');
				break;

			default :
				throw new \UnexpectedValueException('Invalid log type');
			break;
		}

		$log = (realpath($path)) ? $this->processLog($type, $path) : [sprintf(g11n3t('No %s log file found.'), $type)];

		$this->addData('log', $log)
			->addData('log_type', $type);

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
		if (false === file_exists($path))
		{
			return ['File not found in path: ' . $path];
		}

		switch ($type)
		{
			case 'php':
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

	/**
	 * Get the debugger.
	 *
	 * @return  \App\Debug\TrackerDebugger
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function getDebugger()
	{
		if (is_null($this->debugger))
		{
			throw new \UnexpectedValueException('Debugger not set');
		}

		return $this->debugger;
	}

	/**
	 * Get the debugger.
	 *
	 * @param   TrackerDebugger  $debugger  The debugger object.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function setDebugger(TrackerDebugger $debugger)
	{
		$this->debugger = $debugger;

		return $this;
	}

	/**
	 * Get the log type.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function getLogType()
	{
		if ('' == $this->logType)
		{
			throw new \UnexpectedValueException('Log type not set');
		}

		return $this->logType;
	}

	/**
	 * Set the log type.
	 *
	 * @param   string  $logType  The log type.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function setLogType($logType)
	{
		$this->logType = $logType;

		return $this;
	}
}
