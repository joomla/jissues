<?php
/**
 * User: elkuku
 * Date: 09.10.12
 * Time: 23:34
 */
class UsersControllerActivate extends JControllerBase
{
	/**
	 * Method to activate a user.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 */
	public function execute()
	{
		$user = JFactory::getUser();
		$input = JFactory::getApplication()->input;
		$uParams = JComponentHelper::getParams('com_users');
		$application = JFactory::getApplication();

		// If the user is logged in, return them back to the homepage.
		if ($user->get('id'))
		{
			$application->redirect('index.php');
			return true;
		}

		// If user registration or account activation is disabled, throw a 403.
		if ($uParams->get('useractivation') == 0 || $uParams->get('allowUserRegistration') == 0)
		{
			JFactory::getApplication()->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');
			return false;
		}

		$model = new UsersModelRegistration;
		$token = $input->getAlnum('token');

		// Check that the token is in a valid format.
		if ($token === null || strlen($token) !== 32)
		{
			JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			return false;
		}

		// Attempt to activate the user.
		$return = $model->activate($token);

		// Check for errors.
		if ($return === false)
		{
			// Redirect back to the homepage.
			$application->enqueueMessage(JText::sprintf('COM_USERS_REGISTRATION_SAVE_FAILED', $model->getError()), 'warning');
			$application->redirect('index.php');
			return false;
		}

		$useractivation = $uParams->get('useractivation');

		// Redirect to the login screen.
		if ($useractivation == 0)
		{
			$application->enqueueMessage(JText::_('COM_USERS_REGISTRATION_SAVE_SUCCESS'));
			$application->redirect(JRoute::_('index.php?option=com_users&view=login', false));
		}
		elseif ($useractivation == 1)
		{
			$application->enqueueMessage(JText::_('COM_USERS_REGISTRATION_ACTIVATE_SUCCESS'));
			$application->redirect(JRoute::_('index.php?option=com_users&view=login', false));
		}
		elseif ($return->getParam('activate'))
		{
			$application->enqueueMessage(JText::_('COM_USERS_REGISTRATION_VERIFY_SUCCESS'));
			$application->redirect(JRoute::_('index.php?option=com_users&view=registration&layout=complete', false));
		}
		else
		{
			$application->enqueueMessage(JText::_('COM_USERS_REGISTRATION_ADMINACTIVATE_SUCCESS'));
			$application->redirect(JRoute::_('index.php?option=com_users&view=registration&layout=complete', false));
		}
		return true;
	}
}
