#!/usr/bin/env php
<?php
/**
 * This scans the database and generates doc blocks for the table fields.
 */

use CliApp\Application\TrackerApplication;

'cli' == PHP_SAPI
|| die("\nThis script must be run from the command line interface.\n\n");

version_compare(PHP_VERSION, '5.3.10') >= 0
|| die("\nThis application requires PHP version >= 5.3.10 (Your version: " . PHP_VERSION . ")\n\n");

// Configure error reporting to maximum for CLI output.
error_reporting(-1);
ini_set('display_errors', 1);

// Load the autoloader
$loader = require __DIR__ . '/../vendor/autoload.php';

// Add the namespace for our application to the autoloader.
$loader->add('CliApp', __DIR__ . '/../cli');

/**
 * CLI application for installing the tracker application
 *
 * @since  1.0
 */
class MyApplication extends TrackerApplication
{
	/**
	 * Method to run the application routines.  Most likely you will want to instantiate a controller
	 * and execute it, or perform some sort of task directly.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function doExecute()
	{
		$db = $this->getDatabase();

		$tables = $db->getTableList();

		$comms = array();

		foreach ($tables as $table)
		{
			$fields = $db->getTableColumns($table, false);

			$lines = array();

			foreach ($fields as $field)
			{
				$com = new stdClass;

				$com->type    = $this->getType($field->Type);
				$com->name    = '$' . $field->Field;
				$com->comment = $field->Comment ? $field->Comment : $field->Field;

				$lines[] = $com;
			}

			$comms[$table] = $lines;
		}

		foreach ($comms as $table => $com)
		{
			$this->out(' * ' . $table);

			$maxVals = $this->getMaxVals($com);

			foreach ($com as $line)
			{
				$l = '';
				$l .= ' * @property';
				$l .= '   ' . $line->type;
				$l .= str_repeat(' ', $maxVals->maxType - strlen($line->type));
				$l .= '  ' . $line->name;
				$l .= str_repeat(' ', $maxVals->maxName - strlen($line->name));
				$l .= '  ' . $line->comment;

				$this->out($l);
			}

			$this->out();
		}
	}

	private function getMaxVals($lines)
	{
		$mType = 0;
		$mName = 0;

		foreach ($lines as $line)
		{
			$len   = strlen($line->type);
			$mType = $len > $mType ? $len : $mType;

			$len   = strlen($line->name);
			$mName = $len > $mName ? $len : $mName;
		}

		$v = new stdClass;

		$v->maxType = $mType;
		$v->maxName = $mName;

		return $v;
	}

	private function getType($type)
	{
		if (0 === strpos($type, 'int')
			|| 0 === strpos($type, 'tinyint')
		)
		{
			return 'integer';
		}

		if (0 === strpos($type, 'varchar')
			|| 0 === strpos($type, 'text')
			|| 0 === strpos($type, 'mediumtext')
			|| 0 === strpos($type, 'datetime')
		)
		{
			return 'string';
		}

		return $type;
	}
}


try
{
	(new MyApplication)->execute();
}
catch (\Exception $e)
{
	echo "\n\nERROR: " . $e->getMessage() . "\n\n";

	echo $e->getTraceAsString();

	exit($e->getCode() ? : 255);
}
