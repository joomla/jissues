<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Debug;

use g11n\g11n;

use Joomla\DI\Container;
use Joomla\Profiler\Profiler;
use Joomla\Utilities\ArrayHelper;

use JTracker\Application;

use App\Debug\Database\DatabaseDebugger;
use App\Debug\Format\Html\SqlFormat;
use App\Debug\Format\Html\TableFormat;
use App\Debug\Handler\ProductionHandler;

use JTracker\View\Renderer\TrackerExtension;

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
	 * Application object.
	 *
	 * @var    Application
	 * @since  1.0
	 */
	private $application;

	/**
	 * Log array.
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $log = array();

	/**
	 * Profiler object.
	 *
	 * @var    Profiler
	 * @since  1.0
	 */
	private $profiler;

	/**
	 * Logger object.
	 *
	 * @var    Logger
	 * @since  1.0
	 */
	private $logger;

	/**
	 * @var  Container
	 * @since  1.0
	 */
	private $container;

	/**
	 * Constructor.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @since   1.0
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;

		$this->application = $container->get('app');

		$this->profiler = $container->get('app')->get('debug.system') ? new Profiler('Tracker') : null;

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

		(new Run)
			->pushHandler($handler)
			->register();
	}

	/**
	 * Set up loggers.
	 *
	 * @return  $this  Method allows chaining
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

			$db = $this->container->get('db');
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
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function addDatabaseEntry($record)
	{
		// $db = $this->container->get('db');

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
			$debug[] = '<div id="dbgDatabase">';
			$debug[] = '<h3>' . g11n3t('Database') . '</h3>';

			$debug[] = $this->renderDatabase();
			$debug[] = '</div>';
		}

		if ($this->application->get('debug.system'))
		{
			$debug[] = '<div id="dbgProfile">';
			$debug[] = '<h3>' . g11n3t('Profile') . '</h3>';
			$debug[] = $this->renderProfile();
			$debug[] = '</div>';

			$debug[] = '<div id="dbgUser">';
			$debug[] = '<h3>' . g11n3t('User') . '</h3>';
			$debug[] = @Kint::dump($this->application->getUser());
			$debug[] = '</div>';

			$debug[] = '<div id="dbgProject">';
			$debug[] = '<h3>' . g11n3t('Project') . '</h3>';
			$debug[] = @Kint::dump($this->application->getProject());
			$debug[] = '</div>';
		}

		if ($this->application->get('debug.language'))
		{
			$debug[] = '<div id="dbgLanguageStrings">';
			$debug[] = '<h3>' . g11n3t('Language Strings') . '</h3>';
			$debug[] = $this->renderLanguageStrings();
			$debug[] = '</div>';

			$debug[] = '<div id="dbgLanguageFiles">';
			$debug[] = '<h3>' . g11n3t('Language Files') . '</h3>';
			$debug[] = $this->renderLanguageFiles();
			$debug[] = '</div>';
		}

		if (!$debug)
		{
			return '';
		}

		return implode("\n", $this->getNavigation()) . implode("\n", $debug);
	}

	/**
	 * Get the navigation bar.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	private function getNavigation()
	{
		$navigation = array();

		// OK, here comes some very beautiful CSS !!
		// It's kinda "hidden" here, so evil template designers won't find it :P
		$navigation[] = '
		<style>
			div#debugBar { background-color: #eee; }
			div#debugBar a:hover { background-color: #ddd; }
			div#debugBar a:active { background-color: #ccc; }
			pre.dbQuery { background-color: #333; color: white; font-weight: bold; }
			span.dbgTable { color: yellow; }
			span.dbgCommand { color: lime; }
			span.dbgOperator { color: red; }
			div:target { border: 2px dashed orange; padding: 5px; padding-top: 100px; }
			div:target { transition:all 0.5s ease; }
			body { margin-bottom: 50px; }
		</style>
		';

		$navigation[] = '<div class="navbar navbar-fixed-bottom" id="debugBar">';

		$navigation[] = '<a class="brand" href="#top" class="hasTooltip" title="' . g11n3t('Go up') . '">'
			. '&nbsp;<i class="icon icon-joomla"></i></a>';

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

			$navigation[] = '<li class="hasTooltip"'
				. ' title="' . g11n3t('Profile') . '">'
				. '<a href="#dbgProfile"><i class="icon icon-lightning"></i> '
				. sprintf('%s MB', $this->getBadge(number_format($profile->peak / 1000000, 2)))
				. ' '
				. sprintf('%s ms', $this->getBadge(number_format($profile->time * 1000)))
				. '</a></li>';
		}

		if ($this->application->get('debug.language'))
		{
			$info = $this->getLanguageStringsInfo();
			$badge = $this->getBadge($info->untranslateds, array(1 => 'badge-warning'));
			$count = count(g11n::getEvents());

			$navigation[] = '<li class="hasTooltip"'
				. ' title="' . sprintf(
					g11n4t(
							'One untranslated string of %2$d', '%1$d untranslated strings of %2$d', $info->untranslateds
						), $info->untranslateds, $info->total
					) . '">'
				. '<a href="#dbgLanguageStrings"><i class="icon icon-question-sign"></i>  '
				. $badge . '/' . $this->getBadge($info->total)
				. '</a></li>';

			$navigation[] = '<li class="hasTooltip"'
				. ' title="' . sprintf(g11n4t('One language file loaded', '%d language files loaded', $count), $count) . '">'
				. '<a href="#dbgLanguageFiles"><i class="icon icon-file-word"></i> '
				. $this->getBadge($count)
				. '</a></li>';
		}

		if ($this->application->get('debug.system'))
		{
			$user    = $this->application->getUser();
			$project = $this->application->getProject();

			$title = $project ? $project->title : g11n3t('No Project');

			// Add build commit if available
			$buildHref = '#';

			if (file_exists(JPATH_ROOT . '/current_SHA'))
			{
				$build = trim(file_get_contents(JPATH_ROOT . '/current_SHA'));
				preg_match('/-g([0-9a-z]+)/', $build, $matches);
				$buildHref = $matches
					? 'https://github.com/joomla/jissues/commit/' . $matches[1]
					: '#';
			}
			// Fall back to composer.json version
			else
			{
				$composer = json_decode(trim(file_get_contents(JPATH_ROOT . '/composer.json')));
				$build    = $composer->version;
			}

			$navigation[] = '<li class="hasTooltip"'
				. ' title="' . g11n3t('User') . '">'
				. '<a href="#dbgUser"><i class="icon icon-user"></i> <span class="badge">'
				. ($user && $user->username ? $user->username : g11n3t('Guest'))
				. '</span></a></li>';

			$navigation[] = '<li class="hasTooltip"'
				. ' title="' . g11n3t('Project') . '">'
				. '<a href="#dbgProject"><i class="icon icon-cube"></i> <span class="badge">'
				. $title
				. '</span></a></li>';

			// Display the build to admins
			if ($this->application->getUser()->isAdmin)
			{
				$navigation[] = '<li class="hasTooltip"'
					. ' title="' . g11n3t('Build') . '">'
					. '<a href="' . $buildHref . '"><i class="icon icon-broadcast"></i> <span class="badge">'
					. $build
					. '</span></a></li>';
			}
		}

		$navigation[] = '</ul>';
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
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function renderLanguageFiles()
	{
		$items = array();
		$tableFormat = new TableFormat;

		foreach (g11n::getEvents() as $e)
		{
			$items[] = ArrayHelper::fromObject($e);
		}

		$pluralInfo = sprintf(
			g11n3t(
				'Plural forms: <code>%1$d</code><br />Plural function: <code>%2$s</code>'),
			g11n::get('pluralForms'), g11n::get('pluralFunctionRaw'
			)
		);

		return $tableFormat->fromArray($items) . $pluralInfo;
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

		$renderer = $view->getRenderer();
		$renderer->addExtension(new TrackerExtension($this->container));

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
	 * @return  void
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
	 * @return  string  HTML markup for database debug
	 *
	 * @since   1.0
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
		$dbDebugger  = new DatabaseDebugger($this->container->get('db'));

		$debug[] = sprintf(g11n4t('One database query', '%d database queries', count($dbLog)), count($dbLog));

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
	 * @return  string  HTML markup for language debug
	 *
	 * @since   1.0
	 */
	protected function renderLanguageStrings()
	{
		$html = array();

		$items = g11n::get('processedItems');

		$html[] = '<table class="table table-hover table-condensed">';
		$html[] = '<tr>';
		$html[] = '<th>' . g11n3t('String') . '</th><th>' . g11n3t('File (line)') . '</th><th></th>';
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
	 * @return  \stdClass
	 *
	 * @since   1.0
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
	 * @param   integer  $count    The number to display inside the badge.
	 * @param   array    $options  An indexed array of values and CSS classes.
	 *
	 * @return  string
	 *
	 * @since   1.0
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
