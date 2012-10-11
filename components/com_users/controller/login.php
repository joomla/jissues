<?php
/**
 * User: elkuku
 * Date: 09.10.12
 * Time: 21:17
 */

class UsersControllerLogin extends JControllerBase
{
	/**
	 * Method to log in a user.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 */
	public function execute()
	{
		JSession::checkToken('post') or jexit(JText::_('JInvalid_Token'));

		$application = JFactory::getApplication();

		// Populate the data array:
		$data = array();
		$data['return'] = base64_decode($application->input->post->get('return', '', 'BASE64'));
		$data['username'] = $application->input->post->getUsername('username');
		$data['password'] = $application->input->post->get('password', 'none');

		// Set the return URL if empty.
		if (empty($data['return']))
		{
			$data['return'] = 'index.php?option=com_users&view=profile';
		}

		// Set the return URL in the user state to allow modification by plugins
		$application->setUserState('users.login.form.return', $data['return']);

		// Get the log in options.
		$options = array();
		$options['remember'] = $this->input->getBool('remember', false);
		$options['return'] = $data['return'];

		// Get the log in credentials.
		$credentials = array();
		$credentials['username'] = $data['username'];
		$credentials['password'] = $data['password'];

		try
		{
			// Perform the log in.
			$legacyReturn = $application->login($credentials, $options);

			if (false === $legacyReturn)
			{
				// Login failed !
				$data['remember'] = (int) $options['remember'];
				$application->setUserState('users.login.form.data', $data);
				$application->redirect(JRoute::_('index.php?option=com_users&view=login', false));

				return false;
			}

			// Success
			$application->setUserState('users.login.form.data', array());
			$application->redirect(JRoute::_($application->getUserState('users.login.form.return', 'index.php'), false));
		}
		catch (Exception $e)
		{
			// Login failed !
			$application->enqueueMessage($e->getMessage(), 'error');

			$data['remember'] = (int) $options['remember'];
			$application->setUserState('users.login.form.data', $data);
			$application->redirect(JRoute::_('index.php?option=com_users&view=login', false));

			return false;

		}

		return true;
	}
}
