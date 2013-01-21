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
class UsersControllerGhlogin extends JControllerTracker
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
		if (false == JGithubLoginhelper::login())
		{
			JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_users&view=login', false));

			return false;
		}

		JFactory::getApplication()->enqueueMessage('Login successful');

		$usrRedirect = $this->input->getBase64('usr_redirect');

		if ($usrRedirect)
		{
			JFactory::getApplication()->redirect(base64_decode($usrRedirect), false);

			return false;
		}

		return true;
	}
}
