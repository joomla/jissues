<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Debug\View\Logs;

use App\Debug\TrackerDebugger;

use JTracker\View\AbstractTrackerHtmlView;

/**
 * System configuration view.
 *
 * @since  1.0
 */
class LogsHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * @var string
	 */
	protected $logType = '';

	/**
	 * @var TrackerDebugger
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

			case '403' :
			case '404' :
			case '500' :
			case 'database' :
			case 'error' :
			case 'github_issues' :
			case 'github_comments' :
			case 'github_pulls' :
				$path = $this->getDebugger()->getLogPath('root') . '/' . $type . '.log';
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

	/**
	 * Get the debugger.
	 *
	 * @throws \UnexpectedValueException
	 * @return \App\Debug\TrackerDebugger
	 *
	 * @since   1.0
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
	 * @return $this
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
	 * @throws \UnexpectedValueException
	 * @return string
	 *
	 * @since   1.0
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
	 * @return $this
	 */
	public function setLogType($logType)
	{
		$this->logType = $logType;

		return $this;
	}
}
