<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Debug;

use Joomla\Factory;
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
			$handler = new \Joomla\Tracker\Components\Debug\Handler\ProductionHandler;
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
		/* @type TrackerApplication $application */
		//$application = Factory::$application;
		//$db = $application->getDatabase();

		$entry = new \stdClass;

		$entry->sql     = isset($context['sql'])     ? $context['sql']     : 'n/a';
		$entry->time    = isset($context['time'])    ? $context['time']    : 'n/a';
		$entry->trace   = isset($context['trace'])   ? $context['trace']   : 'n/a';

		if ($entry->sql == 'SHOW PROFILE')
		{
			return $this;
		}

/*		$db->setQuery('SHOW PROFILE');
		/ Get the profiling information
		$entry->profile = $db->loadAssocList();
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
			pre.dbQuery { background-color: #333; color: white; font-weight: bold; }
			span.dbgTable { color: yellow; }
			span.dbgCommand { color: lime; }
			span.dbgOperator { color: red; }
			h2.debug { background-color: #333; color: lime; border-radius: 10px; padding: 0.5em; }
		</style>
		';

		$debug = array();

		$debug[] = $css;

		$debug[] = '<h2 class="debug">Debug</h2>';

		$dbLog = $this->getLog('db');

		if ($dbLog)
		{
			$debug[] = '<h3>Database</h3>';

			$debug[] = count($dbLog) . ' Queries.';

			$prefix = $this->application->getDatabase()->getPrefix();

			foreach ($dbLog as $i => $entry)
			{
				// @is_object($entry))
				if (0)
				{
					$debug[] = '<pre class="dbQuery">' . $this->highlightQuery($entry->sql, $prefix) . '</pre>';
					$debug[] = sprintf('Query Time: %.3f ms', $entry->time * 1000) . '<br />';

					$debug[] = '';
					$debug[] = '<ul class="nav nav-tabs">';
					$debug[] = '<li><a data-toggle="tab" href="#queryExplain-' . $i . '">Explain</a></li>';
					$debug[] = '<li><a data-toggle="tab" href="#queryTrace-' . $i . '">Trace</a></li>';
					$debug[] = '<li><a data-toggle="tab" href="#queryProfile-' . $i . '">Profile</a></li>';
					$debug[] = '</ul>';

					$debug[] = '<div class="tab-content">';

					$debug[] = '<div id="queryExplain-' . $i . '" class="tab-pane">';

					// $debug[] = $this->getExplain($entry->sql);
					$debug[] = '</div>';
					$debug[] = '<div id="queryTrace-' . $i . '" class="tab-pane">';

					// $debug[] = $this->traceToHtmlTable($entry->trace);
					$debug[] = '</div>';
					$debug[] = '<div id="queryProfile-' . $i . '" class="tab-pane">';

					// $debug[] = $this->tableToHtml($entry->profile);
					$debug[] = '</div>';

					$debug[] = '</div>';
				}
				else
				{
					$debug[] = '<pre class="dbQuery">' . $this->highlightQuery($entry->sql, $prefix) . '</pre>';

					// $debug[] = $this->getExplain($entry->sql);
				}
			}
		}

		$debug[] = '<h3>Profile</h3>';
		$debug[] = $this->renderProfile();
		$debug[] = '</div>';

		$session = Factory::$application->getSession();

		ob_start();
		echo '<h3>User</h3>';
		var_dump($session->get('user'));
		echo '<h3>Project</h3>';
		var_dump($session->get('project'));
		$session = ob_get_clean();

		$debug[] = $session;

		return implode("\n", $debug);
	}

	/**
	 * Get a database explain statement.
	 *
	 * @param   string  $query  The query.
	 *
	 * @since  1.0
	 * @return string
	 */
	protected function getExplain($query)
	{
		/* @type TrackerApplication $application */
		$application = Factory::$application;
		$db = $application->getDatabase();

		$db->setDebug(false);

		// Run an EXPLAIN EXTENDED query on the SQL query if possible:
		$explain = '';

		if (in_array($db->name, array('mysqli','mysql', 'postgresql')))
		{
			$dbVersion56 = ( strncmp($db->name, 'mysql', 5) == 0 ) && version_compare($db->getVersion(), '5.6', '>=');

			if ((stripos($query, 'select') === 0) || ($dbVersion56 && ((stripos($query, 'delete') === 0)||(stripos($query, 'update') === 0))))
			{
				$db->setQuery('EXPLAIN ' . ($dbVersion56 ? 'EXTENDED ' : '') . $query);

				if ($db->execute())
				{
					$explainTable = $db->loadAssocList();
					$explain = $this->tableToHtml($explainTable);
				}
				else
				{
					$explain = 'Failed EXPLAIN on query: ' . htmlspecialchars($query);
				}
			}
		}

		$db->setDebug(true);

		return $explain;
	}

	/**
	 * Get a db profile.
	 *
	 * @param   string  $query  The query.
	 *
	 * @return string
	 */
	protected function getProfile($query)
	{
		/* @type TrackerApplication $application */
		$application = Factory::$application;
		$db = $application->getDatabase();

		// Run a SHOW PROFILE query:
		$profile = '';

		if (false == in_array($db->name, array('mysqli','mysql')))
		{
			return sprintf('%d database is not supported yet.', $db->name);
		}

		$db->setDebug(false);

		$dbVersion5037 = (strncmp($db->name, 'mysql', 5) == 0 ) && version_compare($db->getVersion(), '5.0.37', '>=');

		if ($dbVersion5037)
		{
			// Run a SHOW PROFILE query:
			// SHOW PROFILE ALL FOR QUERY ' . (int) ($k+1));
			$db->setQuery('SHOW PROFILES');
			$profiles = $db->loadAssocList();

			if ($profiles)
			{
				foreach ($profiles as $qn)
				{
					$db->setQuery('SHOW PROFILE FOR QUERY ' . (int) ($qn['Query_ID']));
					$this->sqlShowProfileEach[(int) ($qn['Query_ID'] - 1)] = $db->loadAssocList();
				}
			}
		}

		if (in_array($db->name, array('mysqli','mysql', 'postgresql')))
		{
			$log = $db->getLog();

			foreach ($log as $k => $query)
			{
				$dbVersion56 = ( strncmp($db->name, 'mysql', 5) == 0 ) && version_compare($db->getVersion(), '5.6', '>=');

				if ((stripos($query, 'select') === 0) || ($dbVersion56 && ((stripos($query, 'delete') === 0)||(stripos($query, 'update') === 0))))
				{
					$db->setQuery('EXPLAIN ' . ($dbVersion56 ? 'EXTENDED ' : '') . $query);
					$this->explains[$k] = $db->loadAssocList();
				}
			}
		}

			if (isset($this->sqlShowProfileEach[$k]))
			{
				$profileTable = $this->sqlShowProfileEach[$k];
				$profile = $this->tableToHtml($profileTable);
			}
			else
			{
				$profile = 'No SHOW PROFILE (maybe because more than 100 queries)';
			}
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
			case 403 :
			case 404 :
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
	 * @return string
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

	/**
	 * Convert a stack trace ta a HTML table.
	 *
	 * @param   array  $trace  The stack trace
	 *
	 * @since  1.0
	 * @return string
	 */
	protected function traceToHtmlTable(array $trace)
	{
		$html = array();

		$html[] = '<table class="table table-hover">';

		foreach ($trace as $entry)
		{
			$html[] = '<tr>';
			$html[] = '<td>';

			if (isset($entry['file']))
			{
				$html[] = basename($entry['file']) . '@' . $entry['line'];
			}

			$html[] = '</td>';
			$html[] = '<td>';

			if (isset($entry['class']))
			{
				$html[] = $entry['class'] . $entry['type'] . $entry['function'];
			}
			elseif (isset($entry['function']))
			{
				$html[] = $entry['function'];
			}

			$html[] = '</td>';
			$html[] = '</tr>';
		}

		$html[] = '</table>';

		return implode("\n", $html);
	}

	/**
	 * Displays errors in language files.
	 *
	 * @param   array  $table  The table.
	 *
	 * @return string
	 *
	 * @since CMS 3.1.2
	 */
	protected function tableToHtml($table)
	{
		if (! $table)
		{
			return null;
		}

		$html = '<table class="table table-striped dbgQueryTable"><tr>';

		foreach (array_keys($table[0]) as $k)
		{
			$html .= '<th>' . htmlspecialchars($k) . '</th>';
		}

		$html .= '</tr>';

		foreach ($table as $tr)
		{
			$html .= '<tr>';

			foreach ($tr as $td)
			{
				$html .= '<td>' . ($td === null ? 'NULL' : htmlspecialchars($td) ) . '</td>';
			}

			$html .= '</tr>';
		}

		$html .= '</table>';

		return $html;
	}
}
