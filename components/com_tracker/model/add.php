<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Model class to add an item via the tracker component.
 *
 * @package     JTracker
 * @subpackage  com_tracker
 * @since       1.0
 */
class TrackerModelAdd extends TrackerModelIssue
{
	/**
	 * Get a project by its id.
	 *
	 * @todo     move to its own model.
	 *
	 * @throws RuntimeException
	 *
	 * @return mixed|null
	 */
	public function getProject()
	{
		$projectId = JFactory::getApplication()->input->getUint('project_id');

		if (!$projectId)
		{
			$projectId = JFactory::getSession()->get('tracker.project_id');
		}

		if (!$projectId)
		{
			// Panic
			throw new RuntimeException(__METHOD__ . ' - No project given');
		}

		$db = JFactory::getDbo();

		$project = $this->db->setQuery(
			$this->db->getQuery(true)
				->from('#__tracker_projects')
				->select('*')
				->where($db->qn('project_id') . '=' . (int) $projectId)
		)->loadObject();

		return $project;
	}
}
