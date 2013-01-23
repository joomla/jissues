<?php
/**
 * @package     JTracker
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Controller class to log in a user with GitHub.
 *
 * @package     JTracker
 * @subpackage  com_users
 * @since       1.0
 */
class UsersControllerGhlogin extends JControllerBase
{
	/**
	 * Method to log in a user using oAuth on GitHub.
	 *
	 * This method handles the response of the initial request.
	 *
	 * @see              http://developer.github.com/v3/oauth/
	 *
	 * @throws DomainException
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since            1.0
	 */
	public function execute()
	{
		/* @var $app JApplicationSite */
		$app = $this->getApplication();

		$redirect = $this->input->getBase64('usr_redirect')
			? base64_decode($this->input->getBase64('usr_redirect'))
			: JRoute::_('index.php?option=com_users&view=login');

		$user = JGithubLoginhelper::login();

		if (false == $user)
		{
			$app->redirect($redirect);

			return false;
		}

		$theHardCodedPassword = '123456789012';

		// Check if the user is already a registered JUser

		if ($user->id)
		{
			// The user is already a JUser

			$credentials = array(
				'username' => $user->username,
				'password' => $theHardCodedPassword
			);
		}
		else
		{
			// Register a new JUser

			$credentials = array(
				'username'  => $user->username,
				'password'  => $theHardCodedPassword,
				'password1' => $theHardCodedPassword,
				'name'      => $user->name,
				'email1'    => $user->email
			);

			// Register a dummy mailer. - @todo remove
			JFactory::$mailer = new JTrackerDummymailer;

			$model = new UsersModelRegistration;

			if (false == $model->register($credentials))
			{
				$app->enqueueMessage('Can not register.', 'error');

				$app->redirect($redirect);
			}
		}

		// Login

		if (false == $app->login($credentials))
		{
			$app->enqueueMessage('Can not login.', 'error');
		}
		else
		{
			$app->enqueueMessage('Login successful');
		}

		$app->redirect($redirect);

		return false;
	}
}
