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
			echo __METHOD__ . 'File could not be written :(';
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
