<?php
/**
 * Part of the Joomla! Tracker
 *
 * @copyright  Copyright (C) 2015 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects\Action;

use App\Projects\TrackerProject;

use Joomla\Database\DatabaseDriver;

/**
 * Class AbstractAction
 *
 * @since  1.0
 */
abstract class AbstractAction
{
	/**
	 * Project object.
	 *
	 * @var    TrackerProject
	 * @since  1.0
	 */
	protected $project = null;

	/**
	 * @var    DatabaseDriver
	 * @since  1.0
	 */
	protected $database = null;

	/**
	 * Constructor.
	 *
	 * @param   TrackerProject  $project   The project.
	 * @param   DatabaseDriver  $database  The database object.
	 *
	 * @since   1.0
	 */
	public function __construct(TrackerProject $project, DatabaseDriver $database)
	{
		$this->project  = $project;
		$this->database = $database;
	}

	/**
	 * Run the action.
	 *
	 * @param   array   $params        Parameters.
	 * @param   object  $actionParams  Parameters for the action.
	 *
	 * @since   1.0
	 *
	 * @return mixed
	 */
	abstract public function run(array $params, $actionParams);

	/**
	 * Check if all required parameters are set.
	 *
	 * @param   array  $params  Parameters.
	 * @param   array  $types   Parameter types to check for.
	 *
	 * @since   1.0
	 *
	 * @return $this
	 */
	protected function checkParams(array $params, array $types)
	{
		foreach ($types as $type)
		{
			if (false == array_key_exists($type, $params))
			{
				throw new \UnexpectedValueException(
					sprintf('Action of type %1$s not found in params for class %2$s', $type, __CLASS__)
				);
			}
		}

		return $this;
	}
}
