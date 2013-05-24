<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Debug;

use Joomla\Profiler\Profiler;

use Joomla\Tracker\Application\TrackerApplication;

/**
 * Class TrackerDebugger.
 *
 * @since  1.0
 */
class TrackerDebugger
{
	/**
	 * @var TrackerApplication
	 */
	private $application;

	/**
	 * @var array
	 * @since   1.0
	 */
	private $log = array();

	/**
	 * @var Profiler
	 * @since   1.0
	 */
	private $profiler;

	/**
	 * Constructor.
	 *
	 * @param   TrackerApplication  $application  The application
	 */
	public function __construct(TrackerApplication $application)
	{
		$this->application = $application;

		$this->profiler = new Profiler('Tracker');

		$this->log['db'] = array();
	}

	/**
	 * Mark a profile point.
	 *
	 * @param   string  $name  The profile point name.
	 *
	 * @since   1.0
	 * @return \Joomla\Profiler\ProfilerInterface
	 */
	public function mark($name)
	{
		return $this->profiler->mark($name);
	}

	/**
	 * Add an entry from the database.
	 *
	 * @param   mixed   $level    The log level.
	 * @param   string  $message  The message
	 * @param   array   $context  The log context.
	 *
	 * @since   1.0
	 * @return $this
	 */
	public function addDatabaseEntry($level, $message, $context)
	{
		$this->log['db'][] = isset($context['sql'])
			? $context['sql']
			: 'DATABASE - Level: ' . $level . ' - Message: ' . $message;

		// Log to a text file

		$log = array();

		$log[] = '';
		$log[] = date('y-m-d H:i:s') . ' DB Query';
		$log[] = $this->application->get('uri.request');

		$log[] = isset($context['sql'])
			? $message . ' - SQL: ' . $context['sql']
			: 'DATABASE - Level: ' . $level . ' - Message: ' . $message;

		$log[] = '';

		$fName = JPATH_BASE . '/logs/database.log';

		$returnVal = file_put_contents($fName, implode("\n", $log), FILE_APPEND | LOCK_EX);

		if (!$returnVal)
		{
			echo __METHOD__ . ' - File could not be written :(';
		}

		return $this;
	}

	/**
	 * Get the log entries.
	 *
	 * @param   string  $category  The log category.
	 *
	 * @throws \UnexpectedValueException
	 *
	 * @since   1.0
	 * @return array
	 */
	public function getLog($category = '')
	{
		if ($category)
		{
			if (false == array_key_exists($category, $this->log))
			{
				throw new \UnexpectedValueException(__METHOD__ . ' unkown category: ' . $category);
			}

			return $this->log[$category];
		}

		return $this->log;
	}

