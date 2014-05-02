<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Make;

/**
 * Class for generating class doc blocks in JTracker\Database\AbstractDatabaseTable classes
 *
 * @since  1.0
 */
class Dbcomments extends Make
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Generate file headers for Table classes';

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Make DB Comments');

		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		$since = '1.0';

		$showProps = false;

		// This is a TAB character
		$tab = '	';

		foreach ($db->getTableList() as $table)
		{
			// Transform "jos_my_table_name" => "my_table_name"
			$tableName = str_replace($db->getPrefix(), '', $table);

			// Transform "my_table_name" => "MyTableNameTable"
			$className = str_replace('_', ' ', $tableName);
			$className = str_replace(' ', '', ucwords($className));
			$className .= 'Table';

			$HACKedTableName = '#__' . $tableName;

			$this->out('/** ')
				->out(' * Table interface class for the "' . $tableName . '" database table.')
				->out(' *')
				->out(' * @Entity')
				->out(' * @Table(name="' . $HACKedTableName . '")');

			$columns = $db->getTableColumns($table, false);

			if ($showProps)
			{
				$maxVals = $this->getMaxVals($columns);

				foreach ($columns as $line)
				{
					$this->out(' *')
						->out(
							' * @property'
							. '   ' . $line->proptype
							. str_repeat(' ', $maxVals->maxType - strlen($line->type))
							. '  ' . $line->name
							. str_repeat(' ', $maxVals->maxName - strlen($line->name))
							. '  ' . $line->comment
						);
				}
			}

			$this->out(' *')
				->out(' * @since  ' . $since)
				->out(' */')
				->out(sprintf('class %s extends %s', $className, 'AbstractDatabaseTable'))
				->out('{');

			foreach ($columns as $column)
			{
				$this->out($tab . '/**')
					->out($tab . ' * ' . ($column->Comment ? : $column->Field))
					->out($tab . ' *');

				if ('PRI' == $column->Key)
				{
					$this->out($tab . ' * @Id')
						->out($tab . ' * @GeneratedValue');
				}

				$this->out(
					$tab . ' * @Column('
					. 'name="' . $column->Field . '"'
					. ', type="' . $this->getColumnType($column->Type) . '"'
					. (preg_match('/\(([0-9]+)\)/', $column->Type, $matches) ? ', length=' . $matches[1] : '')
					. ', nullable=' . ('YES' == $column->Null ? 'true' : 'false')
					. ')'
				);

				$this->out($tab . ' *')
					->out($tab . ' * @var  ' . $this->getPropertyType($column->Type))
					->out($tab . ' *')
					->out($tab . ' * @since  ' . $since)
					->out($tab . ' */')
					->out($tab . 'private $' . $this->toCamelCase($column->Field) . ';')
					->out();
			}

			// Getters and Setters

			foreach ($columns as $column)
			{
				$this->out($tab . '/**')
					->out($tab . ' * Get:  ' . ($column->Comment ? : $column->Field))
					->out($tab . ' *')
					->out($tab . ' * @return   ' . $this->getPropertyType($column->Type))
					->out($tab . ' *')
					->out($tab . ' * @since  ' . $since)
					->out($tab . ' */')
					->out($tab . 'public function get' . ucfirst($this->toCamelCase($column->Field)) . '()')
					->out($tab . '{')
					->out($tab . $tab . 'return $this->' . $this->toCamelCase($column->Field) . ';')
					->out($tab . '}')
					->out()
					->out($tab . '/**')
					->out($tab . ' * Set:  ' . ($column->Comment ? : $column->Field))
					->out($tab . ' *')
					->out(
						$tab . ' * @param   ' . $this->getPropertyType($column->Type)
						. '  $' . $this->toCamelCase($column->Field)
						. '  ' . ($column->Comment ? : $column->Field)
					)
					->out($tab . ' *')
					->out($tab . ' * @return   $this')
					->out($tab . ' *')
					->out($tab . ' * @since  ' . $since)
					->out($tab . ' */')
					->out($tab . 'public function set' . ucfirst($this->toCamelCase($column->Field)) . '($' . $this->toCamelCase($column->Field) . ')')
					->out($tab . '{')
					->out($tab . $tab . '$this->' . $this->toCamelCase($column->Field) . ' = $' . $this->toCamelCase($column->Field) . ';')
					->out()
					->out($tab . $tab . 'return $this;')
					->out($tab . '}')
					->out();
			}

				$this->out('}');

			$this->out();
		}

		$this->out()
			->out('Finished =;)');
	}

	/**
	 * Get the maximum values to align doc comments.
	 *
	 * @param   array  $columns  The table columns.
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 */
	private function getMaxVals(array $columns)
	{
		$maxType = 0;
		$maxName = 0;

		foreach ($columns as $column)
		{
			$len   = strlen($column->Type);
			$maxType = $len > $maxType ? $len : $maxType;

			$len   = strlen($column->Field);
			$maxName = $len > $maxName ? $len : $maxName;
		}

		$values = new \stdClass;

		$values->maxType = $maxType;
		$values->maxName = $maxName;

		return $values;
	}

	/**
	 * Get a PHP data type from a SQL data type.
	 *
	 * @param   string  $type  The SQL data type.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	private function getPropertyType($type)
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

	/**
	 * Get a Doctrine column type from a SQL data type.
	 *
	 * @param   string  $type  The SQL data type.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	private function getColumnType($type)
	{
		if (0 === strpos($type, 'int'))
		{
			return 'integer';
		}

		$typeName = substr($type, 0, strpos($type, '(')) ? : $type;

		switch ($typeName)
		{
			case 'varchar' :
				$typeName = 'string';
				break;
			case 'mediumtext' :
				$typeName = 'text';
				break;
			case 'tinyint' :
				$typeName = 'smallint';
				break;
		}

		return $typeName;
	}

	/**
	 * Convert a string to CamelCase.
	 *
	 * @param   string  $string  The string.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	private function toCamelCase($string)
	{
		$parts = explode('_', $string);

		if (1 == count($parts))
		{
			return $string;
		}

		$first = array_shift($parts);

		$result = implode(' ', $parts);
		$result = ucwords($result);
		$result = str_replace(' ', '', $result);

		return $first . $result;
	}
}
