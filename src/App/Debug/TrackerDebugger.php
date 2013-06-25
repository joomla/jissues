<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Debug;

use g11n\g11n;
use g11n\Language\Debugger;

use Joomla\Factory;
use Joomla\Profiler\Profiler;

use JTracker\Application\TrackerApplication;

use App\Debug\Database\DatabaseDebugger;
use App\Debug\Format\Html\SqlFormat;
use App\Debug\Format\Html\TableFormat;

/**
 * Class TrackerDebugger.
 *
 * @since  1.0
 */
class TrackerDebugger
{
	/**
	 * @var    TrackerApplication
	 * @since  1.0
	 */
	private $application;

	/**
	 * @var    array
	 * @since  1.0
	 */
	private $log = array();

	/**
	 * @var    Profiler
	 * @since  1.0
	 */
	private $profiler;

	/**
	 * Constructor.
	 *
	 * @param   TrackerApplication  $application  The application
	 *
	 * @since   1.0
	 */
	public function __construct(TrackerApplication $application)
	{
		$this->application = $application;

		$this->profiler = JDEBUG ? new Profiler('Tracker') : null;

		$this->log['db'] = array();

		/*
		/ Register an error handler.
		if (JDEBUG)
		{
			$handler = new \Whoops\Handler\PrettyPageHandler;
		}
		else
		{
			$handler = new \App\Debug\Handler\ProductionHandler;
		}

		/ $handler = new \Whoops\Handler\JsonResponseHandler;

		$run = new \Whoops\Run;
		$run->pushHandler($handler);
		$run->register();
*/
	}

	/**
	 * Mark a profile point.
	 *
	 * @param   string  $name  The profile point name.
	 *
	 * @return  \Joomla\Profiler\ProfilerInterface
	 *
	 * @since   1.0
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
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function addDatabaseEntry($level, $message, $context)
	{
		/* @type TrackerApplication $application */
		// $application = Factory::$application;
		// $db = $application->getDatabase();

		$entry = new \stdClass;

		$entry->sql   = isset($context['sql'])   ? $context['sql']   : 'n/a';
		$entry->times = isset($context['times']) ? $context['times'] : 'n/a';
		$entry->trace = isset($context['trace']) ? $context['trace'] : 'n/a';

		if ($entry->sql == 'SHOW PROFILE')
		{
			return $this;
		}

		// $db->setQuery('SHOW PROFILE');
		$entry->profile = '';

		// $db->loadAssocList();

/*
		/ Get the profiling information
			$cursor = mysqli_query($this->connection, 'SHOW PROFILE');
			$profile = '';
*/

		// $entry->profile = isset($context['profile']) ? $context['profile'] : 'n/a';

		$this->log['db'][] = $entry;

		if (0)
		{
			$this->log['db'][] = isset($context['sql'])
				? $context['sql']
				: 'DATABASE - Level: ' . $level . ' - Message: ' . $message;
		}

		// Log to a text file

		$log = array();

		$log[] = '';
		$log[] = date('y-m-d H:i:s');
		$log[] = $this->application->get('uri.request');

		$log[] = isset($context['sql'])
			? ('{sql}' == $message ? '' : $message . ' - ') . $context['sql']
			: 'DATABASE - Level: ' . $level . ' - Message: ' . $message;

		$log[] = '';

