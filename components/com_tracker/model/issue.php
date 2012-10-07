<?php
/**
 * @package     BabDev.Tracker
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2012 Michael Babker. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Model to get data for the issue detail view
 *
 * @package     BabDev.Tracker
 * @subpackage  Model
 * @since       1.0
 */
class TrackerModelIssue extends JModelDatabase
{
	public function getItem($id)
	{
		
		//get the item and return
		$db    = $this->getDb();
		$query = $db->getQuery(true);
		
		$query->select($db->quoteName(array('a.id', 'a.gh_id', 'a.title', 'a.description', 'a.priority', 'a.status', 'a.opened', 'a.closed', 'a.modified')));
		
		$query->where('a.id = ' . (int) $id);
		
		$db->setQuery($query);
		
		$data = $db->loadObject();
		
		return $data;
	}
}