<?php
/**
 * @package     JTracker
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Utility class for projects
 *
 * @package     JTracker
 * @subpackage  Tracker
 * @since       1.0
 */
class JTrackerProject
{
	/**
	 * @var integer
	 * @since       1.0
	 */
	public $id;

	/**
	 * @var string
	 * @since       1.0
	 */
	public $title;

	/**
	 * @var string
	 * @since       1.0
	 */
	public $alias;

	/**
	 * @var string
	 * @since       1.0
	 */
	public $gh_user;

	/**
	 * @var string
	 * @since       1.0
	 */
	public $gh_project;

	/**
	 * Constructor.
	 *
	 * @param   integer  $id  The project id.
	 *
	 * @throws RuntimeException
	 */
	public function __construct($id)
	{
		$db = JFactory::getDbo();

		$project = $db->setQuery(
			$db->getQuery(true)
				->from($db->qn('#__tracker_projects'))
				->select('*')
				->where($db->qn('project_id') . '=' . (int) $id)
		)->loadObject();

		if (!$project)
		{
			throw new RuntimeException('Invalid project');
		}

		foreach ($this as $k => $v)
		{
			if ('id' == $k)
			{
				continue;
			}

			$this->$k = $project->$k;
		}

		$this->id = $project->project_id;
	}
}
