<?php
/**
 * @package     JTracker
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Controller class to activate a user.
 *
 * @package     JTracker
 * @subpackage  com_users
 * @since       1.0
 */
class UsersControllerActivate extends JControllerBase
{
	/**
	 * Method to activate a user.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$user    = JFactory::getUser();
		$input   = $this->getInput();
		$uParams = JComponentHelper::getParams('com_users');
		$app     = $this->getApplication();

		// If the user is logged in, return them back to the homepage.
		if ($user->get('id'))
		{
			$app->redirect('index.php');
			return true;
		}

		// If user registration or account activation is disabled, throw a 403.
		if ($uParams->get('useractivation') == 0 || $uParams->get('allowUserRegistration') == 0)
		{
			$app->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');
			return false;
		}

		$model = new UsersModelRegistration;
		$token = $input->getAlnum('token');

		// Check that the token is in a valid format.
		if ($token === null || strlen($token) !== 32)
		{
			$app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			return false;
		}

		// Attempt to activate the user.
		try
		{
			$return = $model->activate($token);
		}
		catch (RuntimeException $e)
		{
			// Redirect back to the homepage.
			$app->enqueueMessage(JText::sprintf('COM_USERS_REGISTRATION_SAVE_FAILED', $e->getMessage()), 'warning');
			$app->redirect('index.php');
			return false;
		}

		$useractivation = $uParams->get('useractivation');

		// Redirect to the login screen.
		if ($useractivation == 0)
		{
			$app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_SAVE_SUCCESS'));
			$app->redirect(JRoute::_('index.php?option=com_users&view=login', false));
		}
		elseif ($useractivation == 1)
		{
			$app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_ACTIVATE_SUCCESS'));
			$app->redirect(JRoute::_('index.php?option=com_users&view=login', false));
		}
		elseif ($return->getParam('activate'))
		{
			$app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_VERIFY_SUCCESS'));
			$app->redirect(JRoute::_('index.php?option=com_users&view=registration&layout=complete', false));
		}
		else
		{
			$app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_ADMINACTIVATE_SUCCESS'));
			$app->redirect(JRoute::_('index.php?option=com_users&view=registration&layout=complete', false));
		}

		return true;
	}
}
