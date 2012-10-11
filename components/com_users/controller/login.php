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
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		JSession::checkToken('post') or jexit(JText::_('JInvalid_Token'));

		$app   = $this->getApplication();
		$input = $this->getInput();

		// Populate the data array:
		$data = array();
		$data['return'] = base64_decode($input->post->get('return', '', 'BASE64'));
		$data['username'] = $input->post->getUsername('username');
		$data['password'] = $input->post->get('password', 'none');

		// Set the return URL if empty.
		if (empty($data['return']))
		{
			$data['return'] = 'index.php?option=com_users&view=profile';
		}

		// Set the return URL in the user state to allow modification by plugins
		$app->setUserState('users.login.form.return', $data['return']);

		// Get the log in options.
		$options = array();
		$options['remember'] = $input->getBool('remember', false);
		$options['return']   = $data['return'];

		// Get the log in credentials.
		$credentials = array();
		$credentials['username'] = $data['username'];
		$credentials['password'] = $data['password'];

		try
		{
			// Perform the log in.
			$legacyReturn = $app->login($credentials, $options);

			if (false === $legacyReturn)
			{
				// Login failed !
				$data['remember'] = (int) $options['remember'];
				$app->setUserState('users.login.form.data', $data);
				$app->redirect(JRoute::_('index.php?option=com_users&view=login', false));

				return false;
			}

			// Success
			$app->setUserState('users.login.form.data', array());
			$app->redirect(JRoute::_($app->getUserState('users.login.form.return', 'index.php'), false));
		}
		catch (Exception $e)
		{
			// Login failed !
			$app->enqueueMessage($e->getMessage(), 'error');

			$data['remember'] = (int) $options['remember'];
			$app->setUserState('users.login.form.data', $data);
			$app->redirect(JRoute::_('index.php?option=com_users&view=login', false));

			return false;
		}

		return true;
	}
}
