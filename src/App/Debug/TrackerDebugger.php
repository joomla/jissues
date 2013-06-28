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

use Joomla\Utilities\ArrayHelper;
use JTracker\Application\TrackerApplication;

use App\Debug\Database\DatabaseDebugger;
use App\Debug\Format\Html\SqlFormat;
use App\Debug\Format\Html\TableFormat;
use App\Debug\Handler\ProductionHandler;

use Kint;

use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\WebProcessor;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * Class TrackerDebugger.
 *
 * @since  1.0
 */
class TrackerDebugger implements LoggerAwareInterface
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
	 * @var Logger
	 */
	private $logger;

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

		$this->profiler = $application->get('debug.system') ? new Profiler('Tracker') : null;

		$this->setupLogging();

		// Register an error handler.
		if (JDEBUG)
		{
			$handler = new PrettyPageHandler;
		}
		else
		{
			$handler = new ProductionHandler;
		}

		$run = new Run;
		$run->pushHandler($handler);
		$run->register();
	}

	/**
	 * Set up loggers.
	 *
	 * @since  1.0
	 * @return $this
	 */
	protected function setupLogging()
	{
		$this->log['db'] = array();

		if (!$this->application->get('debug.logging'))
		{
			return $this;
		}

		$logger = new Logger('JTracker');

		$logger->pushHandler(
			new StreamHandler(
				$this->getLogPath('root') . '/error.log',
				Logger::ERROR
			)
		);

		$logger->pushProcessor(new WebProcessor);

		$this->setLogger($logger);

		if ($this->application->get('debug.database'))
		{
			$logger = new Logger('JTracker');

			$logger->pushHandler(
				new StreamHandler(
					$this->getLogPath('root') . '/database.log',
					Logger::DEBUG
				)
			);

			$logger->pushProcessor(array($this, 'addDatabaseEntry'));
			$logger->pushProcessor(new WebProcessor);

			$this->application->getDatabase()->setLogger($logger);
			$this->application->getDatabase()->setDebug(true);
		}

		return $this;
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
	 * @param   array  $record  The log record.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function addDatabaseEntry($record)
	{
		/* @type TrackerApplication $application */
		// $application = Factory::$application;
		// $db = $application->getDatabase();

		if (false == isset($record['context']))
		{
			return $record;
		}

		$context = $record['context'];

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

		return $record;
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
				throw new \UnexpectedValueException(__METHOD__ . ' unknown category: ' . $category);
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
		$debug[] = '<ul class="nav">';

		if ($this->application->get('debug.database'))
		{
			$debug[] = '<li><a href="#dbgDatabase">Database</a></li>';
		}

		if ($this->application->get('debug.system'))
		{
			$debug[] = '<li><a href="#dbgProfile">Profile</a></li>';
			$debug[] = '<li><a href="#dbgUser">User</a></li>';
			$debug[] = '<li><a href="#dbgProject">Project</a></li>';
		}

		if ($this->application->get('debug.language'))
		{
			$debug[] = '<li><a href="#dbgLanguageStrings">Lang Strings</a></li>';
			$debug[] = '<li><a href="#dbgLanguageFiles">Lang Files</a></li>';
		}

		$debug[] = '</ul>';
		$debug[] = '</div>';

		if ($this->application->get('debug.database'))
		{
			$debug[] = '<h3><a class="muted" href="javascript:;" name="dbgDatabase">Database</a></h3>';

			$debug[] = $this->renderDatabase();
		}

		$debug[] = '<h3><a class="muted" href="javascript:;" name="dbgProfile">Profile</a></h3>';
		$debug[] = $this->renderProfile();

		$session = $this->application->getSession();

		$debug[] = '<h3><a class="muted" href="javascript:;" name="dbgUser">User</a></h3>';
		$debug[] = @Kint::dump($session->get('user'));

		$debug[] = '<h3><a class="muted" href="javascript:;" name="dbgProject">Project</a></h3>';
		$debug[] = @Kint::dump($session->get('project'));

		if ($this->application->get('debug.language'))
		{
			$debug[] = '<h3><a class="muted" href="javascript:;" name="dbgLanguageStrings">Language Strings</a></h3>';
			$debug[] = $this->renderLanguageStrings();

			$debug[] = '<h3><a class="muted" href="javascript:;" name="dbgLanguageFiles">Language Files</a></h3>';
			$debug[] = $this->renderLanguageFiles();
		}

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
	 * @return string
	 */
	public function renderLanguageFiles()
	{
		$events = array();
		$tableFormat = new TableFormat;

		foreach (g11n::getEvents() as $e)
		{
			$events[] = ArrayHelper::fromObject($e);
		}

		return $tableFormat->fromArray($events);
	}

	/**
	 * Method to render an exception in a user friendly format
	 *
	 * @param   \Exception  $exception  The caught exception.
	 * @param   array       $context    The message to display.
	 *
	 * @return  string  The exception output in rendered format.
	 *
	 * @since   1.0
	 */
	public function renderException(\Exception $exception, array $context = array())
	{
		static $loaded = false;

		if ($loaded)
		{
			// Seems that we're recursing...
			$this->logger->error($exception->getCode() . ' ' . $exception->getMessage(), $context);

			return str_replace(JPATH_BASE, 'JROOT', $exception->getMessage())
			. '<pre>' . $exception->getTraceAsString() . '</pre>'
			. 'Previous: ' . get_class($exception->getPrevious());
		}

		$viewClass = '\\JTracker\\View\\TrackerDefaultView';

		/* @type \JTracker\View\TrackerDefaultView $view */
		$view = new $viewClass;

		$message = '';

		foreach ($context as $key => $value)
		{
			$message .= $key . ': ' . $value . "\n";
		}

		$view->setLayout('exception')
			->getRenderer()
			->set('exception', $exception)
			->set('message', str_replace(JPATH_BASE, 'ROOT', $message));

		$loaded = true;

		$contents = $view->render();

		$debug = JDEBUG ? $this->getOutput() : '';

		$contents = str_replace('%%%DEBUG%%%', $debug, $contents);

		$this->logger->error($exception->getCode() . ' ' . $exception->getMessage(), $context);

		return $contents;
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

	/**
	 * Sets a logger instance on the object
	 *
	 * @param   LoggerInterface  $logger  The logger.
	 *
	 * @return null
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * Render database information.
	 *
	 * @since  1.0
	 * @return string
	 */
	protected function renderDatabase()
	{
		$debug = array();

		$dbLog = $this->getLog('db');

		if (!$dbLog)
		{
			return '';
		}

		$tableFormat = new TableFormat;
		$sqlFormat   = new SqlFormat;
		$dbDebugger  = new DatabaseDebugger($this->application->getDatabase());

		$debug[] = count($dbLog) . ' Queries.';

		$prefix = $dbDebugger->getPrefix();

		foreach ($dbLog as $i => $entry)
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

		return implode("\n", $debug);
	}

	/**
	 * Prints out translated and untranslated strings.
	 *
	 * @return string
	 */
	protected function renderLanguageStrings()
	{
		$html = array();

		$items = g11n::get('processedItems');

		$html[] = '<table class="table table-hover table-condensed">';
		$html[] = '<tr>';
		$html[] = '<th>String</th><th>File (line)</th><th></th>';
		$html[] = '</tr>';

		$tableFormat = new TableFormat;

		$i = 0;

		foreach ($items as $string => $item)
		{
			$color =('-' == $item->status)
				? '#ffb2b2;'
				: '#e5ff99;';

			$html[] = '<tr>';
			$html[] = '<td style="border-left: 7px solid ' . $color . '">' . htmlentities($string) . '</td>';
			$html[] = '<td>' . str_replace(JPATH_ROOT, 'ROOT', $item->file) . ' (' . $item->line . ')</td>';
			$html[] = '<td><span class="btn btn-mini" onclick="$(\'#langStringTrace' . $i . '\').slideToggle();">Trace</span></td>';
			$html[] = '</tr>';

			$html[] = '<tr><td colspan="4" id="langStringTrace' . $i . '" style="display: none;">'
				. $tableFormat->fromTrace($item->trace)
				. '</td></tr>';

			$i ++;
		}

		$html[] = '</table>';

		return implode("\n", $html);
	}
}