	/**
	 * Generate a call stack for debugging purpose.
	 *
	 * @since   1.0
	 * @return  string
	 */
	public function getOutput()
	{
		if (!JDEBUG)
		{
			return '';
		}

		// OK, here comes some very beautiful CSS !!
		// It's kinda "hidden" here, so evil template designers won't find it :P
		$css = '
		<style>
			span.dbgTable { color: yellow; }
			span.dbgCommand { color: lime; }
			span.dbgOperator { color: red; }
			pre.dbQuery { background-color: #333; color: white; font-weight: bold; }
		</style>
		';

		$debug = array();

		$debug[] = $css;

		$debug[] = '<h3>Debug</h3>';

		$dbLog = $this->getLog('db');

		if ($dbLog)
		{
			$debug[] = '<h4>Database</h4>';

			$debug[] = count($dbLog) . ' Queries.';

			$prefix = $this->application->getDatabase()->getPrefix();

			foreach ($dbLog as $entry)
			{
				$debug[] = '<pre class="dbQuery">' . $this->highlightQuery($entry, $prefix) . '</pre>';
			}
		}

		$debug[] = '<h4>Profile</h4>';
		$debug[] = $this->renderProfile();
		$debug[] = '</div>';

		return implode("\n", $debug);
	}

	/**
	 * Render the profiler output.
	 *
	 * @since   1.0
	 * @return string
	 */
	public function renderProfile()
	{
		return $this->profiler->render();
	}

	/**
	 * Method to render an exception in a user friendly format
	 *
	 * @param   \Exception  $exception   The caught exception.
	 * @param   string      $message     The message to display.
	 * @param   integer     $statusCode  The status code.
	 *
	 * @return  string  The exception output in rendered format.
	 *
	 * @since   1.0
	 */
	public function renderException(\Exception $exception, $message = '', $statusCode = 500)
	{
		static $loaded = false;

		if ($loaded)
		{
			// Seems that we're recursing...
			return str_replace(JPATH_BASE, 'JROOT', $exception->getMessage())
			. '<pre>' . $exception->getTraceAsString() . '</pre>'
			. 'Previous: ' . get_class($exception->getPrevious());
		}

		$viewClass = '\\Joomla\\Tracker\\View\\TrackerDefaultView';

		/* @type \Joomla\Tracker\View\TrackerDefaultView $view */
		$view = new $viewClass;

		$view->setLayout('exception')
			->getRenderer()
			->set('exception', $exception)
			->set('message', str_replace(JPATH_BASE, 'JROOT', $message));

		$loaded = true;

		$contents = $view->render();

		$debug = JDEBUG ? $this->getOutput() : '';

		$contents = str_replace('%%%DEBUG%%%', $debug, $contents);

		if ($this->application->get('debug.logging'))
		{
			$this->writeLog($exception, $message, $statusCode);
		}

		return $contents;
	}

	/**
	 * Write a log file entry.
	 *
	 * @param   \Exception  $exception   The Exception.
	 * @param   string      $message     An additional message.
	 * @param   integer     $statusCode  The status code.
	 *
	 * @return  void
	 */
	protected function writeLog(\Exception $exception, $message, $statusCode)
	{
		$code = $exception->getCode();
		$code = $code ? : $statusCode;

		$log = array();

		switch ($code)
		{
			case 404 :
			case 403 :
			case 500 :
				$log[] = '';
				$log[] = $exception->getMessage();

				if ($message)
				{
					$log[] = $message;
				}

				$log[] = '';
				break;

			default :
				$log[] = '';
				$log[] = 'Unknown status code: ' . $code;
				$log[] = $exception->getMessage();

				if ($message)
				{
					$log[] = $message;
				}

				$log[] = '';

				$code = 500;
				break;
		}

		$path = $this->getLogPath($code);

		if (!$path)
		{
			return;
		}

		if (!file_put_contents($path, implode("\n", $log), FILE_APPEND | LOCK_EX))
		{
			echo __METHOD__ . ' - File could not be written :( - ' . $path;
		}
	}

	/**
	 * Get a log path.
	 *
	 * @param   string  $type  The log type.
	 *
	 * @return string
	 */
	public function getLogPath($type)
	{
		if ('php' == $type)
		{
			return ini_get('error_log');
		}

		$logPath = $this->application->get('debug.' . $type . '-log');

		if (!realpath(dirname($logPath)))
		{
			$logPath = JPATH_ROOT . '/' . $logPath;
		}

		if (realpath(dirname($logPath)))
		{
			return $logPath;
		}

		return '';
	}

	/**
	 * Simple highlight for SQL queries.
	 *
	 * @param   string  $query   The query to highlight
	 * @param   string  $prefix  Table prefix.
	 *
	 * @since   1.0
	 * @return  string
	 */
	protected function highlightQuery($query, $prefix)
	{
		$newlineKeywords = '#\b(FROM|LEFT|INNER|OUTER|WHERE|SET|VALUES|ORDER|GROUP|HAVING|LIMIT|ON|AND|CASE)\b#i';

		$query = htmlspecialchars($query, ENT_QUOTES);

		$query = preg_replace($newlineKeywords, '<br />&#160;&#160;\\0', $query);

		$regex = array(

			// Tables are identified by the prefix
			'/(=)/'
			=> '<span class="dbgOperator">$1</span>',

			// All uppercase words have a special meaning
			'/(?<!\w|>)([A-Z_]{2,})(?!\w)/x'
			=> '<span class="dbgCommand">$1</span>',

			// Tables are identified by the prefix
			'/(' . $prefix . '[a-z_0-9]+)/'
			=> '<span class="dbgTable">$1</span>'
		);

		$query = preg_replace(array_keys($regex), array_values($regex), $query);

		$query = str_replace('*', '<b style="color: red;">*</b>', $query);

		return $query;
	}
}
