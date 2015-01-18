<?php
/**
 * Part of the Joomla! Tracker
 *
 * @copyright  Copyright (C) 2015 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects\Action\GitHub;

use App\Projects\Action\AbstractAction;

use JTracker\GitHub\Github;

/**
 * Class AbstractGitHub
 *
 * @since  1.0
 */
class AbstractGitHub extends AbstractAction
{
	/**
	 * @var GitHub
	 */
	private $gitHub;

	/**
	 * Get a GitHub object.
	 *
	 * @since   1.0
	 *
	 * @return Github
	 */
	public function getGitHub()
	{
		if (!$this->gitHub)
		{
			throw new \UnexpectedValueException('GitHub object not set!');
		}

		return $this->gitHub;
	}

	/**
	 * Set the GitHub object.
	 *
	 * @param   Github  $gitHub  The GitHub object
	 *
	 * @since   1.0
	 *
	 * @return  $this
	 */
	public function setGitHub($gitHub)
	{
		$this->gitHub = $gitHub;

		return $this;
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
	public function run(array $params, $actionParams)
	{
		throw new \RuntimeException(__METHOD__ . ' must be implemented in child class!');
	}
}