		$fName = $this->getLogPath('root') . '/database.log';

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
	 * @return  array
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
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
	 * Get the debug output.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getOutput()
	{
		if (!JDEBUG)
		{
			return '';
		}

		$dbDebugger = new DatabaseDebugger($this->application->getDatabase());
		$tableFormat = new TableFormat;

		// OK, here comes some very beautiful CSS !!
		// It's kinda "hidden" here, so evil template designers won't find it :P
		$css = '
		<style>
			pre.dbQuery { background-color: #333; color: white; font-weight: bold; }
			span.dbgTable { color: yellow; }
			span.dbgCommand { color: lime; }
			span.dbgOperator { color: red; }
			h2.debug { background-color: #333; color: lime; border-radius: 10px; padding: 0.5em; }
			h3:target { margin-top: 200px;}
		</style>
		';

		$debug = array();

		$debug[] = $css;

		$debug[] = '<div class="well well-small navbar navbar-fixed-bottom">';
		$debug[] = '<a class="brand" href="javascript:;">Debug</a>';
		$debug[] = '<ul class="nav">
    <li><a href="#dbgDatabase">Database</a></li>
    <li><a href="#dbgProfile">Profile</a></li>
    <li><a href="#dbgUser">User</a></li>
    <li><a href="#dbgProject">Project</a></li>';

		if ($this->application->get('debug.language'))
		{
			$debug[] = '<li><a href="#dbgLanguage">Language</a></li>';
		}

		$debug[] = '</ul>';
		$debug[] = '</div>';

		$dbLog = $this->getLog('db');

		if ($dbLog)
		{
			$sqlFormat = new SqlFormat;

			$debug[] = '<h3><a class="muted" href="javascript:;" name="dbgDatabase">Database</a></h3>';

			$debug[] = count($dbLog) . ' Queries.';

			$prefix = $dbDebugger->getPrefix();

			foreach ($dbLog as $i => $entry)
			{
				// @is_object($entry))
				if (1)
				{
					$explain = $dbDebugger->getExplain($entry->sql);

					$debug[] = '<pre class="dbQuery">' . $sqlFormat->highlightQuery($entry->sql, $prefix) . '</pre>';
					$debug[] = sprintf('Query Time: %.3f ms', ($entry->times[1] - $entry->times[0]) * 1000) . '<br />';

					$debug[] = '<ul class="nav nav-tabs">';

					if ($explain)
					{
						$debug[] = '<li><a data-toggle="tab" href="#queryExplain-' . $i . '">Explain</a></li>';
					}

					$debug[] = '<li><a data-toggle="tab" href="#queryTrace-' . $i . '">Trace</a></li>';

					// $debug[] = '<li><a data-toggle="tab" href="#queryProfile-' . $i . '">Profile</a></li>';

					$debug[] = '</ul>';

					$debug[] = '<div class="tab-content">';

					$debug[] = '<div id="queryExplain-' . $i . '" class="tab-pane">';

					$debug[] = $explain;
					$debug[] = '</div>';

					$debug[] = '<div id="queryTrace-' . $i . '" class="tab-pane">';

					if (is_array($entry->trace))
					{
						$debug[] = $tableFormat->fromTrace($entry->trace);
					}

					$debug[] = '</div>';

					// $debug[] = '<div id="queryProfile-' . $i . '" class="tab-pane">';

					// $debug[] = $tableFormat->fromArray($entry->profile);
					// $debug[] = '</div>';

					$debug[] = '</div>';
				}
				else
				{
					$debug[] = '<pre class="dbQuery">' . $sqlFormat->highlightQuery($entry->sql, $prefix) . '</pre>';
					$debug[] = $dbDebugger->getExplain($entry->sql);
				}
			}
		}

		$debug[] = '<h3><a class="muted" href="javascript:;" name="dbgProfile">Profile</a></h3>';
		$debug[] = $this->renderProfile();
		$debug[] = '</div>';

		$session = $this->application->getSession();

		ob_start();

		echo '<h3><a class="muted" href="javascript:;" name="dbgUser">User</a></h3>';
		var_dump($session->get('user'));

		echo '<h3><a class="muted" href="javascript:;" name="dbgProject">Project</a></h3>';
		var_dump($session->get('project'));

		if ($this->application->get('debug.language'))
		{
			echo '<h3><a class="muted" href="javascript:;" name="dbgLanguage">Language</a></h3>';
			$this->renderLanguage();
		}

		$debug[] = ob_get_clean();
		$debug[] = '</div>';

		return implode("\n", $debug);
	}

	/**
	 * Render the profiler output.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function renderProfile()
	{
		return $this->profiler->render();
	}

	/**
	 * Render language debug information.
	 *
	 * @since  1.0
	 * @return void
	 */
	public function renderLanguage()
	{
		g11n::debugPrintTranslateds();

		echo '<h2>Language files loaded</h2>';
		g11n::printEvents();
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

		$viewClass = '\\JTracker\\View\\TrackerDefaultView';

		/* @type \JTracker\View\TrackerDefaultView $view */
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
	 *
	 * @since   1.0
	 */
	protected function writeLog(\Exception $exception, $message, $statusCode)
	{
		$code = $exception->getCode();
		$code = $code ? : $statusCode;

		$log = array();

		switch ($code)
		{
			case 403 :
			case 404 :
			case 500 :
				$log[] = '';
				$log[] = date('y-m-d H:i:s');
				$log[] = $this->application->get('uri.request');
				$log[] = $exception->getMessage();

				if ($message)
				{
					$log[] = $message;
				}

				$log[] = '';
				break;

			default :
				$log[] = '';
				$log[] = date('y-m-d H:i:s');
				$log[] = $this->application->get('uri.request');
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

		$path = $this->getLogPath('root') . '/' . $code . '.log';

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
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getLogPath($type)
	{
		if ('root' == $type)
		{
			$logPath = $this->application->get('debug.log-path');

			if (!realpath($logPath))
			{
				$logPath = JPATH_ROOT . '/' . $logPath;
			}

			if (realpath($logPath))
			{
				return realpath($logPath);
			}

			return JPATH_ROOT;
		}

		if ('php' == $type)
		{
			return ini_get('error_log');
		}

		// @todo: remove the rest..

		$logPath = $this->application->get('debug.' . $type . '-log');

		if (!realpath(dirname($logPath)))
		{
			$logPath = JPATH_ROOT . '/' . $logPath;
		}

		if (realpath(dirname($logPath)))
		{
			return realpath($logPath);
		}

		return JPATH_ROOT;
	}
}
