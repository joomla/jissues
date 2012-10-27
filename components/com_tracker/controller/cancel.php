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
 * Controller class to cancel an edit for an item via the tracker component.
 *
 * @package     JTracker
 * @subpackage  com_tracker
 * @since       1.0
 */
class TrackerControllerCancel extends JControllerTracker
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
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		/* @var JApplicationSite $app */
		$app     = $this->getApplication();
		$model   = new TrackerModelIssue;
		$table   = JTable::getInstance('Issue');
		$checkin = property_exists($table, 'checked_out');
		$context = 'com_tracker.edit.issue';

		// Determine the name of the primary key for the data.
		$key = $table->getKeyName();

		$recordId = $app->input->getInt($key);

		// Attempt to check-in the current record.
		if ($recordId)
		{
			// Check we are holding the id in the edit list.
			if (!$this->checkEditId($context, $recordId))
			{
				// Somehow the person just went to the form - we don't allow that.
				$app->enqueueMessage(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $recordId), 'error');
				$app->redirect(JRoute::_('index.php?option=com_tracker&view=issue' . $this->getRedirectToListAppend(), false));

				return false;
			}

			if ($checkin)
			{
				try
				{
					$model->checkin($recordId);
				}
				catch (Exception $e)
				{
					// Check-in failed, go back to the record and display a notice.
					$app->enqueueMessage($e->getMessage(), 'error');
					$app->redirect(JRoute::_('index.php?option=com_tracker&view=issue&id=' . $recordId . $this->getRedirectToListAppend(), false));
				}
			}
		}

		// Clean the session data and redirect.
		$this->releaseEditId($context, $recordId);
		$app->setUserState($context . '.data', null);

		$app->redirect(JRoute::_('index.php?option=com_tracker&view=issue&id=' . $recordId . $this->getRedirectToListAppend(), false));

		return true;
	}
}
