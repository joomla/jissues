<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Debug;

use App\Debug\Handler\ProductionHandler;
use App\Debug\Renderer\Html;
use Joomla\DI\Container;
use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;
use Joomla\Profiler\Profiler;
use JTracker\Application\Application;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * Class TrackerDebugger.
 *
 * @since  1.0
 */
class TrackerDebugger implements LoggerAwareInterface, ContainerAwareInterface
{
	use LoggerAwareTrait, ContainerAwareTrait;

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
	private $log = [];

	/**
	 * Profiler object.
	 *
	 * @var    Profiler
	 * @since  1.0
	 */
	private $profiler;

	/**
	 * Constructor.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @since   1.0
	 */
	public function __construct(Container $container)
	{
		$this->setContainer($container);

		$this->application = $container->get('app');

		$this->profiler = $container->get('app')->get('debug.system') ? new Profiler('Tracker') : null;

		if ($this->profiler)
		{
			$this->profiler->setStart(JTRACKER_START_TIME, JTRACKER_START_MEMORY);
		}

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
	 * Get the debug output.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getOutput()
	{
		return (new Html)
			->setContainer($this->getContainer())
			->render();
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
		$this->log['db'] = [];

		if ($this->application->get('debug.database'))
		{
			$db = $this->getContainer()->get('db');
			$db->setDebug(true);
			$db->setLogger(new Logger('JTracker', [new NullHandler], [[$this, 'addDatabaseEntry']]));
		}

		if (!$this->application->get('debug.logging'))
		{
			$this->setLogger(new Logger('JTracker', [new NullHandler]));

			return $this;
		}

		$this->setLogger(
			new Logger(
				'JTracker',
				[
					new StreamHandler(
						$this->getLogPath('root') . '/error.log',
						Logger::ERROR
					),
				],
				[
					new WebProcessor,
				]
			)
		);

		return $this;
	}

	/**
	 * Mark a profile point.
	 *
	 * @param   string  $name  The profile point name.
	 *
	 * @return  null|\Joomla\Profiler\ProfilerInterface
	 *
	 * @since   1.0
	 */
	public function mark($name)
	{
		return $this->profiler ? $this->profiler->mark($name) : null;
	}

	/**
	 * Add an entry from the database.
	 *
	 * @param   array  $record  The log record.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function addDatabaseEntry($record)
	{
		// $db = $this->getContainer()->get('db');

		if (isset($record['context']) === false)
		{
			return $record;
		}

		$context = $record['context'];

		$entry = new \stdClass;

		$entry->sql   = $context['sql'] ?? 'n/a';
		$entry->times = $context['times'] ?? 'n/a';
		$entry->trace = $context['trace'] ?? 'n/a';

		if ($entry->sql == 'SHOW PROFILE')
		{
			return $record;
		}

		// $db->setQuery('SHOW PROFILE');
		$entry->profile = '';

		// $db->loadAssocList();

		/*
				/ Get the profiling information
					$cursor = mysqli_query($this->connection, 'SHOW PROFILE');
					$profile = '';
		*/

		$entry->profile = $context['profile'] ?? 'n/a';

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
			if (\array_key_exists($category, $this->log) === false)
			{
				throw new \UnexpectedValueException(__METHOD__ . ' unknown category: ' . $category);
			}

			return $this->log[$category];
		}

		return $this->log;
	}

	/**
	 * Render the profiler output.
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 */
	public function getProfile()
	{
		$points = $this->profiler->getPoints();

		$pointStart = $points[0]->getName();
		$pointEnd   = $points[\count($points) - 1]->getName();

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
		if ($type == 'root')
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

			return JPATH_ROOT . '/logs';
		}

		if ($type == 'php')
		{
			return ini_get('error_log');
		}

		// @todo: remove the rest..

		$logPath = $this->application->get('debug.' . $type . '-log');

		if (!realpath(\dirname($logPath)))
		{
			$logPath = JPATH_ROOT . '/' . $logPath;
		}

		if (realpath(\dirname($logPath)))
		{
			return realpath($logPath);
		}

		return JPATH_ROOT;
	}
}
