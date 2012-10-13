<?php
/**
 * User: elkuku
 * Date: 10.10.12
 * Time: 10:13
 */

class UsersControllerSave extends JControllerBase
{
	/**
	 * Method to save a user's profile data.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$app    = $this->getApplication();
		$model  = new UsersModelProfile;
		$user   = JFactory::getUser();
		$userId = (int) $user->get('id');

		// Get the user data.
		$data = $this->input->post->get('jform', array(), 'array');

		// Force the ID to this user.
		$data['id'] = $userId;

		// Validate the posted data.
		$form = $model->getForm();
		if (!$form)
		{
			//JError::raiseError(500, $model->getError());
			return false;
		}

		// Validate the posted data.
		$data = $model->validate($form, $data);

		// Check for errors.
		if ($data === false)
		{
			// Save the data in the session.
			$app->setUserState('com_users.edit.profile.data', $data);

			// Redirect back to the edit screen.
			$userId = (int) $app->getUserState('com_users.edit.profile.id');
			$app->redirect(JRoute::_('index.php?option=com_users&view=profile&layout=edit&user_id=' . $userId, false));
			return false;
		}

		// Attempt to save the data.
		try
		{
			$return = $model->save($data);
		}
		catch (RuntimeException $e)
		{
			// Save the data in the session.
			$app->setUserState('com_users.edit.profile.data', $data);

			// Redirect back to the edit screen.
			$userId = (int) $app->getUserState('com_users.edit.profile.id');
			$app->enqueueMessage($e->getMessage(), 'warning');
			$app->redirect(JRoute::_('index.php?option=com_users&view=profile&layout=edit&user_id=' . $userId, false));
			return false;
		}

		// Redirect the user and adjust session state based on the chosen task.
		switch ($app->input->get('task'))
		{
			case 'apply':
				// Check out the profile.
				$app->setUserState('com_users.edit.profile.id', $return);
				$model->checkout($return);

				// Redirect back to the edit screen.
				$app->enqueueMessage(JText::_('COM_USERS_PROFILE_SAVE_SUCCESS'));
				$app->redirect(JRoute::_(($redirect = $app->getUserState('com_users.edit.profile.redirect')) ? $redirect : 'index.php?option=com_users&view=profile&layout=edit&hidemainmenu=1', false));
				break;

			default:
				// Check in the profile.
				$userId = (int) $app->getUserState('com_users.edit.profile.id');
				if ($userId)
				{
					$model->checkin($userId);
				}

				// Clear the profile id from the session.
				$app->setUserState('com_users.edit.profile.id', null);

				// Redirect to the list screen.
				$app->enqueueMessage(JText::_('COM_USERS_PROFILE_SAVE_SUCCESS'));
				$app->redirect(JRoute::_(($redirect = $app->getUserState('com_users.edit.profile.redirect')) ? $redirect : 'index.php?option=com_users&view=profile&user_id=' . $return, false));
				break;
		}

		// Flush the data from the session.
		$app->setUserState('com_users.edit.profile.data', null);
	}
}
