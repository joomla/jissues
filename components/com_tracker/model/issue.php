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
		// load the row using JTable and getInstance.
		$table = JTable::getInstance('Issue');

		
		if ($id > 0)
		{
			// Attempt to load the row.
			$return = $table->load($id);

			// Check for a table object error.
			if ($return === false && $table->getError())
			{
				$this->setError($table->getError());
				return false;
			}
		}
		
		// Convert to the JObject before adding other data.
		$properties = $table->getProperties(1);
		$item = JArrayHelper::toObject($properties, 'JObject');
		
		return $item;
	}
}