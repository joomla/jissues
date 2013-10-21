<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Debug;

use g11n\g11n;

use Joomla\Application\AbstractApplication;
use Joomla\Profiler\Profiler;

use Joomla\Utilities\ArrayHelper;
use JTracker\Application;
use JTracker\Container;

use App\Debug\Database\DatabaseDebugger;
use App\Debug\Format\Html\SqlFormat;
use App\Debug\Format\Html\TableFormat;
use App\Debug\Handler\ProductionHandler;

use Kint;

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
	 * @var    Application
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
	 * @var    Logger
	 * @since  1.0
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param   AbstractApplication  $application  The application
	 *
	 * @since   1.0
	 */
	public function __construct(AbstractApplication $application)
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

		with(new Run)
			->pushHandler($handler)
			->register();
	}

	/**
	 * Set up loggers.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function setupLogging()
	{
		$this->log['db'] = array();

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

			$db = Container::retrieve('db');
			$db->setLogger($logger);
			$db->setDebug(true);
		}

		if (!$this->application->get('debug.logging'))
		{
			$this->logger = new Logger('JTracker');
			$this->logger->pushHandler(new NullHandler);

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
		// $db = Container::retrieve('db');

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

		$entry->profile = isset($context['profile']) ? $context['profile'] : 'n/a';

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
		$navigation = $this->getNavigation();

		$debug = array();

		// Check if debug is only displayed for admin users
		if ($this->application->get('debug.admin'))
		{
			if (!$this->application->getUser()->isAdmin)
			{
				return '';
			}
		}

		if ($this->application->get('debug.database'))
		{
			$debug[] = '<h3><a class="muted" href="javascript:;" name="dbgDatabase">Database</a></h3>';

			$debug[] = $this->renderDatabase();
		}

		if ($this->application->get('debug.system'))
		{
			$debug[] = '<h3><a class="muted" href="javascript:;" name="dbgProfile">Profile</a></h3>';
			$debug[] = $this->renderProfile();

			$debug[] = '<h3><a class="muted" href="javascript:;" name="dbgUser">User</a></h3>';
			$debug[] = @Kint::dump($this->application->getSession()->get('user'));

			$debug[] = '<h3><a class="muted" href="javascript:;" name="dbgProject">Project</a></h3>';
			$debug[] = @Kint::dump($this->application->getSession()->get('project'));
		}

		if ($this->application->get('debug.language'))
		{
			$debug[] = '<h3><a class="muted" href="javascript:;" name="dbgLanguageStrings">Language Strings</a></h3>';
			$debug[] = $this->renderLanguageStrings();

			$debug[] = '<h3><a class="muted" href="javascript:;" name="dbgLanguageFiles">Language Files</a></h3>';
			$debug[] = $this->renderLanguageFiles();
		}

		return implode("\n", $navigation) . implode("\n", $debug);
	}

	private function getNavigation()
	{
		$navigation = array();

		// OK, here comes some very beautiful CSS !!
		// It's kinda "hidden" here, so evil template designers won't find it :P
		$navigation[] = '
		<style>
			pre.dbQuery { background-color: #333; color: white; font-weight: bold; }
			span.dbgTable { color: yellow; }
			span.dbgCommand { color: lime; }
			span.dbgOperator { color: red; }
			h2.debug { background-color: #333; color: lime; border-radius: 10px; padding: 0.5em; }
			h3:target { margin-top: 200px;}
		</style>
		';

		$navigation[] = '<div class="navbar navbar-fixed-bottom" id="debugBar">';
		$navigation[] = '<div class="navbar-inner">';
		$navigation[] = '<a class="brand" href="#top">&nbsp;<i class="icon icon-joomla"></i></a>';
		$navigation[] = '<ul class="nav">';

		if ($this->application->get('debug.database'))
		{
			$count = count($this->getLog('db'));

			$navigation[] = '<li class="hasTooltip"'
				. ' title="' . sprintf(g11n4t('One database query', '%d database queries', $count), $count) . '">'
				. '<a href="#dbgDatabase"><i class="icon icon-database"></i> '
				. $this->getBadge($count)
				. '</a></li>';
		}

		if ($this->application->get('debug.system'))
		{
			$profile = $this->getProfile();
			$user    = $this->application->getSession()->get('user');

			$navigation[] = '<li><a href="#dbgProfile"><i class="icon icon-lightning"></i> '
				. sprintf('%s MB', $this->getBadge(number_format($profile->peak / 1000000, 3)))
				. ' '
				. sprintf('%s sec.', $this->getBadge(number_format($profile->time, 3)))
				. '</a></li>';

			$navigation[] = '<li><a href="#dbgUser"><i class="icon icon-user"></i> <span class="badge">'
				. ($user && $user->username ? $user->username : g11n3t('Guest'))
				.'</span></a></li>';

			$navigation[] = '<li><a href="#dbgProject"><i class="icon icon-cube"></i> <span class="badge">'
				. ($this->application->getSession()->get('project')->title ? : g11n3t('No Project'))
				.'</span></a></li>';
		}

		if ($this->application->get('debug.language'))
		{
			$info = $this->getLanguageStringsInfo();
			$badge = $this->getBadge($info->untranslateds, array(1 => 'badge-warning'));

			$navigation[] = '<li><a href="#dbgLanguageStrings"><i class="icon icon-question-sign"></i>  ' . $badge . '/' . $this->getBadge($info->total) . '</a></li>';
			$navigation[] = '<li><a href="#dbgLanguageFiles"><i class="icon icon-file-word"></i> ' . $this->getBadge(count(g11n::getEvents())) . '</a></li>';
		}

		$navigation[] = '</ul>';
		$navigation[] = '</div>';
		$navigation[] = '</div>';

		return $navigation;
	}

	/**
	 * Render the profiler output.
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 */
	private function getProfile()
	{
		$points = $this->profiler->getPoints();

		$pointStart = $points[0]->getName();
		$pointEnd   = $points[count($points) - 1]->getName();

		$profile = new \stdClass;

		$profile->peak = $this->profiler->getMemoryBytesBetween($pointStart, $pointEnd);
		$profile->time = $this->profiler->getTimeBetween($pointStart, $pointEnd);

		return $profile;
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
	 * @return string
	 *
	 * @since  1.0
	 */
	public function renderLanguageFiles()
	{
		$items = array();
		$tableFormat = new TableFormat;

		foreach (g11n::getEvents() as $e)
		{
			$items[] = ArrayHelper::fromObject($e);
		}

		return $tableFormat->fromArray($items);
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

			return str_replace(JPATH_ROOT, 'JROOT', $exception->getMessage())
			. '<pre>' . $exception->getTraceAsString() . '</pre>'
			. 'Previous: ' . get_class($exception->getPrevious());
		}

		$view = new \JTracker\View\TrackerDefaultView;

		$message = '';

		foreach ($context as $key => $value)
		{
			$message .= $key . ': ' . $value . "\n";
		}

		$view->setLayout('exception')
			->getRenderer()
			->set('exception', $exception)
			->set('message', str_replace(JPATH_ROOT, 'ROOT', $message));

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
	 *
	 * @since   1.0
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * Render database information.
	 *
	 * @return string
	 *
	 * @since  1.0
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
		$dbDebugger  = new DatabaseDebugger(Container::retrieve('db'));

		$debug[] = count($dbLog) . ' Queries.';

		$prefix = $dbDebugger->getPrefix();

		foreach ($dbLog as $i => $entry)
		{
			$explain = $dbDebugger->getExplain($entry->sql);

			$debug[] = '<pre class="dbQuery">' . $sqlFormat->highlightQuery($entry->sql, $prefix) . '</pre>';

			if (isset($entry->times) && is_array($entry->times))
			{
				$debug[] = sprintf('Query Time: %.3f ms', ($entry->times[1] - $entry->times[0]) * 1000) . '<br />';
			}

			// Tabs headers

			$debug[] = '<ul class="nav nav-tabs">';

			if ($explain)
			{
				$debug[] = '<li><a data-toggle="tab" href="#queryExplain-' . $i . '">Explain</a></li>';
			}

			if (isset($entry->trace) && is_array($entry->trace))
			{
				$debug[] = '<li><a data-toggle="tab" href="#queryTrace-' . $i . '">Trace</a></li>';
			}

			if (isset($entry->profile) && is_array($entry->profile))
			{
				$debug[] = '<li><a data-toggle="tab" href="#queryProfile-' . $i . '">Profile</a></li>';
			}

			$debug[] = '</ul>';

			// Tabs contents

			$debug[] = '<div class="tab-content">';

			if ($explain)
			{
				$debug[] = '<div id="queryExplain-' . $i . '" class="tab-pane">';

				$debug[] = $explain;
				$debug[] = '</div>';
			}

			if (isset($entry->trace) && is_array($entry->trace))
			{
				$debug[] = '<div id="queryTrace-' . $i . '" class="tab-pane">';
				$debug[] = $tableFormat->fromTrace($entry->trace);
				$debug[] = '</div>';
			}

			if (isset($entry->profile) && is_array($entry->profile))
			{
				$debug[] = '<div id="queryProfile-' . $i . '" class="tab-pane">';
				$debug[] = $tableFormat->fromArray($entry->profile);
				$debug[] = '</div>';
			}

			$debug[] = '</div>';
		}

		return implode("\n", $debug);
	}

	/**
	 * Prints out translated and untranslated strings.
	 *
	 * @return string
	 *
	 * @since   1.0
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

	/**
	 * Get info about processed language strings.
	 *
	 * @return \stdClass
	 *
	 * @since  1.0
	 */
	protected function getLanguageStringsInfo()
	{
		$items = g11n::get('processedItems');

		$info = new \stdClass;

		$info->total = count($items);
		$info->untranslateds = 0;

		foreach ($items as $item)
		{
			if ('-' == $item->status)
			{
				$info->untranslateds ++;
			}
		}

		return $info;
	}

	/**
	 * Create a bootstrap HTML badge.
	 *
	 * an array with optional css class can be supplied. If the $count value exceeds the option value this class will be used.
	 * E.g.: [5 => 'warning', 15 => 'danger']
	 *
	 * @param   integer  $count  The number to display inside the badge.
	 * @param   array    $options  An indexed array of values and CSS classes.
	 *
	 * @return string
	 */
	private function getBadge($count, array $options = array())
	{
		$class = '';

		foreach ($options as $opCount => $opClass)
		{
			if ($count >= $opCount)
			{
				$class = ' ' . $opClass;
			}
		}

		return '<span class="badge' . $class . '">' . $count . '</span>';
	}
}
