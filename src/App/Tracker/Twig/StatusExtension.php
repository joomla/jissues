<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Twig;

use Joomla\Database\DatabaseDriver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension integrating status support
 *
 * @since  1.0
 */
class StatusExtension extends AbstractExtension
{
	/**
	 * Database driver
	 *
	 * @var    DatabaseDriver
	 * @since  1.0
	 */
	private $db;

	/**
	 * Cached status data, lazy loaded when needed
	 *
	 * @var    array|null
	 * @since  1.0
	 */
	private $statuses;

	/**
	 * Constructor.
	 *
	 * @param   DatabaseDriver  $db  Database driver.
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->db = $db;
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return  TwigFunction[]  An array of functions.
	 *
	 * @since   1.0
	 */
	public function getFunctions()
	{
		return [
			new TwigFunction('status', [$this, 'getStatus']),
			new TwigFunction('statuses_by_state', [$this, 'getStatusesByState']),
		];
	}

	/**
	 * Get a status object based on its ID.
	 *
	 * @param   integer  $id  The status id
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function getStatus($id)
	{
		if ($this->statuses === null)
		{
			$items = $this->db->setQuery(
				$this->db->getQuery(true)
					->from($this->db->quoteName('#__status'))
					->select('*')
			)->loadObjectList();

			$statuses = [];

			foreach ($items as $status)
			{
				$status->cssClass = $status->closed ? 'error' : 'success';

				$statuses[$status->id] = $status;
			}

			$this->statuses = $statuses;
		}

		if (!array_key_exists($id, $this->statuses))
		{
			throw new \UnexpectedValueException('Unknown status ID:' . (int) $id);
		}

		return $this->statuses[$id];
	}

	/**
	 * Get a text list of statuses based on an issue state.
	 *
	 * @param   integer  $state  The state of issue: 0 - open, 1 - closed.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getStatusesByState($state = null)
	{
		switch ((string) $state)
		{
			case '0':
				return [
					1 => 'New',
					2 => 'Confirmed',
					3 => 'Pending',
					4 => 'Ready To Commit',
					6 => 'Needs Review',
					7 => 'Information Required',
					14 => 'Discussion',
				];

			case '1':
				return [
					5 => 'Fixed in Code Base',
					8 => 'Unconfirmed Report',
					9 => 'No Reply',
					10 => 'Closed',
					11 => 'Expected Behaviour',
					12 => 'Known Issue',
					13 => 'Duplicate Report',
				];

			default:
				return [
					1 => 'New',
					2 => 'Confirmed',
					3 => 'Pending',
					4 => 'Ready To Commit',
					6 => 'Needs Review',
					7 => 'Information Required',
					14 => 'Discussion',
					5 => 'Fixed in Code Base',
					8 => 'Unconfirmed Report',
					9 => 'No Reply',
					10 => 'Closed',
					11 => 'Expected Behaviour',
					12 => 'Known Issue',
					13 => 'Duplicate Report',
				];
		}
	}
}
