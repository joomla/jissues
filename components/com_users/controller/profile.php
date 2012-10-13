<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Profile controller class for Users.
 *
 * @package     Joomla.Site
 * @subpackage  com_users
 * @since       1.6
 */
class UsersControllerProfile extends JControllerBase
{
	/**
	 * Method to check out a user for editing and redirect to the edit form.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$app         = $this->getApplication();
		$user        = JFactory::getUser();
		$loginUserId = (int) $user->get('id');

		if ($user->guest)
		{
			$app->redirect(JRoute::_('index.php?option=com_users', false));

			return true;
		}

		// Get the previous user id (if any) and the current user id.
		$previousId = (int) $app->getUserState('com_users.edit.profile.id');
		$userId     = $this->input->getInt('user_id', null, 'array');

		// Check if the user is trying to edit another users profile.
		if ($userId != $loginUserId)
		{
			throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// Set the user id for the user to edit in the session.
		$app->setUserState('com_users.edit.profile.id', $userId);

		// Get the model.
		$model = new UsersModelProfile;

		// Check out the user.
		if ($userId)
		{
			try
			{
				$model->checkout($userId);
			}
			catch (RuntimeException $e)
			{
				$app->enqueueMessage($e->getMessage());
			}
		}

		// Check in the previous user.
		if ($previousId)
		{
			try
			{
				$model->checkin($previousId);
			}
			catch (RuntimeException $e)
			{
				$app->enqueueMessage($e->getMessage());
			}
		}

		// Redirect to the edit screen.
		$app->redirect(JRoute::_('index.php?option=com_users&view=profile&layout=edit', false));
	}
}
