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
 * Controller class to edit an item via the tracker component.
 *
 * @package     JTracker
 * @subpackage  com_tracker
 * @since       1.0
 */
class TrackerControllerEdit extends JControllerTracker
{
	/**
	 * Execute the controller.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since            1.0
	 */
	public function execute()
	{
		/* @var JApplicationSite $app */
		$app     = $this->getApplication();
		$model   = new TrackerModelIssue;
		$table   = JTable::getInstance('Issue');
		$cid     = $this->input->post->get('cid', array(), 'array');
		$context = 'com_tracker.edit.issue';

		// Determine the name of the primary key for the data.
		$key = $table->getKeyName();

		// To avoid data collisions the urlVar may be different from the primary key.
		// TODO - Any way to make this configurable?
		$urlVar = $key;

		// Get the previous record id (if any) and the current record id.
		$recordId = (int) (count($cid) ? $cid[0] : $this->input->getInt($urlVar));
		$checkin  = property_exists($table, 'checked_out');

		// Access check.
		if (!$this->allowEdit('com_tracker'))
		{
			$app->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
			$app->redirect(JRoute::_('index.php?option=com_tracker&view=issues' . $this->getRedirectToListAppend(), false));

			return false;
		}

		// Attempt to check-out the new record for editing and redirect.
		if ($checkin)
		{
			try
			{
				$model->checkout($recordId);
			}
			catch (Exception $e)
			{
				// Check-out failed, display a notice but allow the user to see the record.
				$app->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
				$app->redirect(JRoute::_('index.php?option=com_tracker&view=edit' . $this->getRedirectToItemAppend(), false));
			}

			return false;
		}

		// Check-out succeeded, push the new record id into the session.
		$this->holdEditId($context, $recordId);
		$app->setUserState($context . '.data', null);

		$app->redirect(JRoute::_('index.php?option=com_tracker&view=edit' . $this->getRedirectToItemAppend($recordId, $urlVar), false));

		return true;
	}
}
