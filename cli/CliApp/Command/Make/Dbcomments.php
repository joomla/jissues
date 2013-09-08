<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Make;

use JTracker\Container;

/**
 * Class for retrieving issues from GitHub for selected projects
 *
 * @since  1.0
 */
class Dbcomments extends Make
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Generate file headers for Table classes.';
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->application->outputTitle('Make DB Comments');

		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = Container::getInstance()->get('db');

		$tables = $db->getTableList();

		$comms = array();

		foreach ($tables as $table)
		{
			$fields = $db->getTableColumns($table, false);

			$lines = array();

			foreach ($fields as $field)
			{
				$com = new \stdClass;

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

		$this->out()
			->out('Finished =;)');
	}

	/**
	 * Get the maximum values to align doc comments.
	 *
	 * @param   array  $lines  The doc comment.
	 *
	 * @return \stdClass
	 */
	private function getMaxVals(array $lines)
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

		$v = new \stdClass;

		$v->maxType = $mType;
		$v->maxName = $mName;

		return $v;
	}

	/**
	 * Get a PHP data type from a SQL data type.
	 *
	 * @param   string  $type  The SQL data type.
	 *
	 * @return string
	 */
	private function getType($type)
	{
		if (0 === strpos($type, 'int')
			|| 0 === strpos($type, 'tinyint'))
		{
			return 'integer';
		}

		if (0 === strpos($type, 'varchar')
			|| 0 === strpos($type, 'text')
			|| 0 === strpos($type, 'mediumtext')
			|| 0 === strpos($type, 'datetime'))
		{
			return 'string';
		}

		return $type;
	}
}
