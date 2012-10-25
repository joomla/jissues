<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Model class to edit an item via the tracker component.
 *
 * @package     JTracker
 * @subpackage  com_tracker
 * @since       1.0
 */
class TrackerModelEdit extends TrackerModelIssue
{
	/**
	 * Method to get the comments for an item.
	 *
	 * @param   integer  $id  The id of the primary key.
	 *
	 * @return  JRegistry  JRegistry object containing the field data.
	 *
	 * @since   1.0
	 */
	public function getFields($id)
	{
		$db    = $this->getDb();
		$query = $db->getQuery(true);
		$query->select('fv.field_id, fv.value');
		$query->from($db->quoteName('#__tracker_fields_values', 'fv'));
		$query->where($db->quoteName('issue_id') . '=' . $id);

		try
		{
			$db->setQuery($query);
			$fields = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return false;
		}

		$arr = array();

		// Prepare the fields for display
		foreach ($fields as $field)
		{
			$arr[$field->field_id] = $field->value;
		}

		$item = new JRegistry($arr);

		return $item;
	}
}
