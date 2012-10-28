#!/usr/bin/env php
<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// We are a valid entry point.
const _JEXEC = 1;

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

// Bootstrap the Tracker application libraries.
require_once JPATH_LIBRARIES . '/tracker.php';

// Bootstrap the Joomla Platform.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Configure error reporting to maximum for CLI output.
error_reporting(-1);
ini_set('display_errors', 1);

/**
 * CLI Script to process a CSV export from joomlacode.org tracker
 * and inject them to the database.
 *
 * @package     JTracker
 * @subpackage  CLI
 * @since       1.0
 */
class TrackerApplicationProcesscsv extends JApplicationCli
{
	/**
	 * Method to run the application routines.
	 *
	 * @return  void
	 */
	protected function doExecute()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->from('#__status')
			->select('id, status');

		$res = $db->setQuery($query)->loadObjectList();

		$stati = array();

		foreach ($res as $r)
		{
			$stati[$r->id] = $r->status;
		}

		$query->clear();

		$query->insert('#__issues')
			->columns(
			array('id', 'jc_id', 'project_id', 'title', 'priority', 'status',
				'opened', 'closed_date', 'modified')
		);

		$itemCount = 0;
		$fileCount = 0;

		/* @var DirectoryIterator $fileInfo */
		foreach (new DirectoryIterator(JPATH_BASE . '/csv') as $fileInfo)
		{
			if ($fileInfo->isDot())
			{
				continue;
			}

			$this->out(sprintf('Processing %s...', $fileInfo->getFilename()), false);

			$lines = file($fileInfo->getPathname());

			$query->clear('values');

			foreach ($lines as $i => $line)
			{
				if (0 == $i)
				{
					// Header
					continue;
				}

				$parts = explode(',', $line);

				if (11 != count($parts))
				{
					// This is BAD :(
					$this->out(
						sprintf('ERROR: Found %d parts on line %d -- :(',
							count($parts), $i + 1
						)
					);

					continue;
				}

				// @todo get the proper id
				$project_id = 2;

				$status = 1;

				foreach ($stati as $id => $s)
				{
					if (0 === strpos(strtolower($parts[6]), $s))
					{
						$status = $id;
						break;
					}
				}

				$values = array(
					// ID
					(int) $parts[0],
					// Joomlacode ID
					(int) $parts[0],
					// Project ID
					$project_id,
					// Title
					$db->q($parts[1]),
					// Priority
					(int) $parts[2],
					// Assignee

					// Submitted by

					// Category

					// Status
					$status,
					// Easy...

					// Open
					$db->q($parts[8]),
					// Closed
					$db->q($parts[9]),
					// Modified
					$db->q($parts[10])
				);

				$query->values(implode(',', $values));

				$itemCount++;
			}

			$db->setQuery($query)->execute();

			$this->out('ok');

			$fileCount++;
		}

		// Output the final result
		$this->out()
			->out(sprintf('Added %d items from %d files to the tracker.', $itemCount, $fileCount), true);
	}
}

try
{
	JApplicationCli::getInstance('TrackerApplicationProcesscsv')
		->execute();
}
catch (Exception $e)
{
	echo $e->getMessage() . "\n\n";

	echo $e->getTraceAsString();
}
