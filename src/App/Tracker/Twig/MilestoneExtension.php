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
 * Twig extension integrating milestone support
 *
 * @since  1.0
 */
class MilestoneExtension extends AbstractExtension
{
	/**
	 * Database driver
	 *
	 * @var    DatabaseDriver
	 * @since  1.0
	 */
	private $db;

	/**
	 * Cached milestone data, lazy loaded when needed
	 *
	 * @var    array|null
	 * @since  1.0
	 */
	private $milestones;

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
			new TwigFunction('milestone_title', [$this, 'getMilestoneTitle']),
		];
	}

	/**
	 * Get the title of the milestone by ID
	 *
	 * @param   integer  $id  The ID of the milestone
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getMilestoneTitle($id)
	{
		if ($this->milestones === null)
		{
			$this->milestones = $this->db->setQuery(
				$this->db->getQuery(true)
					->select($this->db->quoteName(['milestone_id', 'title']))
					->from($this->db->quoteName('#__tracker_milestones'))
			)->loadObjectList();
		}

		foreach ($this->milestones as $milestone)
		{
			if ($milestone->milestone_id == $id)
			{
				return $milestone->title;
			}
		}

		return '';
	}
}
