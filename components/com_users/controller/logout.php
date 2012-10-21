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
 * Controller class to log out a user.
 *
 * @package     JTracker
 * @subpackage  com_users
 * @since       1.0
 */
class UsersControllerLogout extends JControllerBase
{
	/**
	 * Method to log out a user.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		JSession::checkToken('request') or jexit(JText::_('JInvalid_Token'));

		$app = $this->getApplication();

		// Perform the log out.
		$error = $app->logout();

		// Check if the log out succeeded.
		if (!($error instanceof Exception))
		{
			// Get the return url from the request and validate that it is internal.
			$return = base64_decode($input->post->get('return', '', 'BASE64'));
			if (!JUri::isInternal($return))
			{
				$return = '';
			}

			// Redirect the user.
			$app->redirect(JRoute::_($return, false));
		}
		else
		{
			$app->redirect(JRoute::_('index.php?option=com_users&view=login', false));
		}
	}

}
