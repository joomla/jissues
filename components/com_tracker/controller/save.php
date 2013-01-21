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
 * Controller class to save an item via the tracker component.
 *
 * @package     JTracker
 * @subpackage  com_tracker
 * @since       1.0
 */
class TrackerControllerSave extends JControllerTracker
{
	/**
	 * Execute the task.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		/* @var JApplicationSite $app */
		$app     = $this->getApplication();
		$model   = new TrackerModelIssue;
		$table   = $model->getTable('Issue');
		$data    = $this->input->post->get('jform', array(), 'array');
		$checkin = property_exists($table, 'checked_out');
		$context = $this->option . '.edit.' . $model->getName();
        
		// Determine the name of the primary key for the data.
		$key = $table->getKeyName();

		$recordId = $this->input->getInt($key);

		if (!$this->checkEditId($context, $recordId))
		{
			// Somehow the person just went to the form and tried to save it. We don't allow that.
			$app->enqueueMessage(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $recordId), 'error');
			$app->redirect(JRoute::_('index.php?option=' . $this->option . '&view=issue&id=' . $recordId . $this->getRedirectToListAppend(), false));
		}

		// Populate the row id from the session.
		$data[$key] = $recordId;
        $table->save(array('modified_by' => $user));

		// Access check.
		if (!$this->allowSave($data, $key))
		{
			$app->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');
			$app->redirect(JRoute::_('index.php?option=' . $this->option . '&view=issue&id=' . $recordId . $this->getRedirectToListAppend(), false));
		}

		// Attempt to save the data.
		try
		{
			$model->save($data);
		}
		catch (RuntimeException $e)
		{
			// Save the data in the session.
			$app->setUserState($context . '.data', $data);

			// Redirect back to the edit screen.
			$app->enqueueMessage($e->getMessage(), 'error');

			if($recordId)
			{
				$app->redirect(JRoute::_('index.php?option=' . $this->option . '&view=edit' . $this->getRedirectToItemAppend($recordId, $key), false));
			}
			else
			{
				$app->redirect(JRoute::_('index.php?option=' . $this->option . '&view=add', false));
			}
		}

		// Save succeeded, so check-in the record.
		if ($checkin)
		{
			try
			{
				$model->checkin($data[$key]);
			}
			catch (Exception $e)
			{
				// Check-in failed, go back to the record and display a notice.
				$app->enqueueMessage($e->getMessage(), 'error');
				$app->redirect(JRoute::_('index.php?option=' . $this->option . '&view=edit' . $this->getRedirectToItemAppend($recordId, $key), false));
			}
		}

		$app->enqueueMessage(JText::_('Save successful'), 'message');

		// Clear the record id and data from the session.
		$this->releaseEditId($context, $recordId);
		$app->setUserState($context . '.data', null);

		// Redirect to the list screen.
		$app->redirect(JRoute::_('index.php?option=' . $this->option . '&view=issues' . $this->getRedirectToListAppend(), false));

		// Invoke the postSave method to allow for the child class to access the model.
		$this->postSaveHook($model, $data);

		return true;
	}
}
