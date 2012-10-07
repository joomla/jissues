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
 
 // Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);

 
class TrackerModelIssue extends JModelDatabase
{
	public function getItem($id)
	{
		// Load the query for the list
		$query = $this->getItemQuery($id);
		
		try
		{
			$this->db->setQuery($query);
			$item = $this->db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			$this->setError($e->getMessage());
			return false;
		}
		
		//$item = " test";

		return $item;
	}
	
	protected function getItemQuery($id) {
		
		//get the item and return
		$db    = $this->getDb();
		$query = $db->getQuery(true);
		
		$query->select($db->quoteName(array('a.id', 'a.gh_id', 'a.title', 'a.description', 'a.priority', 'a.status', 'a.opened', 'a.closed', 'a.modified')));
		$query->from($db->quoteName('#__issues', 'a'));
		$query->where('a.id = ' . (int) $id);
		
		//$db->setQuery($query);
		
	
		return $query;
	}
}